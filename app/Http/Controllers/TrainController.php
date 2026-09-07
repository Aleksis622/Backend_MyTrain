<?php

namespace App\Http\Controllers;

use App\Models\Train;
use Illuminate\Http\Request;

class TrainController extends Controller
{
    public function index()
    {
        return Train::paginate(20);
    }

    public function show($id)
    {
        return Train::with(['journeys'])->findOrFail($id);
    }
}
