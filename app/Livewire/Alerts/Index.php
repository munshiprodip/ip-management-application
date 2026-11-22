<?php

declare(strict_types=1);

namespace App\Livewire\Alerts;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Alerts & Operational Events - KYAMCH IPAM')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.alerts.index');
    }
}

