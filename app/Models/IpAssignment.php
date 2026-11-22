<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAssignment extends Model
{
    use HasFactory;

    protected $table = 'ip_assignments';

    protected $fillable = [
        'ip_address_id',
        'interface_id',
        'assigned_at',
        'released_at',
        'assignment_method',
        'assigned_by_user_id',
        'release_reason',
        'request_id',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function ipAddress(): BelongsTo
    {
        return $this->belongsTo(IpAddress::class);
    }

    public function interface(): BelongsTo
    {
        return $this->belongsTo(DeviceInterface::class, 'interface_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->released_at === null;
    }
}
