<?php

namespace Database\Factories;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Factura>
 */
class FacturaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venta_id' => Venta::all()->random()->id,
            'fecha_emision' => $this->faker->date(),
            'total' => $this->faker->randomFloat(2, 1000, 100000),
            'pdf_path' => $this->faker->filePath(),
        ];
    }
}
