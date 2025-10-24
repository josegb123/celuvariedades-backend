<?php

namespace App\Modules\Cartera;

use App\Http\Controllers\Controller;
use App\Modules\Cartera\Requests\StoreCarteraRequest;
use App\Modules\Cartera\Requests\UpdateCarteraRequest;

class CarteraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CarteraResource::collection(Cartera::paginate(15));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCarteraRequest $request)
    {
        $cartera = Cartera::create($request->validated());
        return new CarteraResource($cartera);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCarteraRequest $request, Cartera $cartera)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cartera $cartera)
    {
        //
    }
}
