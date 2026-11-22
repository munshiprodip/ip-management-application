<?php

declare(strict_types=1);

namespace App\Actions\Site;

use App\Models\AuditEvent;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSiteAction
{
    /**
     * @param array{name: string, code: string, description?: ?string, status?: string, is_default?: bool} $data
     */
    public function execute(array $data): Site
    {
        $code = strtoupper(trim($data['code']));
        $name = trim($data['name']);

        if (Site::where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => "Campus code '{$code}' already exists.",
            ]);
        }

        return DB::transaction(function () use ($data, $code, $name) {
            $isDefault = (bool) ($data['is_default'] ?? false);

            if ($isDefault) {
                Site::where('is_default', true)->update(['is_default' => false]);
            }

            $site = Site::create([
                'name' => $name,
                'code' => $code,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'is_default' => $isDefault,
            ]);

            AuditEvent::record(
                eventType: 'site.created',
                reason: "Created campus '{$site->name}' [{$site->code}]",
                subject: $site,
                after: $site->toArray()
            );

            return $site;
        });
    }
}
