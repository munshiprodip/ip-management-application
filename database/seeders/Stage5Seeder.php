<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Workflows\ApproveRequestAction;
use App\Actions\Workflows\ProvisionRequestAction;
use App\Actions\Workflows\SubmitRequestAction;
use App\Domain\AddressSpace\Enums\IpClassification;
use App\Domain\Security\Enums\UserRole;
use App\Domain\Workflows\Enums\RequestPriority;
use App\Domain\Workflows\Enums\RequestType;
use App\Models\Department;
use App\Models\Device;
use App\Models\Subnet;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Stage5Seeder extends Seeder
{
    /**
     * Run the database seeds for Stage 5.
     */
    public function run(): void
    {
        $password = Hash::make('Password123!');

        // 1. Ensure Department Operator for ICU exists with proper scope
        $icuDept = Department::where('code', 'ICU')->first();
        $radDept = Department::where('code', 'RAD')->first();

        $icuOperator = User::updateOrCreate(
            ['email' => 'operator.icu@kyamch.local'],
            [
                'name' => 'ICU Biomedical Technician',
                'password' => $password,
                'role' => UserRole::DEPARTMENT_OPERATOR,
                'status' => 'active',
                'department' => 'Intensive Care Unit',
                'timezone' => 'Asia/Dhaka',
            ]
        );

        if ($icuDept !== null) {
            UserScope::firstOrCreate([
                'user_id' => $icuOperator->id,
                'scope_type' => 'department',
                'scope_id' => $icuDept->id,
            ], [
                'permission_level' => 'write',
            ]);
        }

        $generalOperator = User::where('email', 'operator@kyamch.local')->first() ?? $icuOperator;
        $approver = User::where('email', 'approver@kyamch.local')->first() ?? User::where('role', UserRole::SUPER_ADMIN)->first();
        $netAdmin = User::where('email', 'netadmin@kyamch.local')->first() ?? User::where('role', UserRole::SUPER_ADMIN)->first();

        $icuSubnet = Subnet::where('cidr', '10.10.20.0/24')->first();
        $radSubnet = Subnet::where('cidr', '10.10.10.0/24')->first();

        $submitAction = app(SubmitRequestAction::class);
        $approveAction = app(ApproveRequestAction::class);
        $provisionAction = app(ProvisionRequestAction::class);

        // Request 1: Approved IP Assignment ready for provisioning (ICU Monitor)
        if ($icuSubnet !== null) {
            $req1 = $submitAction->execute($icuOperator, [
                'request_type' => RequestType::IP_ASSIGNMENT,
                'department_id' => $icuDept?->id,
                'priority' => RequestPriority::URGENT,
                'reason' => 'New bedside telemetry monitor installation in ICU Bed 08.',
                'payload' => [
                    'subnet_id' => $icuSubnet->id,
                    'ip_address' => '10.10.20.50',
                    'hostname' => 'ky-icu-mon08.kyamch.local',
                    'classification' => IpClassification::STATIC_HOST->value,
                ],
                'idempotency_key' => 'SEED-REQ-00001',
            ]);

            // Independent approval by approver (T-022 compliant: approver != icuOperator)
            if ($req1->isPending()) {
                $approveAction->execute($req1, $approver, 'Approved telemetry deployment for ICU Bed 08.');
            }
        }

        // Request 2: Pending IP Assignment for Radiology
        if ($radSubnet !== null) {
            $submitAction->execute($generalOperator, [
                'request_type' => RequestType::IP_ASSIGNMENT,
                'department_id' => $radDept?->id,
                'priority' => RequestPriority::NORMAL,
                'reason' => 'Secondary diagnostic workstation for Radiology viewing room 3.',
                'payload' => [
                    'subnet_id' => $radSubnet->id,
                    'ip_address' => '10.10.10.45',
                    'hostname' => 'ky-rad-view03.kyamch.local',
                    'classification' => IpClassification::STATIC_HOST->value,
                ],
                'idempotency_key' => 'SEED-REQ-00002',
            ]);
        }

        // Request 3: Emergency Request (Pending, near SLA breach)
        if ($icuSubnet !== null) {
            $submitAction->execute($icuOperator, [
                'request_type' => RequestType::IP_ASSIGNMENT,
                'department_id' => $icuDept?->id,
                'priority' => RequestPriority::EMERGENCY,
                'reason' => 'Emergency ventilator network module connection for isolation ward 2.',
                'payload' => [
                    'subnet_id' => $icuSubnet->id,
                    'ip_address' => '10.10.20.99',
                    'hostname' => 'ky-icu-vent02.kyamch.local',
                    'classification' => IpClassification::STATIC_HOST->value,
                ],
                'idempotency_key' => 'SEED-REQ-00003',
            ]);
        }

        // Request 4: Provisioned Request with Change Records
        if ($radSubnet !== null) {
            $req4 = $submitAction->execute($generalOperator, [
                'request_type' => RequestType::IP_ASSIGNMENT,
                'department_id' => $radDept?->id,
                'priority' => RequestPriority::NORMAL,
                'reason' => 'Radiology ultrasound archive link server.',
                'payload' => [
                    'subnet_id' => $radSubnet->id,
                    'ip_address' => '10.10.10.60',
                    'hostname' => 'ky-rad-us01.kyamch.local',
                    'classification' => IpClassification::STATIC_HOST->value,
                ],
                'idempotency_key' => 'SEED-REQ-00004',
            ]);

            if ($req4->isPending()) {
                $approveAction->execute($req4, $approver, 'Approved for PACS archive integration.');
            }
            if ($req4->isApproved()) {
                $provisionAction->execute($req4, $netAdmin);
            }
        }
    }
}
