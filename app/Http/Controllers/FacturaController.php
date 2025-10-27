<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFacturaRequest;
use App\Http\Requests\UpdateFacturaRequest;
use App\Http\Resources\FacturaResource;
use App\Models\Factura;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return FacturaResource::collection(Factura::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFacturaRequest $request)
    {
        $factura = Factura::create($request->validated());
        return new FacturaResource($factura);
    }

    /**
     * Display the specified resource.
     */
    public function show(Factura $factura)
    {
        return new FacturaResource($factura);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFacturaRequest $request, Factura $factura)
    {
        $factura->update($request->validated());
        return new FacturaResource($factura);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Factura $factura)
    {
        $factura->delete();
        return response()->noContent();
    }
}
