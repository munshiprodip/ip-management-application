<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Discovery\Enums\FindingSeverity;
use App\Domain\Discovery\Enums\FindingStatus;
use App\Domain\Discovery\Enums\FindingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationFinding extends Model
{
    use HasFactory;

    protected $fillable = [
        'finding_type',
        'vrf_id',
        'subnet_id',
        'ip_address_id',
        'address_text',
        'severity',
        'status',
        'title',
        'details',
        'observed_mac',
        'authoritative_mac',
        'evidence_refs_json',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'finding_type' => FindingType::class,
            'severity' => FindingSeverity::class,
            'status' => FindingStatus::class,
            'evidence_refs_json' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function vrf(): BelongsTo
    {
        return $this->belongsTo(Vrf::class, 'vrf_id');
    }

    public function subnet(): BelongsTo
    {
        return $this->belongsTo(Subnet::class, 'subnet_id');
    }

    public function ipAddress(): BelongsTo
    {
        return $this->belongsTo(IpAddress::class, 'ip_address_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [FindingStatus::OPEN, FindingStatus::ACKNOWLEDGED], true);
    }

    public function acknowledge(User $user): void
    {
        $this->update([
            'status' => FindingStatus::ACKNOWLEDGED,
        ]);
    }

    public function resolve(User $user, string $note): void
    {
        $this->update([
            'status' => FindingStatus::RESOLVED,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'resolution_note' => $note,
        ]);
    }

    public function ignore(User $user, string $note): void
    {
        $this->update([
            'status' => FindingStatus::IGNORED,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'resolution_note' => $note,
        ]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [FindingStatus::OPEN->value, FindingStatus::ACKNOWLEDGED->value]);
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', FindingSeverity::CRITICAL->value);
    }
}
