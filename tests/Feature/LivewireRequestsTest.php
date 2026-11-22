<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Workflows\SubmitRequestAction;
use App\Domain\Security\Enums\UserRole;
use App\Domain\Workflows\Enums\RequestPriority;
use App\Domain\Workflows\Enums\RequestStatus;
use App\Domain\Workflows\Enums\RequestType;
use App\Livewire\Requests\Index as RequestsIndex;
use App\Models\Department;
use App\Models\IpRequest;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\UserScope;
use App\Models\Vrf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $approver;
    protected User $operator;
    protected Department $icuDept;
    protected Subnet $subnet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'status' => 'active',
        ]);

        $this->approver = User::factory()->create([
            'role' => UserRole::APPROVER,
            'status' => 'active',
        ]);

        $this->operator = User::factory()->create([
            'role' => UserRole::OPERATOR,
            'status' => 'active',
        ]);

        $org = Organization::create([
            'name' => 'KYAMCH Main Org',
            'code' => 'KYAMCH-ORG',
            'slug' => 'kyamch-org',
            'status' => 'active',
        ]);

        $site = Site::create([
            'organization_id' => $org->id,
            'name' => 'Main Campus',
            'code' => 'CAMPUS-01',
            'status' => 'active',
        ]);

        $this->icuDept = Department::create([
            'organization_id' => $org->id,
            'site_id' => $site->id,
            'name' => 'Intensive Care Unit',
            'code' => 'ICU-DEPT',
            'status' => 'active',
        ]);

        $vrf = Vrf::firstOrCreate(
            ['code' => 'VRF-GLOBAL'],
            [
                'name' => 'Default Global VRF',
                'description' => 'Mandatory default routing context',
                'status' => 'active',
                'is_default' => true,
            ]
        );

        $this->subnet = Subnet::create([
            'vrf_id' => $vrf->id,
            'name' => 'ICU Assignable Block',
            'cidr' => '10.10.80.0/24',
            'ip_version' => 4,
            'network_hex' => '0a0a5000',
            'start_hex' => '0a0a5001',
            'end_hex' => '0a0a50fe',
            'prefix_length' => 24,
            'subnet_type' => 'assignable',
            'status' => 'active',
        ]);
    }

    /**
     * Verifies that the requests index renders for an authenticated user.
     */
    public function test_requests_index_renders_for_authenticated_operator(): void
    {
        $this->actingAs($this->operator);

        Livewire::test(RequestsIndex::class)
            ->assertStatus(200)
            ->assertSee('IP Requests')
            ->assertSee('Total Requests')
            ->assertSee('Pending Review');
    }

    /**
     * Verifies that the separation of duties banner is displayed when viewing own request.
     */
    public function test_review_drawer_displays_separation_of_duties_banner_for_own_request(): void
    {
        $submitAction = app(SubmitRequestAction::class);

        $request = $submitAction->execute($this->operator, [
            'request_type' => RequestType::IP_ASSIGNMENT,
            'department_id' => $this->icuDept->id,
            'priority' => RequestPriority::NORMAL,
            'reason' => 'Operator testing separation of duties',
            'payload' => ['subnet_id' => $this->subnet->id],
        ]);

        // Operator views their own request
        $this->actingAs($this->operator);

        Livewire::test(RequestsIndex::class)
            ->call('openReviewDrawer', $request->id)
            ->assertSee('Separation of Duties Enforced (Two-Person Rule)')
            ->assertSee('As the author of this request, you cannot approve or reject it.');
    }

    /**
     * Verifies that an independent approver can approve a request through Livewire.
     */
    public function test_independent_approver_can_approve_request(): void
    {
        $submitAction = app(SubmitRequestAction::class);

        $request = $submitAction->execute($this->operator, [
            'request_type' => RequestType::IP_ASSIGNMENT,
            'department_id' => $this->icuDept->id,
            'priority' => RequestPriority::NORMAL,
            'reason' => 'Legitimate deployment request',
            'payload' => ['subnet_id' => $this->subnet->id],
        ]);

        // Approver signs in and opens drawer
        $this->actingAs($this->approver);

        Livewire::test(RequestsIndex::class)
            ->call('openReviewDrawer', $request->id)
            ->assertDontSee('Separation of Duties Enforced (Two-Person Rule)')
            ->set('approvalComments', 'Verified clinical necessity. Approved.')
            ->call('approve', $request->id)
            ->assertHasNoErrors();

        $this->assertEquals(RequestStatus::APPROVED, $request->fresh()->status);
    }

    /**
     * Verifies that an operator can submit a new request via the Livewire modal.
     */
    public function test_operator_can_submit_request_via_modal(): void
    {
        $this->actingAs($this->operator);

        Livewire::test(RequestsIndex::class)
            ->set('formRequestType', 'ip_assignment')
            ->set('formSubnetId', (string) $this->subnet->id)
            ->set('formIpAddress', '10.10.80.77')
            ->set('formHostname', 'ky-icu-dev77.kyamch.local')
            ->set('formPriority', 'urgent')
            ->set('formDepartmentId', (string) $this->icuDept->id)
            ->set('formReason', 'New monitoring workstation for ICU room 12')
            ->call('submitRequest')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ip_requests', [
            'requester_id' => $this->operator->id,
            'priority' => 'urgent',
            'status' => 'pending_approval',
        ]);
    }
}
