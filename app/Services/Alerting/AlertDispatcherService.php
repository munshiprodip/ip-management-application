<?php

declare(strict_types=1);

namespace App\Services\Alerting;

use App\Domain\Alerts\Enums\AlertRuleCode;
use App\Domain\Alerts\Enums\AlertSeverity;
use App\Domain\Alerts\Enums\AlertStatus;
use App\Domain\Security\Enums\UserRole;
use App\Models\Alert;
use App\Models\AuditEvent;
use App\Models\NotificationDelivery;
use App\Models\ReconciliationFinding;
use App\Models\Subnet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AlertDispatcherService
{
    /**
     * Dispatches an operational alert with strict deduplication.
     * Repeated occurrences increment occurrence_count and update timestamps without flooding notifications.
     *
     * @param array{
     *     finding_id?: ?int,
     *     rule_code: AlertRuleCode|string,
     *     dedupe_key: string,
     *     severity?: AlertSeverity|string,
     *     title: string,
     *     description: string,
     *     context?: ?array
     * } $data
     */
    public function dispatchAlert(array $data): Alert
    {
        $dedupeKey = $data['dedupe_key'];
        $ruleCode = $data['rule_code'] instanceof AlertRuleCode ? $data['rule_code'] : AlertRuleCode::from((string) $data['rule_code']);
        $severity = isset($data['severity']) && $data['severity'] instanceof AlertSeverity
            ? $data['severity']
            : (isset($data['severity']) ? AlertSeverity::from((string) $data['severity']) : AlertSeverity::WARNING);

        return DB::transaction(function () use ($data, $dedupeKey, $ruleCode, $severity) {
            /** @var Alert|null $existing */
            $existing = Alert::where('dedupe_key', $dedupeKey)->open()->lockForUpdate()->first();

            // Deduplication: if alert is already open, increment counter and refresh timestamp
            if ($existing !== null) {
                $existing->increment('occurrence_count');
                $existing->update([
                    'last_occurred_at' => now(),
                    'context_json' => $data['context'] ?? $existing->context_json,
                ]);

                return $existing->fresh();
            }

            // Otherwise, create a new alert
            $alert = Alert::create([
                'finding_id' => $data['finding_id'] ?? null,
                'rule_code' => $ruleCode,
                'dedupe_key' => $dedupeKey,
                'severity' => $severity,
                'status' => AlertStatus::OPEN,
                'title' => $data['title'],
                'description' => $data['description'],
                'context_json' => $data['context'] ?? [],
                'occurrence_count' => 1,
                'opened_at' => now(),
                'last_occurred_at' => now(),
            ]);

            // Dispatch in-app notification to network administrators
            $recipients = User::whereIn('role', [UserRole::SUPER_ADMIN, UserRole::NETWORK_ADMIN])
                ->where('status', 'active')
                ->get();

            foreach ($recipients as $recipient) {
                NotificationDelivery::create([
                    'alert_id' => $alert->id,
                    'recipient_id' => $recipient->id,
                    'channel' => 'in_app',
                    'status' => 'delivered',
                    'subject' => "[{$severity->label()}] {$alert->title}",
                    'body' => $alert->description,
                    'sent_at' => now(),
                ]);
            }

            AuditEvent::record(
                eventType: 'alert.created',
                reason: sprintf('Raised operational alert [%s]: %s', $ruleCode->value, $alert->title),
                subject: $alert,
                after: [
                    'rule_code' => $ruleCode->value,
                    'severity' => $severity->value,
                    'dedupe_key' => $dedupeKey,
                ]
            );

            return $alert;
        });
    }

    /**
     * Evaluates a subnet for capacity exhaustion (>= 80% warning, >= 95% critical).
     */
    public function evaluateSubnetExhaustion(Subnet $subnet): ?Alert
    {
        $total = (int) (string) $subnet->totalAddresses();
        if ($total <= 0) {
            return null;
        }

        $allocated = $subnet->allocatedCount();
        $utilizationPct = ($allocated / $total) * 100;

        if ($utilizationPct < 80.0) {
            return null;
        }

        $severity = $utilizationPct >= 95.0 ? AlertSeverity::CRITICAL : AlertSeverity::WARNING;
        $dedupeKey = sprintf('SUBNET_EXHAUSTION:subnet_%d', $subnet->id);

        return $this->dispatchAlert([
            'rule_code' => AlertRuleCode::SUBNET_EXHAUSTION,
            'dedupe_key' => $dedupeKey,
            'severity' => $severity,
            'title' => sprintf('Subnet Capacity Warning: %s at %.1f%% utilization', $subnet->cidr, $utilizationPct),
            'description' => sprintf(
                'Subnet %s (%s) has allocated %d of %d usable addresses (%.1f%%). Immediate expansion or reclamation required.',
                $subnet->cidr,
                $subnet->name,
                $allocated,
                $total,
                $utilizationPct
            ),
            'context' => [
                'subnet_id' => $subnet->id,
                'cidr' => $subnet->cidr,
                'allocated' => $allocated,
                'total' => $total,
                'utilization_pct' => round($utilizationPct, 1),
            ],
        ]);
    }

    /**
     * Converts a reconciliation finding into a deduplicated operational alert.
     */
    public function evaluateFromFinding(ReconciliationFinding $finding): Alert
    {
        $ruleCode = match ($finding->finding_type->value) {
            'unknown_active_ip' => AlertRuleCode::ROGUE_IP,
            'unexpected_mac' => AlertRuleCode::MAC_DRIFT,
            'reported_duplicate_ip' => AlertRuleCode::IP_CONFLICT,
            'agent_down' => AlertRuleCode::AGENT_OFFLINE,
            default => AlertRuleCode::ROGUE_IP,
        };

        $severity = match ($finding->severity->value) {
            'critical' => AlertSeverity::CRITICAL,
            'warning' => AlertSeverity::WARNING,
            default => AlertSeverity::INFO,
        };

        $dedupeKey = sprintf('FINDING:%s:%s', $finding->finding_type->value, $finding->address_text);

        return $this->dispatchAlert([
            'finding_id' => $finding->id,
            'rule_code' => $ruleCode,
            'dedupe_key' => $dedupeKey,
            'severity' => $severity,
            'title' => $finding->title,
            'description' => $finding->details,
            'context' => [
                'finding_id' => $finding->id,
                'address_text' => $finding->address_text,
                'observed_mac' => $finding->observed_mac,
                'authoritative_mac' => $finding->authoritative_mac,
            ],
        ]);
    }
}
