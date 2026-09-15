<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use Illuminate\Http\Request;

class JourneyController extends Controller
{
    public function index()
    {
        return Journey::with(['train', 'fromStop', 'toStop'])
            ->paginate(20);
    }

    public function show($id)
    {
        return Journey::with(['train', 'fromStop', 'toStop'])
            ->findOrFail($id);
    }
}
