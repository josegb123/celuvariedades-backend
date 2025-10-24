<?php

namespace Database\Factories;

use App\Modules\Mensajeria\Mensaje;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Mensajeria\Mensaje>
 */
class MensajeFactory extends Factory
{
    protected $model = Mensaje::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mensaje' => fake()->paragraph(1, true),
        ];
    }
}
