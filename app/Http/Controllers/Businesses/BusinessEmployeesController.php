<?php

namespace App\Http\Controllers\Businesses;

use App\Http\Controllers\Controller;
use App\Http\Resources\Business\BusinessEmployeesResource;
use App\Models\Business\Store\Employee;
use Illuminate\Support\Facades\Auth;

class BusinessEmployeesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::join('stores', 'stores.id', 'employees.store_id')
            ->join('businesses', 'businesses.id', 'stores.business_id')
            ->where('businesses.user_id', '=', Auth::user()->id)->get();

        return BusinessEmployeesResource::collection($employees);
    }
}
