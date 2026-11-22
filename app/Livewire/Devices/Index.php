<?php

declare(strict_types=1);

namespace App\Livewire\Devices;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Device & Interface Inventory - KYAMCH IPAM')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.devices.index');
    }
}
