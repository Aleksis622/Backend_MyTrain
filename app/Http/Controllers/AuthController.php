<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'language' => 'nullable|in:lv,en,ru'
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'language' => $data['language'] ?? 'lv',
        ]);

        // Auto-login after registration
        Auth::login($user);

        return response()->json([
            'message' => 'Registration successful',
            'user'    => $user,
        ], 201);
    }

    
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($data)) {
            return response()->json([
                'error' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();

        return response()->json([
            'message' => 'Login successful',
            'user'    => $user,
        ]);
    }

    
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function changeLanguage(Request $request)
    {
        $request->validate([
            'language' => 'required|in:lv,en,ru'
        ]);

        $user = $request->user();
        $user->language = $request->language;
        $user->save();

        return response()->json([
            'message'  => __('language_changed'),
            'language' => $user->language
        ]);
    }
}
