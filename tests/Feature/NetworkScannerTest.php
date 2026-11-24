<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AddressSpace\CreateSubnetAction;
use App\Domain\AddressSpace\Enums\SubnetType;
use App\Livewire\Scanner\Index as ScannerIndex;
use App\Models\IpAddress;
use App\Models\ScanJob;
use App\Models\ScanResult;
use App\Models\Subnet;
use App\Models\User;
use App\Services\Network\ArpLookupService;
use App\Services\Network\NetworkScanService;
use App\Services\Network\PingResult;
use App\Services\Network\PingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NetworkScannerTest extends TestCase
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

        $createSubnet = app(CreateSubnetAction::class);
        $this->subnet = $createSubnet->execute([
            'cidr' => '10.99.1.0/28',
            'name' => 'Biomedical ICU Segment',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'allocation_enabled' => true,
        ]);
    }

    public function test_guest_cannot_access_network_scanner(): void
    {
        $response = $this->get(route('scanner.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_scanner_and_select_subnet(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('scanner.index', ['subnet' => $this->subnet->id]));
        $response->assertStatus(200);
        $response->assertSee('Network IP Scanner & Device Discovery');
        $response->assertSee('Biomedical ICU Segment');
    }

    public function test_cannot_scan_non_assignable_aggregate_subnet(): void
    {
        $createSubnet = app(CreateSubnetAction::class);
        $aggregate = $createSubnet->execute([
            'cidr' => '10.99.0.0/16',
            'name' => 'Core Aggregation Block',
            'subnet_type' => SubnetType::AGGREGATE,
            'allocation_enabled' => false,
        ]);

        $scanner = app(NetworkScanService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not marked as active and assignable');
        $scanner->createScanJob($aggregate, $this->admin);
    }

    public function test_cannot_scan_large_subnet_exceeding_safety_limit(): void
    {
        $createSubnet = app(CreateSubnetAction::class);
        $largeSubnet = $createSubnet->execute([
            'cidr' => '10.80.0.0/22', // 1022 usable hosts > 512 max limit
            'name' => 'Campus Large Segment',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'allocation_enabled' => true,
        ]);

        $scanner = app(NetworkScanService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds maximum scan limit (512 hosts)');
        $scanner->createScanJob($largeSubnet, $this->admin);
    }

    public function test_cannot_start_duplicate_concurrent_scan_on_same_subnet(): void
    {
        $scanner = app(NetworkScanService::class);
        $job1 = $scanner->createScanJob($this->subnet, $this->admin);
        $this->assertEquals('pending', $job1->status);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already running or queued for subnet');
        $scanner->createScanJob($this->subnet, $this->admin);
    }

    public function test_scan_execution_discovers_hosts_and_identifies_unregistered_devices(): void
    {
        // 1. Authoritative registered IP in IPAM: 10.99.1.1 is Assigned to Patient Monitor
        $allocateAction = app(\App\Actions\AddressSpace\AllocateIpAction::class);
        $allocateAction->execute([
            'subnet_id' => $this->subnet->id,
            'ip_address' => '10.99.1.1',
            'hostname' => 'icu-mon-01.kyamch.local',
            'assigned_to' => 'ICU Bed 1 Monitor',
        ], $this->admin);

        // 2. Mock PingService: 10.99.1.1 and 10.99.1.2 respond; 10.99.1.3 through 10.99.1.14 do not
        $mockPing = $this->createMock(PingService::class);
        $mockPing->method('ping')->willReturnCallback(function (string $ip) {
            if ($ip === '10.99.1.1') {
                return PingResult::reachable('4.2ms');
            }
            if ($ip === '10.99.1.2') {
                return PingResult::reachable('8.5ms');
            }
            return PingResult::noReply('Request timed out');
        });

        // 3. Mock ArpLookupService: returns MAC for 10.99.1.2
        $mockArp = $this->createMock(ArpLookupService::class);
        $mockArp->method('lookupMac')->willReturnCallback(function (string $ip) {
            return $ip === '10.99.1.2' ? '00:1A:2B:3C:4D:5E' : null;
        });

        $scanner = new NetworkScanService($mockPing, $mockArp);

        // 4. Create and execute scan
        $job = $scanner->createScanJob($this->subnet, $this->admin);
        $this->assertEquals(14, $job->total_targets); // /28 has 14 usable hosts

        $scanner->executeScan($job);

        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertEquals(14, $job->scanned_count);
        $this->assertEquals(2, $job->responding_count);
        $this->assertEquals(1, $job->unregistered_count); // 10.99.1.2 is unregistered!

        // 5. Verify individual scan results
        $res1 = ScanResult::where('scan_job_id', $job->id)->where('ip_address', '10.99.1.1')->firstOrFail();
        $this->assertEquals('responding', $res1->status);
        $this->assertEquals('assigned', $res1->ipam_status);
        $this->assertFalse($res1->is_unregistered);
        $this->assertEquals('ICU Bed 1 Monitor', $res1->ipam_assigned_to);

        $res2 = ScanResult::where('scan_job_id', $job->id)->where('ip_address', '10.99.1.2')->firstOrFail();
        $this->assertEquals('responding', $res2->status);
        $this->assertEquals('unassigned', $res2->ipam_status);
        $this->assertTrue($res2->is_unregistered); // Rogue / newly plugged in device!
        $this->assertEquals('00:1A:2B:3C:4D:5E', $res2->discovered_mac);

        $res3 = ScanResult::where('scan_job_id', $job->id)->where('ip_address', '10.99.1.3')->firstOrFail();
        $this->assertEquals('no_reply', $res3->status);
        $this->assertFalse($res3->is_unregistered);
    }

    public function test_stopping_scan_marks_job_cancelled(): void
    {
        $scanner = app(NetworkScanService::class);
        $job = $scanner->createScanJob($this->subnet, $this->admin);
        $this->assertTrue($job->isRunning());

        $scanner->cancelScan($job, $this->admin);

        $job->refresh();
        $this->assertEquals('cancelled', $job->status);
        $this->assertTrue($job->isCancelled());
    }

    public function test_livewire_scanner_start_scan_and_allocate_unregistered_host(): void
    {
        $this->actingAs($this->admin);

        $mockPing = $this->createMock(PingService::class);
        $mockPing->method('ping')->willReturn(PingResult::reachable('5.0ms'));
        $this->app->instance(PingService::class, $mockPing);

        $comp = Livewire::test(ScannerIndex::class, ['subnet' => $this->subnet->id])
            ->assertStatus(200)
            ->call('startScan')
            ->assertDispatched('notify');

        $job = ScanJob::where('subnet_id', $this->subnet->id)->latest('id')->firstOrFail();
        $this->assertNotNull($job);

        // Find the scanned result for 10.99.1.5
        $result = ScanResult::where('scan_job_id', $job->id)->where('ip_address', '10.99.1.5')->firstOrFail();
        $this->assertEquals('unassigned', $result->ipam_status);
        $this->assertTrue($result->is_unregistered);

        $comp->call('openAllocateModal', '10.99.1.5', 'telemetry-icu-05.local', 'AA:BB:CC:DD:EE:05')
            ->assertSet('showAllocateModal', true)
            ->set('alloc_assigned_to', 'Dr. Smith Station ICU')
            ->call('saveAllocation')
            ->assertDispatched('notify')
            ->assertSet('showAllocateModal', false);

        // Verify IP address was registered in authoritative IPAM table
        $this->assertDatabaseHas('ip_addresses', [
            'subnet_id' => $this->subnet->id,
            'address_text' => '10.99.1.5',
            'assigned_to' => 'Dr. Smith Station ICU',
        ]);

        // Verify ScanResult was updated to registered
        $result->refresh();
        $this->assertEquals('assigned', $result->ipam_status);
        $this->assertFalse($result->is_unregistered);
    }

    public function test_register_modal_autofills_hostname_and_mac_from_scan_result_id(): void
    {
        $this->actingAs($this->admin);

        $job = ScanJob::create([
            'subnet_id' => $this->subnet->id,
            'user_id' => $this->admin->id,
            'status' => 'completed',
            'total_targets' => 14,
            'scanned_count' => 14,
            'responding_count' => 1,
            'unregistered_count' => 1,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        $scanResult = ScanResult::create([
            'scan_job_id' => $job->id,
            'ip_address' => '10.99.1.7',
            'ip_hex' => '0A630107',
            'status' => 'responding',
            'response_time_ms' => 1.8,
            'discovered_hostname' => 'radiology-workstation-07.kyamch.local',
            'discovered_mac' => '00:14:22:01:23:45',
            'ipam_status' => 'unassigned',
            'is_unregistered' => true,
            'checked_at' => now(),
        ]);

        // Call openAllocateModal passing the ScanResult ID (as in the Blade table row)
        $comp = Livewire::test(ScannerIndex::class, ['subnet' => $this->subnet->id])
            ->set('currentJobId', $job->id)
            ->call('openAllocateModal', $scanResult->id)
            ->assertSet('showAllocateModal', true)
            ->assertSet('alloc_ip', '10.99.1.7')
            ->assertSet('alloc_hostname', 'radiology-workstation-07.kyamch.local')
            ->assertSet('alloc_mac_address', '00:14:22:01:23:45')
            ->assertSet('alloc_assigned_to', 'radiology-workstation-07.kyamch.local')
            ->assertSee('Auto-filled discovered network attributes')
            ->call('saveAllocation')
            ->assertDispatched('notify')
            ->assertSet('showAllocateModal', false);

        // Verify IP address was created with the auto-filled hostname and MAC
        $this->assertDatabaseHas('ip_addresses', [
            'subnet_id' => $this->subnet->id,
            'address_text' => '10.99.1.7',
            'hostname' => 'radiology-workstation-07.kyamch.local',
            'mac_address' => '00:14:22:01:23:45',
            'assigned_to' => 'radiology-workstation-07.kyamch.local',
        ]);

        $scanResult->refresh();
        $this->assertEquals('assigned', $scanResult->ipam_status);
        $this->assertFalse($scanResult->is_unregistered);
    }

    public function test_start_scan_is_applicable_for_selected_subnet(): void
    {
        $this->actingAs($this->admin);

        $createSubnet = app(CreateSubnetAction::class);
        $secondSubnet = $createSubnet->execute([
            'cidr' => '10.99.2.0/28',
            'name' => 'PACU Recovery Segment',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'allocation_enabled' => true,
        ]);

        $mockPing = $this->createMock(PingService::class);
        $mockPing->method('ping')->willReturn(PingResult::reachable('6.2ms'));
        $this->app->instance(PingService::class, $mockPing);

        // Mount on initial subnet, verify quick pills are removed and only the select option is used
        $comp = Livewire::test(ScannerIndex::class, ['subnet' => $this->subnet->id])
            ->assertSet('selectedSubnetId', $this->subnet->id)
            ->assertDontSee('Select Subnet:') // Quick pills removed
            ->assertSee($this->subnet->cidr)
            ->set('selectedSubnetId', $secondSubnet->id)
            ->assertSet('selectedSubnetId', $secondSubnet->id)
            ->assertSee('Start Scan (' . $secondSubnet->cidr . ')')
            ->call('startScan', $secondSubnet->id)
            ->assertDispatched('notify');

        // Verify calling selectSubnet and startScan without arguments scans the newly selected subnet
        $createSubnet = app(CreateSubnetAction::class);
        $thirdSubnet = $createSubnet->execute([
            'cidr' => '10.99.3.0/28',
            'name' => 'Surgery Segment',
            'subnet_type' => SubnetType::ASSIGNABLE,
            'allocation_enabled' => true,
        ]);

        $comp2 = Livewire::test(ScannerIndex::class, ['subnet' => $this->subnet->id])
            ->assertSet('selectedSubnetId', $this->subnet->id)
            ->call('selectSubnet', $thirdSubnet->id)
            ->assertSet('selectedSubnetId', $thirdSubnet->id)
            ->call('startScan')
            ->assertDispatched('notify');

        $job3 = ScanJob::where('subnet_id', $thirdSubnet->id)->latest('id')->firstOrFail();
        $this->assertEquals($thirdSubnet->id, $job3->subnet_id);

        // Confirm scan job was created specifically for secondSubnet
        $job = ScanJob::where('subnet_id', $secondSubnet->id)->latest('id')->firstOrFail();
        $this->assertEquals($secondSubnet->id, $job->subnet_id);
        $this->assertEquals(14, $job->total_targets);

        // Confirm created ScanResults are in 10.99.2.x range, not 10.99.1.x
        $firstResult = ScanResult::where('scan_job_id', $job->id)->firstOrFail();
        $this->assertStringStartsWith('10.99.2.', $firstResult->ip_address);
    }

    public function test_arp_lookup_service_handles_parsing_and_nulls(): void
    {
        $arp = new ArpLookupService();

        // 1. Invalid IPs return null
        $this->assertNull($arp->lookupMac('invalid-ip'));
        $this->assertNull($arp->lookupMac('2001:db8::1')); // IPv6 returns null

        // 2. Windows ARP table output parsing
        $windowsOutput = "
Interface: 192.168.1.100 --- 0x10
  Internet Address      Physical Address      Type
  10.99.1.2             00-1a-2b-3c-4d-5e     dynamic
  10.99.1.255           ff-ff-ff-ff-ff-ff     static
";
        $parsedMac = $arp->parseMacFromOutput($windowsOutput, '10.99.1.2');
        $this->assertEquals('00:1A:2B:3C:4D:5E', $parsedMac);

        // 3. Linux ARP / IP neigh output parsing
        $linuxOutput = "10.99.1.2 dev eth0 lladdr a0:b1:c2:d3:e4:f5 REACHABLE";
        $parsedLinuxMac = $arp->parseMacFromOutput($linuxOutput, '10.99.1.2');
        $this->assertEquals('A0:B1:C2:D3:E4:F5', $parsedLinuxMac);

        // 4. Broadcast MACs return null
        $broadcastOutput = "10.99.1.255 ff:ff:ff:ff:ff:ff";
        $this->assertNull($arp->parseMacFromOutput($broadcastOutput, '10.99.1.255'));
    }
}
