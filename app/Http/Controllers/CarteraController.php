<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarteraRequest;
use App\Http\Requests\UpdateCarteraRequest;
use App\Http\Resources\CarteraResource;
use App\Models\Cartera;

class CarteraController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCarteraRequest $request)
    {
        $cartera = Cartera::create($request->validated());

        return new CarteraResource($cartera);
    }

    public function show(Cartera $cartera)
    {

        return new CarteraResource($cartera);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCarteraRequest $request, Cartera $cartera)
    {
        $cartera->update($request->validated());

        return new CarteraResource($cartera);
    }
}
