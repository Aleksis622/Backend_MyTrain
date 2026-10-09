<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "My account" page of the logged-in admin.
 */
class AdminAccountController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $admin = $request->user();

        return response()->json([
            'user' => $admin,
            'actions_total' => AdminAction::where('user_id', $admin->id)->count(),
            'actions_today' => AdminAction::where('user_id', $admin->id)->whereDate('created_at', today())->count(),
            'recent_actions' => AdminAction::where('user_id', $admin->id)->latest()->limit(8)->get(),
        ]);
    }

    /**
     * PUT /admin/account { name, email }
     */
    public function update(Request $request): JsonResponse
    {
        $admin = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
        ]);

        $admin->update($data);

        if ($admin->wasChanged()) {
            AdminAction::record($admin, AdminAction::ACCOUNT_UPDATED, 'account', $admin->id, [
                'fields' => array_values(array_diff(array_keys($admin->getChanges()), ['updated_at'])),
            ]);
        }

        return response()->json(['message' => 'Account updated', 'user' => $admin]);
    }

    /**
     * PUT /admin/account/password { current_password, password, password_confirmation }
     */
    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|current_password:web',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ]);

        $admin = $request->user();
        $admin->update(['password' => $data['password']]);
        AdminAction::record($admin, AdminAction::PASSWORD_CHANGED, 'account', $admin->id);

        return response()->json(['message' => 'Password changed']);
    }
}
