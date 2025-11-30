<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProveedorFactory extends Factory
{


    /**
     * Define el estado por defecto del modelo.
     */
    public function definition(): array
    {
        // Genera nombres comerciales y de contacto realistas
        $companyName = fake()->unique()->company();
        $contactName = fake()->name();

        // 80% de probabilidad de tener RUC/NIT, Email y Teléfono
        $hasIdentification = fake()->boolean(80);
        $hasContactInfo = fake()->boolean(80);

        return [
            'nombre_comercial' => $companyName,
            'nombre_contacto' => $contactName,

            // Identificación (RUC/NIT)
            'identificacion' => $hasIdentification ? fake()->unique()->numberBetween(1000000000, 9999999999) : null,

            // Contacto
            'telefono' => $hasContactInfo ? fake()->phoneNumber() : null,
            'email' => $hasContactInfo ? fake()->unique()->companyEmail() : null,

            // Dirección
            'direccion' => fake()->streetAddress(),
            'ciudad' => fake()->city(),

            // Notas y estado
            'notas' => fake()->boolean(20) ? fake()->sentence() : null,
            'activo' => fake()->boolean(90), // La mayoría de los proveedores están activos

            // Los campos created_at y updated_at son automáticos
        ];
    }

    /**
     * Define un estado para crear proveedores inactivos.
     */
    public function inactivo(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Define un estado para crear proveedores activos.
     */
    public function activo(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'activo' => true,
        ]);
    }
}