<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\AddressSpace\ReleaseIpAction;
use App\Actions\AddressSpace\ReserveIpAction;
use App\Actions\AddressSpace\UnquarantineIpAction;
use App\Domain\AddressSpace\Enums\HostType;
use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Models\IpAddress;
use App\Models\IpAssignment;
use App\Models\IpExclusion;
use App\Models\IpPool;
use App\Models\Subnet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IpAllocationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Subnet $subnet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'network_admin',
            'status' => 'active',
        ]);

        $subnetAction = app(CreateSubnetAction::class);
        $this->subnet = $subnetAction->execute([
            'name' => 'Campus Host Subnet',
            'cidr' => '10.10.1.0/24',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'allocation_enabled' => true,
        ]);
    }

    public function test_t006_auto_first_free_candidate_allocation(): void
    {
        $action = app(AllocateIpAction::class);

        $allocated = $action->execute([
            'subnet_id' => $this->subnet->id,
            'hostname' => 'host-01.kyamch.local',
            'assigned_to' => 'Server 01',
        ], $this->admin);

        $this->assertEquals('10.10.1.1', $allocated->address_text);
        $this->assertEquals(IpAllocationStatus::ASSIGNED, $allocated->allocation_status);
        $this->assertEquals('host-01.kyamch.local', $allocated->hostname);

        $this->assertDatabaseHas('ip_assignments', [
            'ip_address_id' => $allocated->id,
            'hostname' => 'host-01.kyamch.local',
        ]);
    }

    public function test_t007_manual_static_address_allocation(): void
    {
        $action = app(AllocateIpAction::class);

        $allocated = $action->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.50',
            'hostname' => 'db-01.kyamch.local',
        ], $this->admin);

        $this->assertEquals('10.10.1.50', $allocated->address_text);
        $this->assertEquals(IpAllocationStatus::ASSIGNED, $allocated->allocation_status);
    }

    public function test_t008_cannot_allocate_already_assigned_address(): void
    {
        $action = app(AllocateIpAction::class);

        $action->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.25',
        ], $this->admin);

        $this->expectException(ValidationException::class);
        $action->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.25',
        ], $this->admin);
    }

    public function test_t009_cannot_allocate_excluded_or_dynamic_pool_address(): void
    {
        IpExclusion::create([
            'subnet_id' => $this->subnet->id,
            'start_address' => '10.10.1.1',
            'end_address' => '10.10.1.10',
            'start_hex' => \App\Domain\AddressSpace\ValueObjects\IpAddress::parse('10.10.1.1')->toHex(),
            'end_hex' => \App\Domain\AddressSpace\ValueObjects\IpAddress::parse('10.10.1.10')->toHex(),
            'reason' => 'Gateways excluded',
            'status' => 'active',
        ]);

        $action = app(AllocateIpAction::class);

        $this->expectException(ValidationException::class);
        $action->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.5',
        ], $this->admin);
    }

    public function test_t011_allocation_records_audit_trail(): void
    {
        $action = app(AllocateIpAction::class);

        $allocated = $action->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.80',
            'hostname' => 'audit-test.kyamch.local',
        ], $this->admin);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'ip.assigned',
            'subject_id' => $allocated->id,
        ]);
    }

    public function test_t012_quarantine_enforcement_and_release(): void
    {
        $allocateAction = app(AllocateIpAction::class);
        $releaseAction = app(ReleaseIpAction::class);

        $ip = $allocateAction->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.99',
        ], $this->admin);

        // Release with 3 days quarantine
        $released = $releaseAction->execute($ip, 'Server decommissioned', 3, $this->admin);

        $this->assertEquals(IpAllocationStatus::QUARANTINED, $released->allocation_status);
        $this->assertTrue($released->isQuarantined());
        $this->assertFalse($released->isAvailable());

        // Attempting to allocate during quarantine must throw
        $this->expectException(ValidationException::class);
        $allocateAction->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.99',
        ], $this->admin);
    }

    public function test_t013_idempotent_re_allocation_with_same_key(): void
    {
        $action = app(AllocateIpAction::class);
        $key = 'req-key-12345';

        $first = $action->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.120',
            'idempotency_key' => $key,
        ], $this->admin);

        $second = $action->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.120',
            'idempotency_key' => $key,
        ], $this->admin);

        $this->assertEquals($first->id, $second->id);
    }

    public function test_reserve_ip_address(): void
    {
        $reserveAction = app(ReserveIpAction::class);
        $allocateAction = app(AllocateIpAction::class);

        $reservation = $reserveAction->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.200',
            'reason' => 'Reserved for upcoming telemetry node',
        ], $this->admin);

        $this->assertDatabaseHas('ip_reservations', [
            'id' => $reservation->id,
            'purpose' => 'Reserved for upcoming telemetry node',
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'address_text' => '10.10.1.200',
            'allocation_status' => IpAllocationStatus::RESERVED->value,
        ]);

        // Attempting to allocate reserved IP must fail
        $this->expectException(ValidationException::class);
        $allocateAction->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.200',
        ], $this->admin);
    }

    public function test_t014_unquarantine_returns_address_to_unassigned(): void
    {
        $allocateAction = app(AllocateIpAction::class);
        $releaseAction = app(ReleaseIpAction::class);
        $unquarantineAction = app(UnquarantineIpAction::class);

        $ip = $allocateAction->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.95',
            'host_type' => HostType::MEDICAL,
            'hostname' => 'infusion-01.kyamch.local',
            'assigned_to' => 'ICU Infusion Pump 1',
        ], $this->admin);

        // Put in quarantine
        $quarantined = $releaseAction->execute($ip, 'Infusion pump maintenance', 7, $this->admin);
        $this->assertEquals(IpAllocationStatus::QUARANTINED, $quarantined->allocation_status);
        $this->assertFalse($quarantined->isAvailable());

        // Lift quarantine manually (Make Unassigned)
        $unquarantined = $unquarantineAction->execute($quarantined, 'Operator cleared quarantine', $this->admin);

        $this->assertEquals(IpAllocationStatus::UNASSIGNED, $unquarantined->allocation_status);
        $this->assertNull($unquarantined->quarantined_until);
        $this->assertNull($unquarantined->assigned_to);
        $this->assertTrue($unquarantined->isAvailable());

        // Should now be immediately re-allocatable
        $reallocated = $allocateAction->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.95',
            'host_type' => HostType::WORKSTATION,
            'assigned_to' => 'Nursing Station 2',
        ], $this->admin);

        $this->assertEquals('10.10.1.95', $reallocated->address_text);
        $this->assertEquals(IpAllocationStatus::ASSIGNED, $reallocated->allocation_status);
        $this->assertEquals(HostType::WORKSTATION, $reallocated->host_type);
    }

    public function test_t015_allocation_with_host_type(): void
    {
        $action = app(AllocateIpAction::class);

        $allocated = $action->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.10.1.150',
            'host_type' => HostType::SERVER,
            'hostname' => 'his-db.kyamch.local',
            'assigned_to' => 'HIS Database Primary Node',
        ], $this->admin);

        $this->assertEquals(HostType::SERVER, $allocated->host_type);
        $this->assertDatabaseHas('ip_addresses', [
            'address_text' => '10.10.1.150',
            'host_type' => 'server',
        ]);
        $this->assertDatabaseHas('ip_assignments', [
            'ip_address_id' => $allocated->id,
            'host_type' => 'server',
        ]);
    }
}
