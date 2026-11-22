<?php

declare(strict_types=1);

namespace App\Livewire\Vrfs;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('VRF Routing Contexts - KYAMCH IPAM')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.vrfs.index');
    }
}
