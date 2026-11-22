<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Actions\AddressSpace\CreateVrfAction;
use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\AddressSpace\ValueObjects\IpPrefix;
use App\Models\Organization;
use App\Models\Site;
use App\Models\Subnet;
use App\Models\User;
use App\Models\Vrf;
use App\Services\AddressSpace\SubnetHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubnetHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Vrf $globalVrf;
    private Vrf $clinicalVrf;
    private Site $site;
    private SubnetHierarchyService $hierarchyService;

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
            'name' => 'Main Campus',
            'code' => 'MAIN',
            'slug' => 'main-campus',
        ]);

        $this->globalVrf = Vrf::create([
            'name' => 'VRF-GLOBAL',
            'code' => 'GLOBAL',
            'rd' => '65000:1',
            'description' => 'Default Global VRF',
            'is_default' => true,
        ]);

        $this->clinicalVrf = Vrf::create([
            'name' => 'VRF-CLINICAL',
            'code' => 'CLINICAL',
            'rd' => '65000:200',
            'description' => 'Clinical Medical Devices VRF',
            'is_default' => false,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'network_admin',
            'status' => 'active',
        ]);

        $this->hierarchyService = app(SubnetHierarchyService::class);
    }

    /**
     * T-002: Create valid child prefix within parent.
     */
    public function test_t002_creates_valid_child_prefix_within_parent(): void
    {
        $createAction = app(CreateSubnetAction::class);

        // 1. Create parent container: 10.10.0.0/16
        $parent = $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'site_id' => $this->site->id,
            'cidr' => '10.10.0.0/16',
            'name' => 'Campus Aggregate',
            'subnet_type' => SubnetType::AGGREGATE->value,
            'status' => SubnetStatus::ACTIVE->value,
        ]);

        // 2. Create child subnet: 10.10.1.0/24
        $child = $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'site_id' => $this->site->id,
            'parent_id' => $parent->id,
            'cidr' => '10.10.1.0/24',
            'name' => 'Radiology Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
            'status' => SubnetStatus::ACTIVE->value,
        ]);

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertEquals(1, $parent->children()->count());
        $this->assertEquals($child->id, $parent->children()->first()->id);
    }

    /**
     * T-003: Child outside parent is strictly rejected.
     */
    public function test_t003_child_outside_parent_is_rejected(): void
    {
        $createAction = app(CreateSubnetAction::class);

        $parent = $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'cidr' => '10.10.0.0/16',
            'name' => 'Campus Aggregate',
            'subnet_type' => SubnetType::AGGREGATE->value,
        ]);

        $this->expectException(ValidationException::class);

        // 10.20.1.0/24 is outside 10.10.0.0/16
        $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'parent_id' => $parent->id,
            'cidr' => '10.20.1.0/24',
            'name' => 'Illegal Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);
    }

    /**
     * T-004: Overlapping siblings in same VRF and family are strictly rejected.
     */
    public function test_t004_overlapping_siblings_in_same_vrf_are_rejected(): void
    {
        $createAction = app(CreateSubnetAction::class);

        // Create first subnet: 10.10.1.0/24
        $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'cidr' => '10.10.1.0/24',
            'name' => 'Existing Block',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);

        $this->expectException(ValidationException::class);

        // Attempt to create overlapping subnet 10.10.1.128/25 in same VRF without hierarchy
        $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'cidr' => '10.10.1.128/25',
            'name' => 'Conflicting Sibling',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);
    }

    public function test_same_cidr_allowed_in_different_vrfs(): void
    {
        $createAction = app(CreateSubnetAction::class);

        // Subnet in VRF-GLOBAL
        $sub1 = $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'cidr' => '192.168.100.0/24',
            'name' => 'Global Lab',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);

        // Subnet in VRF-CLINICAL with same CIDR
        $sub2 = $createAction->execute([
            'vrf_id' => $this->clinicalVrf->id,
            'cidr' => '192.168.100.0/24',
            'name' => 'Clinical Isolated Lab',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);

        $this->assertNotEquals($sub1->id, $sub2->id);
        $this->assertEquals($sub1->cidr, $sub2->cidr);
        $this->assertNotEquals($sub1->vrf_id, $sub2->vrf_id);
    }

    /**
     * T-005: Ancestry cycle detection.
     */
    public function test_t005_ancestry_cycle_detection(): void
    {
        $createAction = app(CreateSubnetAction::class);

        $parent = $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'cidr' => '10.10.0.0/16',
            'name' => 'Parent Container',
            'subnet_type' => SubnetType::AGGREGATE->value,
        ]);

        $child = $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'parent_id' => $parent->id,
            'cidr' => '10.10.1.0/24',
            'name' => 'Child Subnet',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);

        // Attempting to make parent point to child as its parent must fail
        $this->expectException(ValidationException::class);
        $this->hierarchyService->validateNoCycle($parent, $child->id);
    }

    public function test_cannot_be_its_own_parent(): void
    {
        $createAction = app(CreateSubnetAction::class);

        $subnet = $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'cidr' => '10.10.0.0/16',
            'name' => 'Subnet',
            'subnet_type' => SubnetType::AGGREGATE->value,
        ]);

        $this->expectException(ValidationException::class);
        $this->hierarchyService->validateNoCycle($subnet, $subnet->id);
    }

    public function test_find_free_child_prefix_suggests_next_available_block(): void
    {
        $createAction = app(CreateSubnetAction::class);

        // Parent /23 consists of two /24s: 10.10.0.0/24 and 10.10.1.0/24
        $parent = $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'cidr' => '10.10.0.0/23',
            'name' => 'Small Container',
            'subnet_type' => SubnetType::AGGREGATE->value,
        ]);

        // Initially, first /24 is free
        $free1 = $this->hierarchyService->findFreeChildPrefix($parent, 24);
        $this->assertNotNull($free1);
        $this->assertEquals('10.10.0.0/24', $free1->cidr());

        // Allocate first /24
        $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'parent_id' => $parent->id,
            'cidr' => '10.10.0.0/24',
            'name' => 'First Child',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);

        // Next free /24 should now be 10.10.1.0/24
        $free2 = $this->hierarchyService->findFreeChildPrefix($parent, 24);
        $this->assertNotNull($free2);
        $this->assertEquals('10.10.1.0/24', $free2->cidr());

        // Allocate second /24
        $createAction->execute([
            'vrf_id' => $this->globalVrf->id,
            'parent_id' => $parent->id,
            'cidr' => '10.10.1.0/24',
            'name' => 'Second Child',
            'subnet_type' => SubnetType::ASSIGNABLE->value,
        ]);

        // Container is full, no more /24s available
        $free3 = $this->hierarchyService->findFreeChildPrefix($parent, 24);
        $this->assertNull($free3);
    }
}
