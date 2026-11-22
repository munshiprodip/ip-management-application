<?php

use App\Livewire\Auth\Login;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\IPAddresses\Index as IPAddressesIndex;
use App\Livewire\Subnets\Index as SubnetsIndex;
use App\Livewire\Vlans\Index as VlansIndex;
use App\Models\AuditEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Authenticated Enterprise IPAM Routes
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::post('/logout', function (Request $request) {
        $user = Auth::user();
        if ($user) {
            AuditEvent::record('auth.logout', 'Operator initiated sign out', $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been safely signed out.');
    })->name('logout');

    Route::get('/dashboard', DashboardIndex::class)->name('dashboard');
    Route::get('/subnets', SubnetsIndex::class)->name('subnets.index');
    Route::get('/vlans', VlansIndex::class)->name('vlans.index');
    Route::get('/ip-addresses', IPAddressesIndex::class)->name('ip-addresses.index');
    Route::get('/sites', \App\Livewire\Sites\Index::class)->name('sites.index');
    Route::get('/host-types', \App\Livewire\HostTypes\Index::class)->name('host-types.index');
});
