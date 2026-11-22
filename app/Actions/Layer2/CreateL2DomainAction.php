<?php

declare(strict_types=1);

namespace App\Actions\Layer2;

use App\Models\AuditEvent;
use App\Models\L2Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateL2DomainAction
{
    /**
     * @param array{site_id: int, code: string, name: string, description?: ?string, status?: string} $data
     */
    public function execute(array $data): L2Domain
    {
        $code = strtoupper(trim($data['code']));
        $siteId = (int) $data['site_id'];

        if (L2Domain::where('site_id', $siteId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => "Layer 2 domain with code '{$code}' already exists for this site.",
            ]);
        }

        return DB::transaction(function () use ($data, $siteId, $code) {
            $domain = L2Domain::create([
                'site_id' => $siteId,
                'code' => $code,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            AuditEvent::record(
                eventType: 'l2_domain.created',
                reason: "Created L2 Domain {$domain->code} ({$domain->name})",
                subject: $domain,
                after: $domain->toArray()
            );

            return $domain;
        });
    }
}
