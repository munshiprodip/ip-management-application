<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;

class SiteContext
{
    public function getActiveSiteId(): ?int
    {
        $id = session('active_site_id');
        return ($id !== null && $id !== '' && $id !== 0) ? (int) $id : null;
    }

    public function setActiveSiteId(?int $siteId): void
    {
        if ($siteId === null || $siteId === 0) {
            session()->forget('active_site_id');
        } else {
            session(['active_site_id' => $siteId]);
        }
    }

    public function getActiveSite(): ?Site
    {
        $id = $this->getActiveSiteId();
        return $id ? Site::find($id) : null;
    }

    /**
     * @return Collection<int, Site>
     */
    public function getAllSites(): Collection
    {
        return Site::where('status', 'active')->orderBy('name')->get();
    }
}

