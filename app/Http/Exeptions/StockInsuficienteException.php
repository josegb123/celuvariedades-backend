<?php

namespace App\Exceptions;

use Exception;

class StockInsuficienteException extends Exception
{
    protected $productoId;

    protected $cantidadSolicitada;

    protected $cantidadDisponible;

    public function __construct($productoId = null, int $cantidadSolicitada = 0, int $cantidadDisponible = 0, ?string $message = null, int $code = 0, ?Exception $previous = null)
    {
        $this->productoId = $productoId;
        $this->cantidadSolicitada = $cantidadSolicitada;
        $this->cantidadDisponible = $cantidadDisponible;

        if (is_null($message)) {
            $message = 'Stock insuficiente';
            if ($productoId !== null) {
                $message .= " para el producto ID {$productoId}";
            }
            $message .= " (solicitado: {$cantidadSolicitada}, disponible: {$cantidadDisponible})";
        }

        parent::__construct($message, $code, $previous);
    }

    public function getProductoId()
    {
        return $this->productoId;
    }

    public function getCantidadSolicitada(): int
    {
        return $this->cantidadSolicitada;
    }

    public function getCantidadDisponible(): int
    {
        return $this->cantidadDisponible;
    }

    // Método opcional para Laravel: devuelve respuesta JSON cuando se lanza la excepción
    public function render($request)
    {
        return response()->json([
            'error' => 'stock_insuficiente',
            'message' => $this->getMessage(),
            'producto_id' => $this->getProductoId(),
            'cantidad_solicitada' => $this->getCantidadSolicitada(),
            'cantidad_disponible' => $this->getCantidadDisponible(),
        ], 422);
    }
}
