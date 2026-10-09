<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminActivityController extends Controller
{
    /**
     * GET /admin/activity?type=ticket&admin_id=1 -> the activity log, newest first.
     */
    public function index(Request $request): LengthAwarePaginator
    {
        $data = $request->validate([
            'type' => ['nullable', Rule::in(AdminAction::SUBJECT_TYPES)],
            'admin_id' => 'nullable|integer',
        ]);

        return AdminAction::with('admin:id,name,email')
            ->when($data['type'] ?? null, fn ($query, $type) => $query->where('subject_type', $type))
            ->when($data['admin_id'] ?? null, fn ($query, $adminId) => $query->where('user_id', $adminId))
            ->latest()
            ->latest('id') // same-second entries keep their order
            ->paginate(25);
    }
}
