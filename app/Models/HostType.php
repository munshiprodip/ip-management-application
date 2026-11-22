<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostType extends Model
{
    use HasFactory;

    protected $table = 'host_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function ipAddresses(): HasMany
    {
        return $this->hasMany(IpAddress::class, 'host_type', 'slug');
    }

    public function allocatedCount(): int
    {
        return $this->ipAddresses()->count();
    }

    public function badgeClasses(): string
    {
        return match ($this->color) {
            'rose' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800 font-semibold',
            'indigo' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800 font-medium',
            'blue' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800 font-medium',
            'purple' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800 font-medium',
            'orange' => 'bg-orange-50 text-orange-700 dark:bg-orange-950/60 dark:text-orange-300 border-orange-200 dark:border-orange-800 font-medium',
            'sky' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800 font-medium',
            'amber' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800 font-medium',
            'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800 font-medium',
            'teal' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300 border-teal-200 dark:border-teal-800 font-medium',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700 font-medium',
        };
    }

    public static function colorOptions(): array
    {
        return [
            'rose' => 'Rose (Medical / Clinical Equipment)',
            'indigo' => 'Indigo (Servers / Database / HIS)',
            'blue' => 'Blue (Workstations / Desktops / Laptops)',
            'purple' => 'Purple (Network Equipment / Switches)',
            'orange' => 'Orange (Printers / Thermal / Label)',
            'sky' => 'Sky Blue (Wireless / Access Points)',
            'amber' => 'Amber (Surveillance / CCTV / Access Control)',
            'emerald' => 'Emerald (Critical Care / Green / Monitored)',
            'teal' => 'Teal (Specialized Clinical / Lab Systems)',
            'slate' => 'Slate (Other / IoT / Sensors / Generic)',
        ];
    }
}

