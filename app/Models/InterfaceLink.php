<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterfaceLink extends Model
{
    use HasFactory;

    protected $table = 'interface_links';

    protected $fillable = [
        'from_interface_id',
        'to_interface_id',
        'link_type',
        'status',
        'notes',
    ];

    public function fromInterface(): BelongsTo
    {
        return $this->belongsTo(DeviceInterface::class, 'from_interface_id');
    }

    public function toInterface(): BelongsTo
    {
        return $this->belongsTo(DeviceInterface::class, 'to_interface_id');
    }
}

