<?php

declare(strict_types=1);

namespace App\Actions\Layer2;

use App\Models\AuditEvent;
use App\Models\L2Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateL2DomainAction
{
    /**
     * @param array{code?: string, name?: string, description?: ?string, status?: string} $data
     */
    public function execute(L2Domain $domain, array $data): L2Domain
    {
        if (isset($data['code'])) {
            $code = strtoupper(trim($data['code']));
            if (L2Domain::where('site_id', $domain->site_id)
                ->where('code', $code)
                ->where('id', '!=', $domain->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'code' => "Layer 2 domain with code '{$code}' already exists for this site.",
                ]);
            }
            $data['code'] = $code;
        }

        return DB::transaction(function () use ($domain, $data) {
            $before = $domain->toArray();
            $domain->update($data);
            $after = $domain->fresh()->toArray();

            AuditEvent::record(
                eventType: 'l2_domain.updated',
                reason: "Updated L2 Domain {$domain->code}",
                subject: $domain,
                before: $before,
                after: $after
            );

            return $domain;
        });
    }
}
