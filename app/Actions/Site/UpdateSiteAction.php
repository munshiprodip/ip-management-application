<?php

declare(strict_types=1);

namespace App\Actions\Site;

use App\Models\AuditEvent;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSiteAction
{
    /**
     * @param array{name?: string, code?: string, description?: ?string, status?: string, is_default?: bool} $data
     */
    public function execute(Site $site, array $data): Site
    {
        if (isset($data['code'])) {
            $code = strtoupper(trim($data['code']));
            if (Site::where('code', $code)->where('id', '!=', $site->id)->exists()) {
                throw ValidationException::withMessages([
                    'code' => "Campus code '{$code}' already exists on another site.",
                ]);
            }
            $data['code'] = $code;
        }

        if (isset($data['name'])) {
            $data['name'] = trim($data['name']);
        }

        return DB::transaction(function () use ($site, $data) {
            $before = $site->toArray();

            if (!empty($data['is_default'])) {
                Site::where('id', '!=', $site->id)->where('is_default', true)->update(['is_default' => false]);
            }

            $site->update($data);
            $after = $site->fresh()->toArray();

            AuditEvent::record(
                eventType: 'site.updated',
                reason: "Updated campus '{$site->name}' [{$site->code}]",
                subject: $site,
                before: $before,
                after: $after
            );

            return $site;
        });
    }
}

