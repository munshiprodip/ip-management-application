<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\Site;
use App\Services\SiteContext;
use Livewire\Component;

class CampusSwitcher extends Component
{
    public ?int $selectedSiteId = null;

    public function mount(SiteContext $context): void
    {
        $this->selectedSiteId = $context->getActiveSiteId();
    }

    public function updatedSelectedSiteId(?string $value): void
    {
        $siteId = ($value !== null && $value !== '' && $value !== '0') ? (int) $value : null;
        app(SiteContext::class)->setActiveSiteId($siteId);

        $site = $siteId ? Site::find($siteId) : null;
        $name = $site ? "{$site->name} ({$site->code})" : 'All Campuses (Global)';
        session()->flash('success', "Active campus switched to {$name}.");

        $this->dispatch('site-changed', siteId: $siteId);
        $this->redirect(request()->header('Referer') ?? route('dashboard'), navigate: false);
    }

    public function render()
    {
        $sites = Site::where('status', 'active')->orderBy('name')->get();

        return view('livewire.components.campus-switcher', [
            'sites' => $sites,
        ]);
    }
}

