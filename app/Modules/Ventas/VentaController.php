<?php

namespace App\Modules\Ventas;

use App\Http\Controllers\Controller;
use App\Modules\Ventas\Requests\StoreVentaRequest;
use App\Modules\Ventas\Requests\UpdateVentaRequest;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return VentaResource::collection(Venta::paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVentaRequest $request)
    {
        $venta = VentaResource::create($request->validated());

        return new VentaResource($venta);
    }

    /**
     * Display the specified resource.
     */
    public function show(Venta $venta)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVentaRequest $request, Venta $venta)
    {
        //
    }
}
