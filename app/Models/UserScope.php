<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserScope extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'scope_type',
        'scope_id',
        'permission_level',
        'created_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
