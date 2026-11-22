<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditEvent;
use App\Services\Reporting\ReportExportService;
use Illuminate\Console\Command;

class IpamPurgeExpiredCommand extends Command
{
    protected $signature = 'ipam:purge-expired';
    protected $description = 'Purges expired temporary export files and records (Retention Gate T-027)';

    public function handle(ReportExportService $service): int
    {
        $this->info('Starting automated purge of expired temporary files...');

        $purged = $service->purgeExpiredExports();

        $this->info(sprintf('Purged %d expired export jobs and associated physical files.', $purged));

        AuditEvent::record(
            eventType: 'maintenance.exports_purged',
            reason: sprintf('Automated retention purge removed %d expired temporary export files.', $purged)
        );

        return 0;
    }
}
