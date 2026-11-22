<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\AddressSpace\Enums\HostType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAssignment extends Model
{
    use HasFactory;

    protected $table = 'ip_assignments';

    protected $fillable = [
        'ip_address_id',
        'assigned_to',
        'host_type',
        'hostname',
        'mac_address',
        'assignment_method',
        'assigned_by_user_id',
        'assigned_at',
        'released_at',
        'release_reason',
        'notes',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'host_type' => HostType::class,
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function ipAddress(): BelongsTo
    {
        return $this->belongsTo(IpAddress::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
