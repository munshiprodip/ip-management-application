<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('System Administration - KYAMCH IPAM')]
class Index extends Component
{
    public function render()
    {
        $users = User::orderBy('name')->get();

        return view('livewire.admin.index', [
            'users' => $users,
            'discoveryEnabled' => config('ipam.discovery_enabled'),
            'writesEnabled' => config('ipam.external_writes_enabled'),
            'dbDriver' => config('database.default'),
            'timezone' => config('ipam.default_timezone'),
            'quarantineDays' => config('ipam.quarantine_duration_days'),
        ]);
    }
}
