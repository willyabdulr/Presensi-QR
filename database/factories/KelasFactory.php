<?php

namespace Database\Factories;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode_kelas' => fake()->unique()->bothify('04SIFE###'),
        ];
    }
}
