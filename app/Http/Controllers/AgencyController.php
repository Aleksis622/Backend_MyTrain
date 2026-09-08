<?php

namespace App\Http\Controllers;

use App\Models\Agency;

class AgencyController extends Controller
{
    public function index()
    {
        return Agency::all();
    }

    public function show($agency_id)
    {
        $agency = Agency::where('agency_id', $agency_id)->first();

        if (!$agency) {
            return response()->json(['error' => 'Agency not found'], 404);
        }

        return $agency;
    }
}
