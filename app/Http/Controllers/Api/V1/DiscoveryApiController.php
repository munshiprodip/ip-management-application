<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\AddressSpace\Enums\IpConnectivityStatus;
use App\Domain\Discovery\Enums\DiscoverySourceType;
use App\Http\Controllers\Controller;
use App\Models\DiscoveryAgent;
use App\Models\DiscoveryJob;
use App\Models\IpAddress;
use App\Models\IpObservation;
use App\Models\Subnet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoveryApiController extends Controller
{
    public function jobs(Request $request): JsonResponse
    {
        $jobs = DiscoveryJob::with(['agent', 'subnet'])->orderByDesc('id')->paginate(20);

        return response()->json([
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'total' => $jobs->total(),
                'version' => 'v1',
            ],
        ]);
    }

    /**
     * Ingests observational evidence from an authorized discovery collector agent.
     * Enforces T-018: Observations strictly update evidence; NEVER mutate authoritative allocation!
     */
    public function submitObservations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|integer|exists:discovery_agents,id',
            'job_id' => 'nullable|integer|exists:discovery_jobs,id',
            'observations' => 'required|array|min:1|max:500',
            'observations.*.address_text' => 'required|string',
            'observations.*.vrf_id' => 'required|integer|exists:vrfs,id',
            'observations.*.subnet_id' => 'required|integer|exists:subnets,id',
            'observations.*.source_type' => 'required|string',
            'observations.*.mac_address' => 'nullable|string',
            'observations.*.response_time_ms' => 'nullable|integer',
            'observations.*.confidence_score' => 'nullable|numeric|between:0,1',
        ]);

        $agent = DiscoveryAgent::findOrFail($validated['agent_id']);
        $agent->recordHeartbeat();

        $jobId = $validated['job_id'] ?? null;
        if ($jobId === null && ! empty($validated['observations'])) {
            $firstObs = $validated['observations'][0];
            $job = DiscoveryJob::create([
                'agent_id' => $agent->id,
                'subnet_id' => $firstObs['subnet_id'],
                'source_type' => $firstObs['source_type'] ?? 'icmp',
                'status' => \App\Domain\Discovery\Enums\JobStatus::COMPLETED,
                'started_at' => now(),
                'finished_at' => now(),
            ]);
            $jobId = $job->id;
        }

        $ingested = 0;

        foreach ($validated['observations'] as $obs) {
            // Verify agent authorization for target subnet (Gate T-019)
            if (! $agent->canScanSubnet((int) $obs['subnet_id'])) {
                continue;
            }

            $parsedIp = \App\Domain\AddressSpace\ValueObjects\IpAddress::parse($obs['address_text']);

            $ipRecord = IpAddress::where('vrf_id', $obs['vrf_id'])
                ->where('address_text', $obs['address_text'])
                ->first();

            // Ingest observation record
            IpObservation::create([
                'discovery_job_id' => $jobId,
                'subnet_id' => $obs['subnet_id'],
                'vrf_id' => $obs['vrf_id'],
                'ip_address_id' => $ipRecord?->id,
                'ip_version' => $parsedIp->version(),
                'address_hex' => $parsedIp->toHex(),
                'address_text' => $obs['address_text'],
                'observed_mac' => $obs['mac_address'] ?? null,
                'source_type' => $obs['source_type'] ?? 'icmp',
                'confidence' => (int) (($obs['confidence_score'] ?? 1.0) * 100),
                'observed_at' => now(),
            ]);

            // T-018: Update ONLY connectivity status if IP record exists
            if ($ipRecord !== null) {
                $ipRecord->update([
                    'connectivity_status' => IpConnectivityStatus::OBSERVED_ONLINE,
                    'last_observed_at' => now(),
                ]);
            }

            $ingested++;
        }

        return response()->json([
            'data' => [
                'status' => 'ingested',
                'items_processed' => count($validated['observations']),
                'items_accepted' => $ingested,
            ],
            'meta' => [
                'message' => 'Evidence batch safely recorded (T-018: Authoritative allocations unchanged).',
                'version' => 'v1',
            ],
        ], 201);
    }
}
