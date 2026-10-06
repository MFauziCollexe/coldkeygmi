<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserImpersonationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserImpersonationController extends Controller
{
    private const TOKEN_TTL_MINUTES = 5;

    public function createLink(Request $request, User $user)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($user->status === 'active', 422, 'Login link can only be created for active users.');
        abort_if((int) $request->user()->id === (int) $user->id, 422, 'You are already logged in as this user.');

        UserImpersonationToken::query()
            ->where(function ($query) {
                $query->where('expires_at', '<=', now())
                    ->orWhereNotNull('consumed_at');
            })
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $plainToken = Str::random(64);
        $expiresAt = now()->addMinutes(self::TOKEN_TTL_MINUTES);

        UserImpersonationToken::create([
            'token_hash' => hash('sha256', $plainToken),
            'target_user_id' => $user->id,
            'issued_by_user_id' => $request->user()->id,
            'expires_at' => $expiresAt,
        ]);

        Log::notice('Temporary user access link issued', [
            'issued_by_user_id' => $request->user()->id,
            'target_user_id' => $user->id,
            'expires_at' => $expiresAt->toIso8601String(),
            'ip' => $request->ip(),
        ]);

        return redirect()
            ->route('control.users.index', $request->only(['search', 'status', 'department_id', 'page']))
            ->with('login_as_url', route('user-impersonation.confirm', ['token' => $plainToken]));
    }

    public function confirm(string $token)
    {
        $access = UserImpersonationToken::query()
            ->with('targetUser:id,name,account,status')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

        abort_unless($access && $access->targetUser?->status === 'active', 404);

        return Inertia::render('Auth/ImpersonationConfirm', [
            'token' => $token,
            'target' => [
                'name' => $access->targetUser->name,
                'account' => $access->targetUser->account,
            ],
            'expiresAt' => $access->expires_at->toIso8601String(),
        ]);
    }

    public function redeem(Request $request, string $token)
    {
        $plainHash = hash('sha256', $token);

        $access = DB::transaction(function () use ($plainHash, $request) {
            $access = UserImpersonationToken::query()
                ->where('token_hash', $plainHash)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            abort_unless($access, 404);

            $target = User::query()->find($access->target_user_id);
            abort_unless($target && $target->status === 'active', 404);

            $access->update([
                'consumed_at' => now(),
                'redeemed_ip' => $request->ip(),
            ]);

            return $access->load('issuedBy:id,name');
        });

        $target = User::query()->findOrFail($access->target_user_id);
        $request->session()->regenerate();
        Auth::login($target);
        $request->session()->put('impersonation', [
            'issued_by_user_id' => $access->issued_by_user_id,
            'issued_by_name' => $access->issuedBy?->name ?? 'Administrator',
        ]);

        Log::notice('Temporary user access link redeemed', [
            'issued_by_user_id' => $access->issued_by_user_id,
            'target_user_id' => $target->id,
            'token_record_id' => $access->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('dashboard');
    }

    public function stop(Request $request)
    {
        $impersonation = $request->session()->get('impersonation');
        abort_unless(is_array($impersonation), 403);

        Log::notice('Temporary user access session ended', [
            'issued_by_user_id' => $impersonation['issued_by_user_id'] ?? null,
            'target_user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}