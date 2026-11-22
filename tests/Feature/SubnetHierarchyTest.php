<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Domain\AddressSpace\Enums\SubnetStatus;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Domain\AddressSpace\ValueObjects\IpPrefix;
use App\Models\Subnet;
use App\Models\User;
use App\Services\AddressSpace\SubnetHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubnetHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private SubnetHierarchyService $hierarchyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'network_admin',
            'status' => 'active',
        ]);

        $this->hierarchyService = app(SubnetHierarchyService::class);
    }

    public function test_t001_canonical_zero_host_bits(): void
    {
        $action = app(CreateSubnetAction::class);

        // Host bit 192.168.1.135/24 should normalize to 192.168.1.0/24
        $subnet = $action->execute([
            'cidr' => '192.168.1.135/24',
            'name' => 'Non-canonical test',
        ]);

        $this->assertEquals('192.168.1.0/24', $subnet->cidr);
        $this->assertEquals(24, $subnet->prefix_length);
    }

    public function test_t002_t003_parent_containment(): void
    {
        $action = app(CreateSubnetAction::class);

        $parent = $action->execute([
            'cidr' => '10.0.0.0/16',
            'name' => 'Parent Subnet',
            'subnet_type' => SubnetType::AGGREGATE,
        ]);

        // Valid child within parent
        $child = $action->execute([
            'parent_id' => $parent->id,
            'cidr' => '10.0.1.0/24',
            'name' => 'Valid Child',
        ]);

        $this->assertEquals($parent->id, $child->parent_id);

        // Invalid child outside parent
        $this->expectException(ValidationException::class);
        $action->execute([
            'parent_id' => $parent->id,
            'cidr' => '192.168.1.0/24',
            'name' => 'Outside Child',
        ]);
    }

    public function test_t004_non_overlapping_siblings(): void
    {
        $action = app(CreateSubnetAction::class);

        $action->execute([
            'cidr' => '10.10.1.0/24',
            'name' => 'First Subnet',
        ]);

        // Exact duplicate
        $this->expectException(ValidationException::class);
        $action->execute([
            'cidr' => '10.10.1.0/24',
            'name' => 'Duplicate Subnet',
        ]);
    }

    public function test_t005_ancestry_cycle_detection(): void
    {
        $action = app(CreateSubnetAction::class);

        $root = $action->execute([
            'cidr' => '10.0.0.0/8',
            'name' => 'Root',
            'subnet_type' => SubnetType::AGGREGATE,
        ]);

        $child = $action->execute([
            'parent_id' => $root->id,
            'cidr' => '10.1.0.0/16',
            'name' => 'Child',
            'subnet_type' => SubnetType::AGGREGATE,
        ]);

        // Attempt to make root a child of its own child
        $this->expectException(ValidationException::class);
        $this->hierarchyService->validateNoCycle($root, $child->id);
    }

    public function test_find_free_child_prefix_suggests_next_available_block(): void
    {
        $action = app(CreateSubnetAction::class);

        $parent = $action->execute([
            'cidr' => '10.20.0.0/16',
            'name' => 'Parent Container',
            'subnet_type' => SubnetType::AGGREGATE,
        ]);

        // Allocate 10.20.0.0/24
        $action->execute([
            'parent_id' => $parent->id,
            'cidr' => '10.20.0.0/24',
            'name' => 'First Block',
        ]);

        $next = $this->hierarchyService->findFreeChildPrefix($parent, 24);

        $this->assertNotNull($next);
        $this->assertEquals('10.20.1.0/24', $next->cidr());
    }
}
