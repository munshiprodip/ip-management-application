<?php

declare(strict_types=1);

namespace App\Actions\Organization;

use App\Models\AuditEvent;
use App\Models\Department;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageOrganizationAction
{
    public function createSite(array $data): Site
    {
        $orgId = (int) ($data['organization_id'] ?? Organization::firstOrCreate(
            ['code' => 'KYAMCH'],
            ['name' => 'Khwaja Yunus Ali Medical College & Hospital', 'slug' => 'kyamch', 'status' => 'active']
        )->id);

        $code = strtoupper(trim($data['code']));

        if (Site::where('organization_id', $orgId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => "Site code '{$code}' already exists in this organization.",
            ]);
        }

        return DB::transaction(function () use ($data, $orgId, $code) {
            $site = Site::create([
                'organization_id' => $orgId,
                'code' => $code,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'timezone' => $data['timezone'] ?? 'Asia/Dhaka',
                'status' => $data['status'] ?? 'active',
            ]);

            AuditEvent::record(
                eventType: 'site.created',
                reason: "Created campus site {$site->code} ({$site->name})",
                subject: $site,
                after: $site->toArray()
            );

            return $site;
        });
    }

    public function createDepartment(array $data): Department
    {
        $orgId = (int) ($data['organization_id'] ?? Organization::firstOrCreate(
            ['code' => 'KYAMCH'],
            ['name' => 'Khwaja Yunus Ali Medical College & Hospital', 'slug' => 'kyamch', 'status' => 'active']
        )->id);
        $code = strtoupper(trim($data['code']));

        if (Department::where('organization_id', $orgId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => "Department code '{$code}' already exists in this organization.",
            ]);
        }

        return DB::transaction(function () use ($data, $orgId, $code) {
            $dept = Department::create([
                'organization_id' => $orgId,
                'code' => $code,
                'name' => trim($data['name']),
                'owner_user_id' => $data['owner_user_id'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            AuditEvent::record(
                eventType: 'department.created',
                reason: "Created department {$dept->code} ({$dept->name})",
                subject: $dept,
                after: $dept->toArray()
            );

            return $dept;
        });
    }

    public function createLocation(array $data): Location
    {
        $siteId = (int) $data['site_id'];
        $code = strtoupper(trim($data['code']));
        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        return DB::transaction(function () use ($data, $siteId, $code, $parentId) {
            $location = Location::create([
                'site_id' => $siteId,
                'parent_id' => $parentId,
                'type' => $data['type'] ?? 'building',
                'code' => $code,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            AuditEvent::record(
                eventType: 'location.created',
                reason: "Created location {$location->code} ({$location->name})",
                subject: $location,
                after: $location->toArray()
            );

            return $location;
        });
    }
}
