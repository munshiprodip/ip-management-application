<?php

declare(strict_types=1);

namespace App\Livewire\Subnets;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Subnets & CIDR Hierarchy - KYAMCH IPAM')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.subnets.index');
    }
}
