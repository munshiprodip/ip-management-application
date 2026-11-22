<?php

declare(strict_types=1);

namespace App\Livewire\Requests;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('IP Requests & Approvals - KYAMCH IPAM')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.requests.index');
    }
}
