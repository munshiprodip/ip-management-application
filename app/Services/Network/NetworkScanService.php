<?php

declare(strict_types=1);

namespace App\Services\Network;

use App\Domain\AddressSpace\ValueObjects\IpAddress as IpAddressVO;
use App\Models\AuditEvent;
use App\Models\IpAddress;
use App\Models\ScanJob;
use App\Models\ScanResult;
use App\Models\Subnet;
use App\Models\User;
use Symfony\Component\Process\Process;

class NetworkScanService
{
    public function __construct(
        protected PingService $pingService,
        protected ArpLookupService $arpService
    ) {}

    /**
     * Initialize and validate a new subnet scan job.
     *
     * @throws \DomainException|\InvalidArgumentException
     */
    public function createScanJob(Subnet $subnet, ?User $user = null): ScanJob
    {
        // 1. Authorization check
        if ($user && ! $user->canOperate()) {
            throw new \DomainException('Unauthorized: You do not have permission to execute network discovery scans.');
        }

        // 2. Subnet validity checks
        if (! $subnet->isAssignable()) {
            throw new \DomainException("Subnet {$subnet->cidr} is not marked as active and assignable for host scanning.");
        }

        if ($subnet->ip_version !== 4) {
            throw new \InvalidArgumentException("Network Scanner currently supports IPv4 address ranges. Subnet {$subnet->cidr} is IPv6.");
        }

        // 3. Size safety bounds (Max 512 hosts for safe hospital network scanning)
        $usableCount = $subnet->usableAddresses();
        if ($usableCount->isGreaterThan(512)) {
            throw new \InvalidArgumentException("For safety on hospital networks, subnet size ({$usableCount} hosts) exceeds maximum scan limit (512 hosts). Please select a /24 or smaller subnet segment.");
        }

        // 4. Subnet concurrency check (Only 1 scan running per subnet at any time)
        $subnetActiveScan = ScanJob::where('subnet_id', $subnet->id)
            ->whereIn('status', ['pending', 'running'])
            ->exists();

        if ($subnetActiveScan) {
            throw new \DomainException("A scan job is already running or queued for subnet {$subnet->cidr}. Please wait or cancel the active scan.");
        }

        // 5. System-wide concurrency throttle (Max 2 concurrent jobs across all subnets)
        $activeJobCount = ScanJob::whereIn('status', ['pending', 'running'])->count();
        if ($activeJobCount >= 2) {
            throw new \DomainException('System scan capacity limit reached (maximum 2 concurrent subnet scans). Please wait for ongoing scans to complete.');
        }

        $totalTargets = (int) (string) $usableCount;

        $job = ScanJob::create([
            'subnet_id' => $subnet->id,
            'user_id' => $user?->id,
            'status' => 'pending',
            'total_targets' => $totalTargets,
            'scanned_count' => 0,
            'responding_count' => 0,
            'unregistered_count' => 0,
            'started_at' => now(),
        ]);

        AuditEvent::record(
            eventType: 'scan.started',
            reason: "Operator initiated network discovery scan for subnet {$subnet->cidr} ({$totalTargets} targets)",
            subject: $job,
            actor: $user
        );

        return $job;
    }

    /**
     * Execute scan target processing for a scan job.
     * Supports chunking to bound memory and CPU execution time per step.
     */
    public function executeScan(ScanJob $job, int $chunkLimit = 0): void
    {
        $freshJob = $job->fresh();
        if (! $freshJob || $freshJob->isCancelled() || $freshJob->isCompleted() || $freshJob->isFailed()) {
            return;
        }

        if ($freshJob->status === 'pending') {
            $freshJob->update([
                'status' => 'running',
                'started_at' => $freshJob->started_at ?? now(),
            ]);
        }

        $subnet = $freshJob->subnet;
        $prefix = $subnet->toIpPrefix();
        $firstUsable = $prefix->firstUsableAddress();
        $totalUsable = (int) (string) $prefix->usableAddresses();

        // Already completed IPs in this scan
        $alreadyScanned = ScanResult::where('scan_job_id', $freshJob->id)
            ->pluck('ip_address')
            ->flip()
            ->all();

        // Preload authoritative IPAM records for this subnet to avoid N+1 queries
        $authoritativeIps = IpAddress::where('subnet_id', $subnet->id)
            ->get()
            ->keyBy('address_hex');

        $scannedInThisRun = 0;

        for ($i = 0; $i < $totalUsable; $i++) {
            // Check cancellation signal
            if ($scannedInThisRun > 0 && $scannedInThisRun % 5 === 0) {
                $checkJob = ScanJob::find($freshJob->id);
                if ($checkJob && $checkJob->isCancelled()) {
                    return;
                }
            }

            $targetVO = $firstUsable->add($i);
            $ipText = $targetVO->toNormalizedString();
            $ipHex = $targetVO->toHex();

            if (isset($alreadyScanned[$ipText])) {
                continue;
            }

            // Perform ICMP Echo check (with scanner bypass on interactive rate limiter)
            $pingResult = $this->pingService->ping($ipText, null, true);

            $status = $pingResult->isReachable() ? 'responding' : ($pingResult->isNoReply() ? 'no_reply' : 'error');
            $responseTime = $pingResult->responseTimeMs();
            $discoveredHostname = null;
            $discoveredMac = null;

            if ($status === 'responding') {
                // Reverse DNS lookup (suppressing network warnings)
                $resolved = @gethostbyaddr($ipText);
                if ($resolved && $resolved !== $ipText) {
                    $discoveredHostname = $resolved;
                } else {
                    $discoveredHostname = $this->lookupNetbiosName($ipText);
                }

                // ARP table lookup for MAC
                $discoveredMac = $this->arpService->lookupMac($ipText);
            }

            // Compare against authoritative IPAM records
            $ipRecord = $authoritativeIps->get($ipHex);

            $ipamStatus = $ipRecord ? $ipRecord->allocation_status->value : 'unassigned';
            $ipamHostname = $ipRecord?->hostname;
            $ipamAssignedTo = $ipRecord?->assigned_to;

            // Flag unregistered responding device
            $isUnregistered = ($status === 'responding' && $ipamStatus === 'unassigned');

            ScanResult::create([
                'scan_job_id' => $freshJob->id,
                'ip_address' => $ipText,
                'ip_hex' => $ipHex,
                'status' => $status,
                'response_time_ms' => $responseTime,
                'discovered_hostname' => $discoveredHostname,
                'discovered_mac' => $discoveredMac,
                'ipam_status' => $ipamStatus,
                'ipam_hostname' => $ipamHostname,
                'ipam_assigned_to' => $ipamAssignedTo,
                'is_unregistered' => $isUnregistered,
                'checked_at' => now(),
            ]);

            $alreadyScanned[$ipText] = true;
            $freshJob->increment('scanned_count');

            if ($status === 'responding') {
                $freshJob->increment('responding_count');
            }

            if ($isUnregistered) {
                $freshJob->increment('unregistered_count');
            }

            $scannedInThisRun++;

            // If chunk limit reached, stop and let next tick or worker continue
            if ($chunkLimit > 0 && $scannedInThisRun >= $chunkLimit) {
                break;
            }
        }

        $freshJob->refresh();
        if ($freshJob->scanned_count >= $freshJob->total_targets && ! $freshJob->isCancelled()) {
            $freshJob->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            AuditEvent::record(
                eventType: 'scan.completed',
                reason: "Completed network discovery scan for subnet {$subnet->cidr}. Responding: {$freshJob->responding_count}, Unregistered: {$freshJob->unregistered_count}",
                subject: $freshJob
            );
        }
    }

    /**
     * Stop and cancel a running scan job.
     */
    public function cancelScan(ScanJob $job, ?User $user = null): void
    {
        if ($job->isRunning()) {
            $job->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

            AuditEvent::record(
                eventType: 'scan.cancelled',
                reason: "Operator stopped network discovery scan #{$job->id} on subnet {$job->subnet->cidr}",
                subject: $job,
                actor: $user
            );
        }
    }

    /**
     * Look up NetBIOS computer name on Windows local network segment.
     */
    public function lookupNetbiosName(string $ip): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows' || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return null;
        }

        try {
            $process = new Process(['nbtstat', '-A', $ip]);
            $process->setTimeout(1.0);
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            $output = $process->getOutput();
            if (preg_match('/^\s*([A-Za-z0-9_-]+)\s+<00>\s+UNIQUE/m', $output, $matches)) {
                $name = trim($matches[1]);
                if (! empty($name) && strtolower($name) !== 'workgroup') {
                    return strtolower($name);
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
