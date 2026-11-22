<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Alerts\Enums\ExportStatus;
use App\Domain\Alerts\Enums\ExportType;
use App\Domain\Security\Enums\UserRole;
use App\Models\Department;
use App\Models\ExportJob;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\UserScope;
use App\Models\Vrf;
use App\Services\Reporting\ReportExportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $deptOperator;
    private Department $deptA;
    private Department $deptB;
    private ReportExportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $org = Organization::create(['name' => 'KYAMCH', 'slug' => 'kyamch-export-test', 'code' => 'KYAMCH-EXP']);
        $site = Site::create(['name' => 'Main Hospital', 'organization_id' => $org->id, 'code' => 'EXP-HQ']);
        $this->deptA = Department::create(['name' => 'Cardiology', 'code' => 'CARDIO', 'site_id' => $site->id, 'organization_id' => $org->id]);
        $this->deptB = Department::create(['name' => 'Neurology', 'code' => 'NEURO', 'site_id' => $site->id, 'organization_id' => $org->id]);

        $this->admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => 'active',
        ]);

        $this->deptOperator = User::factory()->create([
            'role' => UserRole::DEPARTMENT_OPERATOR,
            'status' => 'active',
        ]);

        // Assign operator scope to Department A only (T-021)
        UserScope::create([
            'user_id' => $this->deptOperator->id,
            'scope_type' => 'department',
            'scope_id' => $this->deptA->id,
        ]);

        $this->service = app(ReportExportService::class);
    }

    /**
     * Gate T-027: Large export is authorized, bounded and temp file expires in 24 hours.
     */
    public function test_gate_t027_large_export_is_bounded_and_expires_in_24_hours(): void
    {
        $job = $this->service->generateExport($this->admin, ExportType::SUBNETS);

        $this->assertEquals(ExportStatus::COMPLETED, $job->status);
        $this->assertNotNull($job->expires_at);
        $this->assertTrue($job->expires_at->isFuture());
        $this->assertEquals(24, (int) round(now()->diffInHours($job->expires_at)));

        // File is stored in private storage
        Storage::disk('local')->assertExists('private/' . $job->private_file_path);

        // Can retrieve content before expiry
        $content = $this->service->getExportFileContent($job, $this->admin);
        $this->assertStringContainsString('ID,CIDR,Name', $content);

        // Simulate 25 hours later (Past expiry)
        $this->travel(25)->hours();
        $this->assertTrue($job->fresh()->isExpired());

        // Attempting to download expired export throws RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Export file has expired');
        $this->service->getExportFileContent($job, $this->admin);
    }

    /**
     * Gate T-021 / UAT-09: Department operator cannot export scope outside authorized departments.
     */
    public function test_department_operator_cannot_export_unauthorized_scope(): void
    {
        // Operator attempting to export Dept B (unauthorized) -> Throws AuthorizationException
        $this->expectException(AuthorizationException::class);
        $this->service->generateExport($this->deptOperator, ExportType::SUBNETS, [
            'department_id' => $this->deptB->id,
        ]);
    }

    public function test_department_operator_cannot_export_global_audit_events(): void
    {
        // Operator attempting to export global audit logs -> Throws AuthorizationException
        $this->expectException(AuthorizationException::class);
        $this->service->generateExport($this->deptOperator, ExportType::AUDIT_EVENTS);
    }

    public function test_department_operator_can_export_authorized_scope(): void
    {
        // Operator exporting Dept A (authorized scope) -> Succeeds
        $job = $this->service->generateExport($this->deptOperator, ExportType::SUBNETS, [
            'department_id' => $this->deptA->id,
        ]);

        $this->assertEquals(ExportStatus::COMPLETED, $job->status);
        $this->assertEquals($this->deptOperator->id, $job->requested_by);
    }

    public function test_purge_expired_exports_cleans_up_files_and_records(): void
    {
        $job = $this->service->generateExport($this->admin, ExportType::SUBNETS);
        $filePath = 'private/' . $job->private_file_path;
        Storage::disk('local')->assertExists($filePath);

        // Before expiry, purge does nothing
        $purgedCount = $this->service->purgeExpiredExports();
        $this->assertEquals(0, $purgedCount);
        Storage::disk('local')->assertExists($filePath);

        // Travel 25 hours forward
        $this->travel(25)->hours();

        // Purge now cleans up expired jobs
        $purgedCount = $this->service->purgeExpiredExports();
        $this->assertEquals(1, $purgedCount);
        Storage::disk('local')->assertMissing($filePath);
        $this->assertDatabaseMissing('export_jobs', ['id' => $job->id]);
    }

    public function test_download_endpoint_returns_file_and_enforces_expiry(): void
    {
        $job = $this->service->generateExport($this->admin, ExportType::SUBNETS);

        // Valid download returns 200
        $response = $this->actingAs($this->admin)->get(route('reports.exports.download', $job));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Travel 25 hours
        $this->travel(25)->hours();

        // Expired download returns 410 Gone
        $expiredResponse = $this->actingAs($this->admin)->get(route('reports.exports.download', $job));
        $expiredResponse->assertStatus(410);
    }
}
