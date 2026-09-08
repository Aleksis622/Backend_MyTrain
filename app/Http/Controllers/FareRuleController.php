<?php

namespace App\Http\Controllers;

use App\Models\FareRule;

class FareRuleController extends Controller
{
    public function index()
    {
        return FareRule::all();
    }

    public function show($fare_id)
    {
        $rules = FareRule::where('fare_id', $fare_id)->get();

        if ($rules->isEmpty()) {
            return response()->json(['error' => 'Fare rules not found'], 404);
        }

        return $rules;
    }
}
