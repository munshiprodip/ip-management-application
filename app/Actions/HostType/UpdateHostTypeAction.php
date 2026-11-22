<?php

declare(strict_types=1);

namespace App\Actions\HostType;

use App\Models\AuditEvent;
use App\Models\HostType;
use App\Models\IpAddress;
use App\Models\IpAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateHostTypeAction
{
    /**
     * @param array{name?: ?string, slug?: ?string, description?: ?string, color?: ?string, is_active?: ?bool} $data
     */
    public function execute(HostType $hostType, array $data, ?User $actor = null): HostType
    {
        return DB::transaction(function () use ($hostType, $data, $actor) {
            $before = $hostType->toArray();

            $name = isset($data['name']) ? trim($data['name']) : $hostType->name;
            $newSlug = $hostType->slug;

            if (!empty($data['slug']) && $data['slug'] !== $hostType->slug) {
                if ($hostType->is_system) {
                    throw ValidationException::withMessages([
                        'slug' => 'System host type slugs cannot be modified.',
                    ]);
                }
                $newSlug = Str::slug(trim($data['slug']), '_');
                if (HostType::where('slug', $newSlug)->where('id', '!=', $hostType->id)->exists()) {
                    throw ValidationException::withMessages([
                        'slug' => "Host type slug '{$newSlug}' is already taken.",
                    ]);
                }

                // Cascade slug update to IP addresses & assignments
                $oldSlug = $hostType->slug;
                IpAddress::where('host_type', $oldSlug)->update(['host_type' => $newSlug]);
                IpAssignment::where('host_type', $oldSlug)->update(['host_type' => $newSlug]);
            }

            $hostType->update([
                'name' => $name,
                'slug' => $newSlug,
                'description' => array_key_exists('description', $data) ? ($data['description'] ? trim($data['description']) : null) : $hostType->description,
                'color' => !empty($data['color']) ? trim($data['color']) : $hostType->color,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $hostType->is_active,
            ]);

            AuditEvent::record(
                eventType: 'host_type.updated',
                reason: "Updated host type '{$hostType->name}'",
                subject: $hostType,
                before: $before,
                after: $hostType->fresh()->toArray(),
                actor: $actor
            );

            return $hostType;
        });
    }
}
