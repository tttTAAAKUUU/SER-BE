<?php

namespace App\Http\Controllers\Businesses\Stores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\Employee\AddStoreEmployeeRequest;
use App\Http\Requests\Business\Employee\UpdateStoreEmployeeRequest;
use App\Http\Resources\Business\StoreEmployeesResource;
use App\Models\Business\Store\Amenities;
use App\Models\Business\Store\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreAmenitiesController extends Controller
{
    public function index()
    {
        $amenities = Amenities::whereHas('store.business', function ($q) {
            $q->where('user_id', Auth::user()->id);
        })->get();

        return StoreEmployeesResource::collection($amenities);
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
    public function store(AddStoreEmployeeRequest $request)
    {
        $employee = Employee::create($request->validated());

        return new StoreEmployeesResource($employee);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreEmployeeRequest $request, string $id)
    {

        $employee = Employee::findOrFail($id);

        $employee->update($request->validated());

        return new StoreEmployeesResource($employee);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
