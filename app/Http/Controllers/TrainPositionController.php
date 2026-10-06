<?php

namespace App\Http\Controllers;

use App\Events\TrainPositionUpdated;
use App\Models\TrainPosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainPositionController extends Controller
{
    /**
     * Called by GPS devices / the simulator (protected by the train.tracker middleware).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'train_id' => 'required|exists:trains,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|between:0,360',
            'reported_at' => 'nullable|date',
        ]);

        $position = TrainPosition::create([
            ...$data,
            'reported_at' => $data['reported_at'] ?? now(),
        ]);

        // Pushes the new position to every map listening on the "map-trains" channel.
        event(new TrainPositionUpdated($position));

        return response()->json([
            'message' => 'Train position updated',
            'position' => $position,
        ], 201);
    }
}
