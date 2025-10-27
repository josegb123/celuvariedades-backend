<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovimientoFinancieroRequest;
use App\Http\Requests\UpdateMovimientoFinancieroRequest;
use App\Http\Resources\MovimientoFinancieroResource;
use App\Models\MovimientoFinanciero;
use Illuminate\Http\Request;

class MovimientoFinancieroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return MovimientoFinancieroResource::collection(MovimientoFinanciero::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMovimientoFinancieroRequest $request)
    {
        $movimiento = MovimientoFinanciero::create($request->validated());
        return new MovimientoFinancieroResource($movimiento);
    }

    /**
     * Display the specified resource.
     */
    public function show(MovimientoFinanciero $movimientoFinanciero)
    {
        return new MovimientoFinancieroResource($movimientoFinanciero);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMovimientoFinancieroRequest $request, MovimientoFinanciero $movimientoFinanciero)
    {
        $movimientoFinanciero->update($request->validated());
        return new MovimientoFinancieroResource($movimientoFinanciero);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MovimientoFinanciero $movimientoFinanciero)
    {
        $movimientoFinanciero->delete();
        return response()->noContent();
    }
}
