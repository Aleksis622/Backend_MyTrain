<?php

namespace App\Http\Controllers;

use App\Models\TrainPosition;
use Illuminate\Http\Request;

class TrainPositionController extends Controller
{
   
    public function index()
    {
        return TrainPosition::with('train')
            ->orderBy('reported_at', 'desc')
            ->get();
    }

   
    public function show($trainId)
    {
        return TrainPosition::where('train_id', $trainId)
            ->orderBy('reported_at', 'desc')
            ->firstOrFail();
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'train_id'    => 'required|exists:trains,id',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
            'speed'       => 'nullable|numeric',
            'heading'     => 'nullable|numeric',
            'reported_at' => 'nullable|date',
        ]);

        $position = TrainPosition::create([
            'train_id'    => $data['train_id'],
            'latitude'    => $data['latitude'],
            'longitude'   => $data['longitude'],
            'speed'       => $data['speed'] ?? null,
            'heading'     => $data['heading'] ?? null,
            'reported_at' => $data['reported_at'] ?? now(),
        ]);

        return response()->json([
            'message'  => 'Train position updated',
            'position' => $position,
        ], 201);
    }
}
