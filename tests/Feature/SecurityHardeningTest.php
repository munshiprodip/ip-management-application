<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Alerts\Enums\ExportType;
use App\Domain\Security\Enums\UserRole;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\Vrf;
use App\Services\Reporting\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $org = Organization::create(['name' => 'KYAMCH', 'slug' => 'kyamch-sec', 'code' => 'KYAMCH-SEC']);
        $site = Site::create(['name' => 'Main Hospital', 'organization_id' => $org->id, 'code' => 'SEC-HQ']);
        Vrf::create(['name' => 'Default VRF', 'code' => 'VRF-GLOBAL', 'rd' => '65000:1', 'is_default' => true]);

        $this->admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => 'active',
        ]);
    }

    public function test_owasp_security_headers_are_present_on_responses(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_csv_formula_injection_is_sanitized(): void
    {
        $service = app(ReportExportService::class);

        // Test with malicious formula strings
        $maliciousRow = [
            1,
            '=cmd|\' /C calc\'!A0',
            '+123456',
            '-SUM(1+1)',
            '@malicious_directive',
            "normal_value",
        ];

        $sanitized = $service->sanitizeForCsv($maliciousRow);

        // Assert all formula triggers were prepended with apostrophe
        $this->assertEquals("'=cmd|' /C calc'!A0", $sanitized[1]);
        $this->assertEquals("'+123456", $sanitized[2]);
        $this->assertEquals("'-SUM(1+1)", $sanitized[3]);
        $this->assertEquals("'@malicious_directive", $sanitized[4]);
        $this->assertEquals("normal_value", $sanitized[5]);
    }

    public function test_ipam_health_check_command_runs_successfully(): void
    {
        $this->artisan('ipam:health-check')
            ->expectsOutputToContain('KYAMCH Smart IPAM')
            ->expectsOutputToContain('Database Engine')
            ->assertExitCode(0);
    }

    public function test_ipam_purge_expired_command_runs_successfully(): void
    {
        $this->artisan('ipam:purge-expired')
            ->expectsOutputToContain('Purged')
            ->assertExitCode(0);
    }
}
