<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class L2Domain extends Model
{
    use HasFactory;

    protected $table = 'l2_domains';

    protected $fillable = [
        'site_id',
        'code',
        'name',
        'description',
        'status',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function vlans(): HasMany
    {
        return $this->hasMany(Vlan::class);
    }
}
