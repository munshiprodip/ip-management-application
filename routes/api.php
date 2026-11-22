<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AlertApiController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\DiscoveryApiController;
use App\Http\Controllers\Api\V1\IpAddressController;
use App\Http\Controllers\Api\V1\IpAllocationController;
use App\Http\Controllers\Api\V1\IpRequestController;
use App\Http\Controllers\Api\V1\ReconciliationApiController;
use App\Http\Controllers\Api\V1\SubnetController;
use App\Http\Controllers\Api\V1\VlanController;
use App\Http\Controllers\Api\V1\VrfController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — KYAMCH Smart IPAM v1
|--------------------------------------------------------------------------
| Protected by Bearer API Token Authentication, per-token rate limits,
| granular token scopes, and immutable audit logging.
*/

Route::prefix('v1')->middleware(['auth.api_token'])->group(function () {
    // VRFs
    Route::get('/vrfs', [VrfController::class, 'index'])->middleware('api_scope:read:vrfs');

    // Subnets & Hierarchy
    Route::get('/subnets', [SubnetController::class, 'index'])->middleware('api_scope:read:subnets');
    Route::get('/subnets/{subnet}', [SubnetController::class, 'show'])->middleware('api_scope:read:subnets');
    Route::get('/subnets/{subnet}/capacity', [SubnetController::class, 'capacity'])->middleware('api_scope:read:subnets');
    Route::get('/subnets/{subnet}/candidate-ips', [SubnetController::class, 'candidateIps'])->middleware('api_scope:read:subnets');

    // IP Addresses & History
    Route::get('/ip-addresses', [IpAddressController::class, 'index'])->middleware('api_scope:read:ips');
    Route::get('/ip-addresses/{ip}/history', [IpAddressController::class, 'history'])->middleware('api_scope:read:ips');

    // Allocations & Releases (External Writes)
    Route::post('/ip-allocations', [IpAllocationController::class, 'store'])->middleware('api_scope:write:allocations');
    Route::post('/ip-allocations/{id}/release', [IpAllocationController::class, 'release'])->middleware('api_scope:write:allocations');

    // Requests
    Route::post('/ip-requests', [IpRequestController::class, 'store'])->middleware('api_scope:write:requests');

    // Devices & Interfaces
    Route::get('/devices', [DeviceController::class, 'index'])->middleware('api_scope:read:devices');
    Route::get('/devices/{device}/interfaces', [DeviceController::class, 'interfaces'])->middleware('api_scope:read:devices');

    // VLANs
    Route::get('/vlans', [VlanController::class, 'index'])->middleware('api_scope:read:vlans');

    // Discovery & Agents
    Route::get('/discovery/jobs', [DiscoveryApiController::class, 'jobs'])->middleware('api_scope:read:discovery');
    Route::post('/discovery/observations', [DiscoveryApiController::class, 'submitObservations'])->middleware('api_scope:write:discovery');

    // Reconciliation Findings
    Route::get('/reconciliation/findings', [ReconciliationApiController::class, 'findings'])->middleware('api_scope:read:discovery');

    // Alerts
    Route::get('/alerts', [AlertApiController::class, 'index'])->middleware('api_scope:read:alerts');
    Route::post('/alerts/{alert}/acknowledge', [AlertApiController::class, 'acknowledge'])->middleware('api_scope:write:alerts');
    Route::post('/alerts/{alert}/resolve', [AlertApiController::class, 'resolve'])->middleware('api_scope:write:alerts');
});
