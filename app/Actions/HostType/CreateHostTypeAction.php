<?php

declare(strict_types=1);

namespace App\Actions\HostType;

use App\Models\AuditEvent;
use App\Models\HostType;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateHostTypeAction
{
    /**
     * @param array{name: string, slug?: ?string, description?: ?string, color?: ?string, is_active?: ?bool} $data
     */
    public function execute(array $data, ?User $actor = null): HostType
    {
        $name = trim($data['name']);
        $slug = !empty($data['slug']) ? Str::slug(trim($data['slug']), '_') : Str::slug($name, '_');

        if (HostType::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => "Host type slug '{$slug}' is already in use.",
            ]);
        }

        $hostType = HostType::create([
            'name' => $name,
            'slug' => $slug,
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'color' => !empty($data['color']) ? trim($data['color']) : 'slate',
            'is_active' => $data['is_active'] ?? true,
            'is_system' => false,
        ]);

        AuditEvent::record(
            eventType: 'host_type.created',
            reason: "Created new host type '{$hostType->name}' ({$hostType->slug})",
            subject: $hostType,
            after: $hostType->toArray(),
            actor: $actor
        );

        return $hostType;
    }
}

