<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class StockInsuficienteException extends Exception
{
    protected $productoId;

    protected float $cantidadSolicitada;

    protected float $cantidadDisponible;

    /**
     * Constructor de la excepción.
     * * @param int|null $productoId ID del producto.
     * @param float $cantidadSolicitada Cantidad que el usuario intentó vender.
     * @param float $cantidadDisponible Stock disponible en el sistema.
     * @param string|null $message Mensaje opcional.
     * @param int $code Código HTTP de la excepción.
     * @param \Exception|null $previous Excepción previa.
     */
    public function __construct($productoId = null, float $cantidadSolicitada = 0.0, float $cantidadDisponible = 0.0, ?string $message = null, int $code = 0, ?Exception $previous = null)
    {
        $this->productoId = $productoId;
        $this->cantidadSolicitada = $cantidadSolicitada;
        $this->cantidadDisponible = $cantidadDisponible;

        // Si no se proporciona un mensaje, construimos uno detallado.
        if (is_null($message)) {
            $message = 'Stock insuficiente';
            if ($productoId !== null) {
                $message .= " para el producto ID {$productoId}";
            }
            $message .= " (solicitado: {$cantidadSolicitada}, disponible: {$cantidadDisponible})";
        }

        // El código 422 (Unprocessable Entity) es el más apropiado para errores de validación de negocio.
        parent::__construct($message, $code ?: 422, $previous);
    }

    public function getProductoId()
    {
        return $this->productoId;
    }

    public function getCantidadSolicitada(): float
    {
        return $this->cantidadSolicitada;
    }

    public function getCantidadDisponible(): float
    {
        return $this->cantidadDisponible;
    }

    /**
     * Devuelve una respuesta JSON estructurada cuando se lanza la excepción.
     * * @param  \Illuminate\Http\Request  $request
     */
    public function render(Request $request)
    {
        return response()->json([
            'error' => 'stock_insuficiente',
            'message' => $this->getMessage(),
            'producto_id' => $this->getProductoId(),
            'cantidad_solicitada' => $this->getCantidadSolicitada(),
            'cantidad_disponible' => $this->getCantidadDisponible(),
        ], 422); // Usamos 422 para errores de negocio
    }
}