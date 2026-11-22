<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $code = strtoupper($this->faker->unique()->lexify('CAMPUS-????'));

        return [
            'name' => $this->faker->company() . ' Campus',
            'code' => $code,
            'description' => $this->faker->sentence(),
            'status' => 'active',
            'is_default' => false,
        ];
    }
}

