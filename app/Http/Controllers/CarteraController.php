<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarteraRequest;
use App\Http\Requests\UpdateCarteraRequest;
use App\Http\Resources\CarteraResource;
use App\Models\Cartera;

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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCarteraRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Cartera $cartera)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cartera $cartera)
    {
        //
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
