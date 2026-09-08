<?php

namespace App\Http\Controllers;

use App\Models\FareAttribute;

class FareAttributeController extends Controller
{
    public function index()
    {
        return FareAttribute::all();
    }

    public function show($fare_id)
    {
        $fare = FareAttribute::where('fare_id', $fare_id)->first();

        if (!$fare) {
            return response()->json(['error' => 'Fare attribute not found'], 404);
        }

        return $fare;
    }

    public function rules($fare_id)
    {
        $rules = \App\Models\FareRule::where('fare_id', $fare_id)->get();

        if ($rules->isEmpty()) {
            return response()->json(['error' => 'No fare rules found'], 404);
        }

        return $rules;
    }
}
