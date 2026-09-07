<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Train;
use Illuminate\Http\Request;

class AdminTrainController extends Controller
{
    public function index()
    {
        return Train::paginate(30);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'operator' => 'nullable|string'
        ]);

        return Train::create($data);
    }

    public function update(Request $request, $id)
    {
        $train = Train::findOrFail($id);

        $train->update($request->only(['name', 'operator']));

        return response()->json($train);
    }

    public function destroy($id)
    {
        Train::findOrFail($id)->delete();

        return response()->json(['status' => 'deleted']);
    }
}
