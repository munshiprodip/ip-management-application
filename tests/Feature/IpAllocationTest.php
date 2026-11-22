<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\AllocateIpAction;
use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\AddressSpace\ReleaseIpAction;
use App\Actions\AddressSpace\ReserveIpAction;
use App\Actions\AddressSpace\TransferIpAction;
use App\Actions\DeviceInventory\CreateDeviceAction;
use App\Domain\AddressSpace\Enums\AllocationStrategy;
use App\Domain\AddressSpace\Enums\IpAllocationStatus;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\AddressSpace\Enums\IpConnectivityStatus;
use App\Domain\AddressSpace\Enums\PoolType;
use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Domain\DeviceInventory\Enums\DeviceCategory;
use App\Domain\Security\Enums\UserRole;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\DeviceType;
use App\Models\IpAddress;
use App\Models\IpAssignment;
use App\Models\IpExclusion;
use App\Models\IpPool;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IpAllocationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Vrf $globalVrf;
    private Vrf $clinicalVrf;
    private Site $site;
    private Subnet $globalSubnet;
    private Subnet $clinicalSubnet;
    private DeviceInterface $primaryInterface;
    private DeviceInterface $secondaryInterface;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::create([
            'name' => 'KYAMCH Test Org',
            'code' => 'KYAMCH-TEST',
            'slug' => 'kyamch-test',
        ]);

        $this->site = Site::create([
            'organization_id' => $org->id,
            'name' => 'Main Hospital',
            'code' => 'MAIN',
            'slug' => 'main-hospital',
        ]);

        $this->globalVrf = Vrf::create([
            'name' => 'VRF-GLOBAL',
            'code' => 'GLOBAL',
            'rd' => '65000:1',
            'is_default' => true,
        ]);

        $this->clinicalVrf = Vrf::create([
            'name' => 'VRF-CLINICAL',
            'code' => 'CLINICAL',
            'rd' => '65000:200',
            'is_default' => false,
        ]);

        $createSubnet = app(CreateSubnetAction::class);
        $this->globalSubnet = $createSubnet->execute([
            'vrf_id' => $this->globalVrf->id,
            'site_id' => $this->site->id,
            'cidr' => '10.10.10.0/24',
            'name' => 'Global Radiology Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
            'status' => SubnetStatus::ACTIVE->value,
            'allocation_enabled' => true,
        ]);

        $this->clinicalSubnet = $createSubnet->execute([
            'vrf_id' => $this->clinicalVrf->id,
            'site_id' => $this->site->id,
            'cidr' => '10.10.10.0/24',
            'name' => 'Clinical Isolated Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
            'status' => SubnetStatus::ACTIVE->value,
            'allocation_enabled' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::NETWORK_ADMIN,
            'status' => 'active',
        ]);

        $devType = DeviceType::create([
            'code' => 'TEST-DEV',
            'name' => 'Test Hardware',
            'category' => DeviceCategory::SERVER,
        ]);

        $createDevice = app(CreateDeviceAction::class);
        $device1 = $createDevice->execute([
            'code' => 'SRV-01',
            'name' => 'Server 01',
            'device_type_id' => $devType->id,
            'site_id' => $this->site->id,
            'default_interface_name' => 'eth0',
            'default_interface_mac' => '00:14:22:01:01:01',
        ]);
        $this->primaryInterface = $device1->interfaces()->first();

        $device2 = $createDevice->execute([
            'code' => 'SRV-02',
            'name' => 'Server 02',
            'device_type_id' => $devType->id,
            'site_id' => $this->site->id,
            'default_interface_name' => 'eth0',
            'default_interface_mac' => '00:14:22:01:01:02',
        ]);
        $this->secondaryInterface = $device2->interfaces()->first();
    }

    /**
     * T-006: Duplicate IP within same VRF is rejected by DB unique index.
     */
    public function test_t006_duplicate_ip_within_same_vrf_is_rejected(): void
    {
        $action = app(AllocateIpAction::class);

        // First allocation succeeds
        $action->execute([
            'subnet_id' => $this->globalSubnet->id,
            'ip_address' => '10.10.10.15',
            'interface_id' => $this->primaryInterface->id,
        ], $this->admin);

        // Second allocation of same IP in same VRF is rejected
        $this->expectException(ValidationException::class);
        $action->execute([
            'subnet_id' => $this->globalSubnet->id,
            'ip_address' => '10.10.10.15',
            'interface_id' => $this->secondaryInterface->id,
        ], $this->admin);
    }

    /**
     * T-007: Same IP in separate VRFs are both allowed.
     */
    public function test_t007_same_ip_in_separate_vrfs_both_allowed(): void
    {
        $action = app(AllocateIpAction::class);

        // Allocate 10.10.10.25 in VRF-GLOBAL
        $globalIp = $action->execute([
            'subnet_id' => $this->globalSubnet->id,
            'ip_address' => '10.10.10.25',
            'interface_id' => $this->primaryInterface->id,
        ], $this->admin);

        // Allocate 10.10.10.25 in VRF-CLINICAL
        $clinicalIp = $action->execute([
            'subnet_id' => $this->clinicalSubnet->id,
            'ip_address' => '10.10.10.25',
            'interface_id' => $this->secondaryInterface->id,
        ], $this->admin);

        $this->assertNotEquals($globalIp->id, $clinicalIp->id);
        $this->assertEquals($globalIp->address_text, $clinicalIp->address_text);
        $this->assertNotEquals($globalIp->vrf_id, $clinicalIp->vrf_id);
    }

    /**
     * T-008: Attempting to allocate an already allocated but offline IP is rejected.
     */
    public function test_t008_cannot_allocate_assigned_ip_even_if_offline(): void
    {
        $action = app(AllocateIpAction::class);

        $ip = $action->execute([
            'subnet_id' => $this->globalSubnet->id,
            'ip_address' => '10.10.10.50',
            'interface_id' => $this->primaryInterface->id,
        ], $this->admin);

        // Mark connectivity status as offline
        $ip->update([
            'connectivity_status' => IpConnectivityStatus::OBSERVED_OFFLINE,
        ]);

        $this->expectException(ValidationException::class);
        // Attempting to re-allocate offline IP must still fail
        $action->execute([
            'subnet_id' => $this->globalSubnet->id,
            'ip_address' => '10.10.10.50',
            'interface_id' => $this->secondaryInterface->id,
        ], $this->admin);
    }

    /**
     * T-009: Assigning an IP in an excluded range or DHCP dynamic pool is rejected.
     */
    public function test_t009_cannot_allocate_in_excluded_or_dhcp_pool(): void
    {
        $action = app(AllocateIpAction::class);

        // 1. Create Gateway exclusion for 10.10.10.1
        $gwHex = IpAddressVO::parse('10.10.10.1')->toHex();
        IpExclusion::create([
            'subnet_id' => $this->globalSubnet->id,
            'start_address' => '10.10.10.1',
            'end_address' => '10.10.10.1',
            'start_hex' => $gwHex,
            'end_hex' => $gwHex,
            'reason' => 'Default Gateway',
            'status' => 'active',
        ]);

        // Attempting to allocate excluded gateway IP is rejected
        $this->expectException(ValidationException::class);
        $action->execute([
            'subnet_id' => $this->globalSubnet->id,
            'ip_address' => '10.10.10.1',
        ], $this->admin);
    }

    public function test_t009_cannot_allocate_in_dhcp_dynamic_pool(): void
    {
        $action = app(AllocateIpAction::class);

        // Create DHCP dynamic pool 10.10.10.100 - 10.10.10.150
        $dhcpStart = IpAddressVO::parse('10.10.10.100')->toHex();
        $dhcpEnd = IpAddressVO::parse('10.10.10.150')->toHex();

        IpPool::create([
            'subnet_id' => $this->globalSubnet->id,
            'name' => 'DHCP Dynamic Pool',
            'start_address' => '10.10.10.100',
            'end_address' => '10.10.10.150',
            'start_hex' => $dhcpStart,
            'end_hex' => $dhcpEnd,
            'type' => PoolType::DHCP_DYNAMIC,
            'allocation_strategy' => AllocationStrategy::SEQUENTIAL,
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);
        // Attempting static allocation inside dynamic DHCP pool fails
        $action->execute([
            'subnet_id' => $this->globalSubnet->id,
            'ip_address' => '10.10.10.120',
        ], $this->admin);
    }

    /**
     * T-011: Rollback on assignment error leaves no phantom ownership.
     */
    public function test_t011_rollback_on_assignment_error(): void
    {
        $action = app(AllocateIpAction::class);

        try {
            $action->execute([
                'subnet_id' => $this->globalSubnet->id,
                'ip_address' => '10.10.10.77',
                'interface_id' => 999999, // Non-existent interface ID forces exception inside transaction
            ], $this->admin);
            $this->fail('Expected exception was not thrown.');
        } catch (Exception $e) {
            // Assert that no assigned record remains
            $ip = IpAddress::where('vrf_id', $this->globalVrf->id)->where('address_text', '10.10.10.77')->first();
            $this->assertTrue($ip === null || $ip->allocation_status !== IpAllocationStatus::ASSIGNED);
            $this->assertEquals(0, IpAssignment::whereHas('ipAddress', fn ($q) => $q->where('address_text', '10.10.10.77'))->count());
        }
    }

    /**
     * T-012: Release IP preserves history and applies quarantine.
     */
    public function test_t012_release_ip_preserves_history_and_applies_quarantine(): void
    {
        $allocateAction = app(AllocateIpAction::class);
        $releaseAction = app(ReleaseIpAction::class);

        $ip = $allocateAction->execute([
            'subnet_id' => $this->globalSubnet->id,
            'ip_address' => '10.10.10.88',
            'interface_id' => $this->primaryInterface->id,
        ], $this->admin);

        $assignmentId = $ip->current_assignment_id;
        $this->assertNotNull($assignmentId);

        // Release with 3-day quarantine
        $releasedIp = $releaseAction->execute($ip, 'Device decommissioned', 3, $this->admin);

        // 1. Current assignment cleared
        $this->assertNull($releasedIp->current_assignment_id);
        $this->assertEquals(IpAllocationStatus::QUARANTINED, $releasedIp->allocation_status);
        $this->assertNotNull($releasedIp->quarantined_until);
        $this->assertEquals('Device decommissioned', $releasedIp->quarantine_reason);

        // 2. Historical assignment row remains intact with released_at
        $hist = IpAssignment::find($assignmentId);
        $this->assertNotNull($hist);
        $this->assertNotNull($hist->released_at);
        $this->assertEquals('Device decommissioned', $hist->release_reason);

        // 3. Quarantined IP cannot be immediately re-allocated
        $this->expectException(ValidationException::class);
        $allocateAction->execute([
            'subnet_id' => $this->globalSubnet->id,
            'ip_address' => '10.10.10.88',
        ], $this->admin);
    }

    /**
     * T-013: Transfer IP maintains single current owner; historic owner closed cleanly.
     */
    public function test_t013_transfer_ip_maintains_single_current_owner(): void
    {
        $allocateAction = app(AllocateIpAction::class);
        $transferAction = app(TransferIpAction::class);

        // 1. Allocate to Server 01 eth0
        $ip = $allocateAction->execute([
            'subnet_id' => $this->globalSubnet->id,
            'ip_address' => '10.10.10.90',
            'interface_id' => $this->primaryInterface->id,
        ], $this->admin);

        $oldAssignmentId = $ip->current_assignment_id;

        // 2. Transfer to Server 02 eth0
        $transferred = $transferAction->execute(
            $ip,
            $this->secondaryInterface->id,
            'Emergency hardware migration',
            $this->admin
        );

        // Status remains assigned throughout
        $this->assertEquals(IpAllocationStatus::ASSIGNED, $transferred->allocation_status);
        $this->assertNotEquals($oldAssignmentId, $transferred->current_assignment_id);

        // Old assignment is closed
        $oldHist = IpAssignment::find($oldAssignmentId);
        $this->assertNotNull($oldHist->released_at);
        $this->assertStringContainsString('Transferred to interface', $oldHist->release_reason);

        // New assignment is active
        $newHist = IpAssignment::find($transferred->current_assignment_id);
        $this->assertNull($newHist->released_at);
        $this->assertEquals($this->secondaryInterface->id, $newHist->interface_id);

        // Exactly one active assignment exists
        $activeCount = $transferred->assignments()->whereNull('released_at')->count();
        $this->assertEquals(1, $activeCount);
    }

    /**
     * T-024: Parent utilization with children: No double counting.
     */
    public function test_t024_parent_utilization_with_children_no_double_counting(): void
    {
        $createSubnet = app(CreateSubnetAction::class);
        $allocateAction = app(AllocateIpAction::class);

        // Parent container: 10.20.0.0/23 (total 512 addresses = two /24s)
        $parentContainer = $createSubnet->execute([
            'vrf_id' => $this->globalVrf->id,
            'cidr' => '10.20.0.0/23',
            'name' => 'Campus Data Block',
            'subnet_type' => SubnetType::AGGREGATE->value,
            'status' => SubnetStatus::ACTIVE->value,
        ], $this->admin);

        // Child A: 10.20.0.0/24 (256 addresses)
        $childA = $createSubnet->execute([
            'vrf_id' => $this->globalVrf->id,
            'parent_id' => $parentContainer->id,
            'cidr' => '10.20.0.0/24',
            'name' => 'Data Subnet A',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
            'status' => SubnetStatus::ACTIVE->value,
        ], $this->admin);

        // Allocate 2 IPs in Child A
        $allocateAction->execute([
            'subnet_id' => $childA->id,
            'ip_address' => '10.20.0.10',
        ], $this->admin);

        $allocateAction->execute([
            'subnet_id' => $childA->id,
            'ip_address' => '10.20.0.11',
        ], $this->admin);

        // 1. Child A allocated count is exactly 2
        $this->assertEquals(2, $childA->allocatedCount());

        // 2. Parent container aggregate capacity taken by child subnets is 256 (not double counted!)
        $this->assertEquals(256, $parentContainer->allocatedCount(false));

        // 3. Parent container recursive active IP count is 2 (matching child A)
        $this->assertEquals(2, $parentContainer->allocatedCount(true));
    }
}

