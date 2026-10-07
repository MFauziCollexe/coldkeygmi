<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Show the signup page.
     */
    public function showSignUp()
    {
        return inertia('SignUp');
    }

    /**
     * Handle user signup/registration.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account' => 'required|string|unique:users,account',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],
            'acceptTerms' => 'required|accepted',
            'department' => 'nullable|string',
            'jobPosition' => 'nullable|string',
            'workPosition' => 'nullable|string',
        ]);

        try {
            Log::info('Signup attempt', ['account' => $request->input('account'), 'email' => $request->input('email'), 'ip' => $request->ip()]);

            $user = User::create([
                'name' => $validated['name'],
                'account' => $validated['account'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => 'deactivated',
                'department_id' => null,
                'position_id' => null,
                'user_created' => $request->ip(),
                'user_updated' => $request->ip(),
            ]);

            Log::info('User created', ['id' => $user->id, 'account' => $user->account, 'email' => $user->email, 'ip' => $request->ip()]);

            return redirect('/')->with('success', 'Account created successfully! Please wait for admin approval.');
        } catch (\Exception $e) {
            Log::error('Signup failed', ['account' => $request->input('account'), 'email' => $request->input('email'), 'ip' => $request->ip(), 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create account. Please try again.']);
        }
    }
}
