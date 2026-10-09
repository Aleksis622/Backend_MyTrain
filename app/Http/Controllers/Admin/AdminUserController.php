<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetUserLanguage;
use App\Models\AdminAction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * GET /admin/users?search=anna
     */
    public function index(Request $request): LengthAwarePaginator
    {
        $search = trim((string) $request->query('search', ''));

        return User::withCount('tickets')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(20);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user' => $user->loadCount('tickets'),
            'stats' => $user->tripStats(),
            'tickets' => $user->tickets()
                ->with(['journey.fromStop', 'journey.toStop', 'latestPayment'])
                ->latest()
                ->limit(20)
                ->get(),
            // For admin accounts: what they did last in the admin panel.
            'actions' => $user->is_admin
                ? AdminAction::where('user_id', $user->id)->latest()->limit(10)->get()
                : [],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'language' => ['required', Rule::in(SetUserLanguage::SUPPORTED)],
        ]);

        $user->update($data);

        if ($user->wasChanged()) {
            AdminAction::record($request->user(), AdminAction::USER_UPDATED, 'user', $user->id, [
                'name' => $user->name,
                'fields' => array_values(array_diff(array_keys($user->getChanges()), ['updated_at'])),
            ]);
        }

        return response()->json(['message' => 'User updated', 'user' => $user]);
    }

    /**
     * Deletes the user with their tickets and payments. Admin accounts are protected.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is_admin) {
            return response()->json(['error' => 'Admin accounts cannot be deleted.'], 422);
        }

        $user->delete();
        AdminAction::record($request->user(), AdminAction::USER_DELETED, 'user', $user->id, [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return response()->json(['message' => 'User deleted']);
    }
}
