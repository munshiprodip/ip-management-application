<?php

declare(strict_types=1);

namespace App\Livewire\Discovery;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Discovery & Reconciliation - KYAMCH IPAM')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.discovery.index', [
            'discoveryEnabled' => config('ipam.discovery_enabled'),
        ]);
    }
}

