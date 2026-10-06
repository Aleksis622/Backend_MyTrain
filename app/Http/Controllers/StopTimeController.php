<?php

namespace App\Http\Controllers;

use App\Models\StopTime;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StopTimeController extends Controller
{
    public function index(): LengthAwarePaginator
    {
        return StopTime::paginate(100);
    }

    public function show(int $id): StopTime
    {
        return StopTime::findOrFail($id);
    }
}
