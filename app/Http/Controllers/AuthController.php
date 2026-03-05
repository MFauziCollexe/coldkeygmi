<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'account' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(['account' => $credentials['account'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();
            $user = Auth::user();
            Log::info('Login success', ['user_id' => $user->id, 'account' => $user->account, 'ip' => $request->ip()]);
            return redirect()->intended('/dashboard');
        }

        Log::warning('Login failed', ['account' => $request->input('account'), 'ip' => $request->ip()]);
        return back()->withErrors(['account' => 'The provided credentials do not match our records.']);
    }
}
