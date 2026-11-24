<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ScanJob;
use App\Services\Network\NetworkScanService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScanSubnetJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300; // 5 minute max bounded process timeout
    public int $tries = 1;     // Do not blindly retry network scans on failure

    public function __construct(
        public int $scanJobId
    ) {}

    public function handle(NetworkScanService $scanner): void
    {
        $job = ScanJob::find($this->scanJobId);
        if (! $job || $job->isCancelled()) {
            return;
        }

        try {
            $scanner->executeScan($job);
        } catch (\Throwable $e) {
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }
}

