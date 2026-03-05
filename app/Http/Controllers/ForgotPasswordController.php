<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password page.
     */
    public function showForgotPassword()
    {
        return inertia('ForgotPassword');
    }

    /**
     * Handle forgot password request.
     * Since we're not using email, we verify by account + email
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'account' => 'required|string|exists:users,account',
            'email' => 'required|email',
        ]);

        // Check if account and email match
        $user = User::where('account', $validated['account'])
                    ->where('email', $validated['email'])
                    ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'The account and email do not match our records.']);
        }

        // Generate temporary password reset token (simple random string)
        $resetToken = Str::random(32);
        
        // Store token in session (in production, use database or cache)
        session(['password_reset_token' => $resetToken, 'password_reset_user_id' => $user->id]);

        Log::info('Password reset requested', ['account' => $user->account, 'email' => $user->email, 'ip' => $request->ip()]);

        return redirect('/reset-password')->with('success', 'Identity verified. Please enter your new password.');
    }

    /**
     * Show the reset password page.
     */
    public function showResetPassword()
    {
        if (!session()->has('password_reset_token') || !session()->has('password_reset_user_id')) {
            return redirect('/forgot-password')->withErrors(['error' => 'Please verify your identity first.']);
        }

        return inertia('ResetPassword');
    }

    /**
     * Handle password reset.
     */
    public function reset(Request $request)
    {
        if (!session()->has('password_reset_token') || !session()->has('password_reset_user_id')) {
            return redirect('/forgot-password')->withErrors(['error' => 'Please verify your identity first.']);
        }

        $validated = $request->validate([
            'password' => 'required|confirmed',
        ]);

        $userId = session('password_reset_user_id');
        $user = User::find($userId);

        if (!$user) {
            return back()->withErrors(['error' => 'User not found.']);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
            'user_updated' => 'password_reset',
        ]);

        Log::info('Password reset completed', ['account' => $user->account, 'email' => $user->email, 'ip' => $request->ip()]);

        // Clear session
        session()->forget('password_reset_token');
        session()->forget('password_reset_user_id');

        return redirect('/')->with('success', 'Password has been reset successfully. Please login with your new password.');
    }
}
