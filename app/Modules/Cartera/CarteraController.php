<?php

namespace App\Modules\Cartera;

use App\Http\Controllers\Controller;
use App\Modules\Cartera\Requests\StoreCarteraRequest;
use App\Modules\Cartera\Requests\UpdateCarteraRequest;
use App\Modules\Cartera\CarteraResource;

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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cartera $cartera)
    {
        //
    }
}
