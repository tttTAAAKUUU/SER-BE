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
        $employees = Employee::whereHas('store.business', function ($q) {
            $q->where('user_id', Auth::user()->id);
        })->get();

        return BusinessEmployeesResource::collection($employees);
    }
}
