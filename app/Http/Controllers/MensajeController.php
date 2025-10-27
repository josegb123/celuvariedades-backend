<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMensajeRequest;
use App\Http\Requests\UpdateMensajeRequest;
use App\Models\Mensaje;

class MensajeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Mensaje::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMensajeRequest $request)
    {
        $mensaje = Mensaje::create($request->validated());
        return response()->json($mensaje, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Mensaje $mensaje)
    {
        return $mensaje;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMensajeRequest $request, Mensaje $mensaje)
    {
        $mensaje->update($request->validated());
        return response()->json($mensaje);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mensaje $mensaje)
    {
        $mensaje->delete();
        return response()->json(null, 204);
    }
}
