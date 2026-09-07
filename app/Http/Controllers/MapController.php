<?php

namespace App\Http\Controllers;

use App\Models\TrainPosition;

class MapController extends Controller
{
    public function trains()
    {
        return TrainPosition::with(['train'])
            ->orderBy('reported_at', 'desc')
            ->get();
    }
}
