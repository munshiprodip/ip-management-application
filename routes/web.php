<?php

use App\Livewire\Admin\Index as AdminIndex;
use App\Livewire\Alerts\Index as AlertsIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Devices\Index as DevicesIndex;
use App\Livewire\Discovery\Index as DiscoveryIndex;
use App\Livewire\IPAddresses\Index as IPAddressesIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Requests\Index as RequestsIndex;
use App\Livewire\Subnets\Index as SubnetsIndex;
use App\Livewire\Vlans\Index as VlansIndex;
use App\Livewire\Vrfs\Index as VrfsIndex;
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
    Route::get('/vrfs', VrfsIndex::class)->name('vrfs.index');
    Route::get('/vlans', VlansIndex::class)->name('vlans.index');
    Route::get('/ip-addresses', IPAddressesIndex::class)->name('ip-addresses.index');
    Route::get('/devices', DevicesIndex::class)->name('devices.index');
    Route::get('/requests', RequestsIndex::class)->name('requests.index');
    Route::get('/discovery', DiscoveryIndex::class)->name('discovery.index');
    Route::get('/alerts', AlertsIndex::class)->name('alerts.index');
    Route::get('/reports', ReportsIndex::class)->name('reports.index');
    Route::get('/reports/exports/{job}/download', function (App\Models\ExportJob $job, App\Services\Reporting\ReportExportService $service) {
        try {
            $content = $service->getExportFileContent($job, auth()->user());
            return response($content, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $job->file_name . '"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\RuntimeException $e) {
            abort(410, $e->getMessage());
        }
    })->name('reports.exports.download');
    Route::get('/admin', AdminIndex::class)->name('admin.index');
});
