<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Workflows\ApproveRequestAction;
use App\Actions\Workflows\CancelRequestAction;
use App\Actions\Workflows\ProvisionRequestAction;
use App\Actions\Workflows\RejectRequestAction;
use App\Actions\Workflows\SubmitRequestAction;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\Security\Enums\UserRole;
use App\Domain\Workflows\Enums\RequestPriority;
use App\Domain\Workflows\Enums\RequestStatus;
use App\Domain\Workflows\Enums\RequestType;
use App\Models\AuditEvent;
use App\Models\Department;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\IpAddress;
use App\Models\IpRequest;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\UserScope;
use App\Models\Vrf;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $approver;
    protected User $operator;
    protected User $departmentOperator;
    protected Department $icuDept;
    protected Department $radDept;
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

        $this->departmentOperator = User::factory()->create([
            'role' => UserRole::DEPARTMENT_OPERATOR,
            'status' => 'active',
        ]);

        $org = Organization::create([
            'name' => 'KYAMCH Hospital',
            'code' => 'KYAMCH-TEST',
            'slug' => 'kyamch-test',
            'status' => 'active',
        ]);

        $site = Site::create([
            'organization_id' => $org->id,
            'name' => 'Main Campus',
            'code' => 'MAIN-01',
            'status' => 'active',
        ]);

        $this->icuDept = Department::create([
            'organization_id' => $org->id,
            'site_id' => $site->id,
            'name' => 'Intensive Care Unit',
            'code' => 'ICU',
            'status' => 'active',
        ]);

        $this->radDept = Department::create([
            'organization_id' => $org->id,
            'site_id' => $site->id,
            'name' => 'Radiology',
            'code' => 'RAD',
            'status' => 'active',
        ]);

        // Department operator only has scope for ICU
        UserScope::create([
            'user_id' => $this->departmentOperator->id,
            'scope_type' => 'department',
            'scope_id' => $this->icuDept->id,
            'permission_level' => 'write',
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
            'name' => 'ICU Monitored Network',
            'cidr' => '10.10.50.0/24',
            'ip_version' => 4,
            'network_hex' => '0a0a3200',
            'start_hex' => '0a0a3201',
            'end_hex' => '0a0a32fe',
            'prefix_length' => 24,
            'subnet_type' => 'assignable',
            'status' => 'active',
        ]);
    }

    /**
     * T-022: Approver tries self-approval where forbidden -> Denied.
     * Enforces the two-person rule and separation of duties.
     */
    public function test_t022_approver_tries_self_approval_is_denied(): void
    {
        $submitAction = app(SubmitRequestAction::class);
        $approveAction = app(ApproveRequestAction::class);

        // Approver submits their own change request
        $request = $submitAction->execute($this->approver, [
            'request_type' => RequestType::IP_ASSIGNMENT,
            'department_id' => $this->icuDept->id,
            'priority' => RequestPriority::NORMAL,
            'reason' => 'Submitting request as approver for critical gateway modification',
            'payload' => [
                'subnet_id' => $this->subnet->id,
                'ip_address' => '10.10.50.10',
            ],
        ]);

        $this->assertTrue($request->isPending());

        // Approver tries to self-approve their own request
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Separation of duties violation: Requester cannot approve their own change request.');

        $approveAction->execute($request, $this->approver, 'Self approval attempt');
    }

    /**
     * T-021: Department operator reads other scope -> Denied.
     * Department operator cannot view or submit requests outside their assigned scope.
     */
    public function test_t021_department_operator_access_to_other_scope_is_denied(): void
    {
        $submitAction = app(SubmitRequestAction::class);

        // 1. Operator attempts to submit request for Radiology (outside their ICU scope)
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Department operator is not authorized to submit requests for this department scope.');

        $submitAction->execute($this->departmentOperator, [
            'request_type' => RequestType::IP_ASSIGNMENT,
            'department_id' => $this->radDept->id, // Outside authorized scope!
            'priority' => RequestPriority::NORMAL,
            'reason' => 'Unauthorized request for Radiology department',
            'payload' => ['subnet_id' => $this->subnet->id],
        ]);
    }

    /**
     * T-021 Policy View Scope check: Department operator policy check on request outside scope.
     */
    public function test_t021_department_operator_cannot_view_requests_outside_scope(): void
    {
        $submitAction = app(SubmitRequestAction::class);

        // Request submitted for Radiology by general operator
        $radRequest = $submitAction->execute($this->operator, [
            'request_type' => RequestType::IP_ASSIGNMENT,
            'department_id' => $this->radDept->id,
            'priority' => RequestPriority::NORMAL,
            'reason' => 'Radiology workstation',
            'payload' => ['subnet_id' => $this->subnet->id],
        ]);

        // Policy check for departmentOperator (who only has ICU scope)
        $this->assertFalse($this->departmentOperator->can('view', $radRequest));

        // But super admin CAN view
        $this->assertTrue($this->admin->can('view', $radRequest));
    }

    /**
     * Verifies submission generates unique request number and calculates priority SLA.
     */
    public function test_request_submission_generates_sequential_number_and_sla(): void
    {
        $submitAction = app(SubmitRequestAction::class);

        $request = $submitAction->execute($this->operator, [
            'request_type' => RequestType::IP_ASSIGNMENT,
            'department_id' => $this->icuDept->id,
            'priority' => RequestPriority::EMERGENCY,
            'reason' => 'Life safety telemetry link failure - emergency IP needed',
            'payload' => [
                'subnet_id' => $this->subnet->id,
                'ip_address' => '10.10.50.15',
            ],
        ]);

        $this->assertNotNull($request->request_number);
        $this->assertStringStartsWith('REQ-', $request->request_number);
        $this->assertEquals(RequestStatus::PENDING_APPROVAL, $request->status);
        $this->assertEquals(RequestPriority::EMERGENCY, $request->priority);

        // Emergency SLA is 2 hours
        $this->assertNotNull($request->sla_due_at);
        $expectedSla = now()->addHours(2);
        $this->assertEqualsWithDelta($expectedSla->timestamp, $request->sla_due_at->timestamp, 5);

        // Audit event recorded
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'request.submitted',
            'actor_id' => $this->operator->id,
        ]);
    }

    /**
     * Verifies idempotency key prevents duplicate request creation.
     */
    public function test_idempotency_key_returns_existing_request_without_duplication(): void
    {
        $submitAction = app(SubmitRequestAction::class);
        $idempotencyKey = 'UNIQUE-KEY-12345';

        $req1 = $submitAction->execute($this->operator, [
            'request_type' => RequestType::IP_ASSIGNMENT,
            'department_id' => $this->icuDept->id,
            'priority' => RequestPriority::NORMAL,
            'reason' => 'Initial submission',
            'payload' => ['subnet_id' => $this->subnet->id],
            'idempotency_key' => $idempotencyKey,
        ]);

        $req2 = $submitAction->execute($this->operator, [
            'request_type' => RequestType::IP_ASSIGNMENT,
            'department_id' => $this->icuDept->id,
            'priority' => RequestPriority::NORMAL,
            'reason' => 'Accidental duplicate submission',
            'payload' => ['subnet_id' => $this->subnet->id],
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->assertEquals($req1->id, $req2->id);
        $this->assertEquals($req1->request_number, $req2->request_number);
        $this->assertEquals(1, IpRequest::where('idempotency_key', $idempotencyKey)->count());
    }

    /**
     * Verifies two-person approval workflow and rejection workflow.
     */
    public function test_two_person_approval_and_rejection_workflow(): void
    {
        $submitAction = app(SubmitRequestAction::class);
        $approveAction = app(ApproveRequestAction::class);
        $rejectAction = app(RejectRequestAction::class);

        // 1. Submit Request
        $request = $submitAction->execute($this->operator, [
            'request_type' => RequestType::IP_ASSIGNMENT,
            'department_id' => $this->icuDept->id,
            'priority' => RequestPriority::NORMAL,
            'reason' => 'Valid request for review',
            'payload' => ['subnet_id' => $this->subnet->id],
        ]);

        // 2. Independent approver approves (approver != operator)
        $approvedRequest = $approveAction->execute($request, $this->approver, 'Approved by independent review');
        $this->assertEquals(RequestStatus::APPROVED, $approvedRequest->status);
        $this->assertNotNull($approvedRequest->approved_at);

        $this->assertDatabaseHas('request_approvals', [
            'request_id' => $request->id,
            'approver_id' => $this->approver->id,
            'decision' => 'approved',
        ]);

        // 3. Stale decision protection: cannot re-approve or reject already decided request
        $this->expectException(DomainException::class);
        $rejectAction->execute($approvedRequest, $this->approver, 'Try to reject approved request');
    }

    /**
     * Verifies end-to-end idempotent provisioning of an approved request.
     */
    public function test_provisioning_executes_allocation_and_enforces_idempotency(): void
    {
        $submitAction = app(SubmitRequestAction::class);
        $approveAction = app(ApproveRequestAction::class);
        $provisionAction = app(ProvisionRequestAction::class);

        // 1. Submit and Approve
        $request = $submitAction->execute($this->operator, [
            'request_type' => RequestType::IP_ASSIGNMENT,
            'department_id' => $this->icuDept->id,
            'priority' => RequestPriority::NORMAL,
            'reason' => 'Provisioning target test',
            'payload' => [
                'subnet_id' => $this->subnet->id,
                'ip_address' => '10.10.50.25',
                'hostname' => 'ky-icu-test01.local',
                'classification' => IpClassification::STATIC_HOST->value,
            ],
        ]);

        $approvedRequest = $approveAction->execute($request, $this->approver, 'Approved for provisioning');

        // 2. Admin provisions request
        $provisioned = $provisionAction->execute($approvedRequest, $this->admin);

        $this->assertEquals(RequestStatus::PROVISIONED, $provisioned->status);
        $this->assertNotNull($provisioned->provisioned_at);
        $this->assertEquals($this->admin->id, $provisioned->provisioned_by);

        // Verify IP address was actually created in database with assigned status
        $this->assertDatabaseHas('ip_addresses', [
            'address_text' => '10.10.50.25',
            'allocation_status' => 'assigned',
            'hostname' => 'ky-icu-test01.local',
        ]);

        // Verify immutable change record was created
        $this->assertDatabaseHas('change_records', [
            'request_id' => $request->id,
            'actor_id' => $this->admin->id,
            'change_type' => RequestType::IP_ASSIGNMENT->value,
            'result' => 'success',
        ]);

        // 3. Idempotent replay: calling provision again safely returns without duplicating
        $replayed = $provisionAction->execute($provisioned, $this->admin);
        $this->assertEquals($provisioned->id, $replayed->id);
        $this->assertEquals(RequestStatus::PROVISIONED, $replayed->status);
    }
}
