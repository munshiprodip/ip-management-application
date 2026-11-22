<?php

declare(strict_types=1);

namespace App\Services\Reporting;

use App\Domain\Alerts\Enums\ExportStatus;
use App\Domain\Alerts\Enums\ExportType;
use App\Domain\Security\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\Device;
use App\Models\ExportJob;
use App\Models\IpAddress;
use App\Models\Subnet;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportExportService
{
    /**
     * Bounded row limit to prevent memory exhaustion (Gate T-027).
     */
    public const MAX_ROWS = 5000;

    /**
     * Hours before an export expires and is purged (Gate T-027).
     */
    public const EXPIRATION_HOURS = 24;

    /**
     * Generates a bounded, scoped, temporary CSV export.
     *
     * @param array{department_id?: ?int, vrf_id?: ?int} $filters
     * @throws AuthorizationException
     */
    public function generateExport(User $user, ExportType|string $type, array $filters = []): ExportJob
    {
        $exportType = $type instanceof ExportType ? $type : ExportType::from((string) $type);

        // Security check: Department operators cannot export full system audit logs
        if ($user->isDepartmentOperator() && $exportType === ExportType::AUDIT_EVENTS) {
            throw new AuthorizationException('Department operators are not authorized to export global audit logs.');
        }

        // T-021 / UAT-09 Scope check: If a department filter is passed, verify user scope
        if (isset($filters['department_id']) && $filters['department_id'] !== null) {
            $deptId = (int) $filters['department_id'];
            if ($user->isDepartmentOperator() && ! $user->hasScope('department', $deptId)) {
                throw new AuthorizationException('You are not authorized to export records outside your assigned departmental scope.');
            }
        }

        $fileName = sprintf('kyamch_ipam_%s_%s_%s.csv', $exportType->value, now()->format('Ymd_His'), Str::random(6));
        $relativeDiskPath = 'exports/' . $fileName;

        // Generate rows based on type and user scope
        [$headers, $rows] = $this->queryExportData($user, $exportType, $filters);

        // Build CSV stream/content
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $fileSizeBytes = strlen($csvContent);

        // Store in private storage disk (Gate T-027)
        Storage::disk('local')->put('private/' . $relativeDiskPath, $csvContent);

        $job = ExportJob::create([
            'requested_by' => $user->id,
            'type' => $exportType,
            'format' => 'csv',
            'filters_json' => $filters,
            'status' => ExportStatus::COMPLETED,
            'private_file_path' => $relativeDiskPath,
            'file_name' => $fileName,
            'row_count' => count($rows),
            'file_size_bytes' => $fileSizeBytes,
            'expires_at' => now()->addHours(self::EXPIRATION_HOURS),
        ]);

        AuditEvent::record(
            eventType: 'export.generated',
            reason: sprintf('Generated %s export (%d rows, %d bytes) expiring in 24h', $exportType->value, count($rows), $fileSizeBytes),
            subject: $job,
            after: [
                'type' => $exportType->value,
                'row_count' => count($rows),
                'file_name' => $fileName,
                'expires_at' => $job->expires_at->toIso8601String(),
            ]
        );

        return $job;
    }

    /**
     * Retrieves the file content, enforcing requester authorization and expiry (Gate T-027).
     *
     * @throws AuthorizationException|\RuntimeException
     */
    public function getExportFileContent(ExportJob $job, User $user): string
    {
        // Must be requester or super admin/auditor
        if ($job->requested_by !== $user->id && ! in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::SECURITY_AUDITOR], true)) {
            throw new AuthorizationException('You are not authorized to download this export file.');
        }

        // T-027 Expiration verification
        if ($job->isExpired()) {
            throw new \RuntimeException('Export file has expired and is no longer available for download.');
        }

        $fullPath = 'private/' . $job->private_file_path;
        if (! Storage::disk('local')->exists($fullPath)) {
            throw new \RuntimeException('Export file not found on private storage.');
        }

        return Storage::disk('local')->get($fullPath);
    }

    /**
     * Purges temporary export files that have reached expiration (Gate T-027 / Sec 15.3).
     */
    public function purgeExpiredExports(): int
    {
        $expiredJobs = ExportJob::expired()->get();
        $purgedCount = 0;

        foreach ($expiredJobs as $job) {
            if ($job->private_file_path) {
                Storage::disk('local')->delete('private/' . $job->private_file_path);
            }
            $job->delete();
            $purgedCount++;
        }

        return $purgedCount;
    }

    /**
     * Queries and formats export data adhering to maximum row limit and departmental scopes.
     *
     * @return array{0: array<string>, 1: array<array<mixed>>}
     */
    private function queryExportData(User $user, ExportType $type, array $filters): array
    {
        $allowedDepts = $user->isDepartmentOperator() ? $user->allowedDepartmentIds() : null;

        return match ($type) {
            ExportType::SUBNETS => $this->exportSubnets($allowedDepts, $filters),
            ExportType::IP_ADDRESSES => $this->exportIpAddresses($allowedDepts, $filters),
            ExportType::DEVICES => $this->exportDevices($allowedDepts, $filters),
            ExportType::AUDIT_EVENTS => $this->exportAuditEvents($filters),
        };
    }

    private function exportSubnets(?array $allowedDepts, array $filters): array
    {
        $headers = ['ID', 'CIDR', 'Name', 'VRF', 'IP Version', 'Prefix Type', 'Site', 'Department', 'Total Addresses', 'Allocated', 'Utilization %', 'Status'];

        $query = Subnet::with(['vrf', 'site', 'department'])->limit(self::MAX_ROWS);

        if ($allowedDepts !== null) {
            $query->whereIn('department_id', $allowedDepts);
        } elseif (isset($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        if (isset($filters['vrf_id'])) {
            $query->where('vrf_id', (int) $filters['vrf_id']);
        }

        $subnets = $query->get();
        $rows = [];

        foreach ($subnets as $subnet) {
            $total = (int) (string) $subnet->totalAddresses();
            $allocated = $subnet->allocatedCount();
            $utilization = $total > 0 ? round(($allocated / $total) * 100, 1) : 0;

            $rows[] = [
                $subnet->id,
                $subnet->cidr,
                $subnet->name,
                $subnet->vrf->code ?? 'N/A',
                $subnet->ip_version,
                $subnet->prefix_type->value ?? 'assignable',
                $subnet->site->name ?? 'Global',
                $subnet->department->name ?? 'All Departments',
                $total,
                $allocated,
                $utilization . '%',
                $subnet->status->value ?? 'active',
            ];
        }

        return [$headers, $rows];
    }

    private function exportIpAddresses(?array $allowedDepts, array $filters): array
    {
        $headers = ['ID', 'IP Address', 'VRF', 'Subnet CIDR', 'Status', 'Observed Status', 'Classification', 'FQDN', 'MAC Address', 'Department'];

        $query = IpAddress::with(['subnet', 'vrf', 'assignments.deviceInterface.device.department'])->limit(self::MAX_ROWS);

        if ($allowedDepts !== null) {
            $query->whereHas('assignments.deviceInterface.device', function ($q) use ($allowedDepts) {
                $q->whereIn('department_id', $allowedDepts);
            });
        } elseif (isset($filters['department_id'])) {
            $deptId = (int) $filters['department_id'];
            $query->whereHas('assignments.deviceInterface.device', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }

        if (isset($filters['vrf_id'])) {
            $query->where('vrf_id', (int) $filters['vrf_id']);
        }

        $ips = $query->get();
        $rows = [];

        foreach ($ips as $ip) {
            $assignment = $ip->assignments->first();
            $device = $assignment?->deviceInterface?->device;

            $rows[] = [
                $ip->id,
                $ip->address_text,
                $ip->vrf->code ?? 'N/A',
                $ip->subnet->cidr ?? 'N/A',
                $ip->allocation_status->value ?? (string) $ip->allocation_status,
                $ip->connectivity_status->value ?? 'unobserved',
                $ip->classification->value ?? 'static_host',
                $ip->fqdn ?? '',
                $assignment?->deviceInterface?->mac_address ?? '',
                $device?->department?->name ?? 'Unassigned',
            ];
        }

        return [$headers, $rows];
    }

    private function exportDevices(?array $allowedDepts, array $filters): array
    {
        $headers = ['ID', 'Name', 'Primary FQDN', 'Site', 'Department', 'Device Type', 'Status', 'Interfaces Count'];

        $query = Device::with(['site', 'department', 'deviceType', 'interfaces'])->limit(self::MAX_ROWS);

        if ($allowedDepts !== null) {
            $query->whereIn('department_id', $allowedDepts);
        } elseif (isset($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        $devices = $query->get();
        $rows = [];

        foreach ($devices as $dev) {
            $rows[] = [
                $dev->id,
                $dev->name,
                $dev->primary_fqdn ?? '',
                $dev->site->name ?? 'N/A',
                $dev->department->name ?? 'N/A',
                $dev->deviceType->name ?? 'General',
                $dev->status->value ?? 'active',
                $dev->interfaces->count(),
            ];
        }

        return [$headers, $rows];
    }

    private function exportAuditEvents(array $filters): array
    {
        $headers = ['ID', 'Timestamp', 'Event Type', 'Actor', 'Reason', 'IP Address', 'Subject Type', 'Subject ID'];

        $query = AuditEvent::with('actor')->latest()->limit(self::MAX_ROWS);

        $events = $query->get();
        $rows = [];

        foreach ($events as $event) {
            $rows[] = [
                $event->id,
                $event->created_at->toIso8601String(),
                $event->event_type,
                $event->actor->name ?? 'System',
                $event->reason ?? '',
                $event->ip_address ?? '',
                $event->subject_type ?? '',
                $event->subject_id ?? '',
            ];
        }

        return [$headers, $rows];
    }
}
