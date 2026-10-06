<?php

namespace Tests\Feature;

use App\Models\UserImpersonationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_issue_temporary_login_link(): void
    {
        $target = $this->createUser();
        $userWithUserManagementPermission = $this->createUser([], 'control.users');

        $this->actingAs($userWithUserManagementPermission)
            ->post(route('control.users.temporary-login-link', $target))
            ->assertForbidden();

        $this->post(route('control.users.temporary-login-link', $target))->assertRedirect(route('login'));

        $this->assertDatabaseCount('user_impersonation_tokens', 0);
    }

    public function test_admin_link_confirms_then_logs_in_as_target_once_and_can_be_stopped(): void
    {
        $administrator = $this->createAdminUser();
        $target = $this->createUser();

        $response = $this->actingAs($administrator)
            ->from(route('control.users.index'))
            ->post(route('control.users.temporary-login-link', $target));

        $response->assertRedirect(route('control.users.index'));
        $loginUrl = session('login_as_url');
        $this->assertIsString($loginUrl);
        $this->assertStringContainsString('/temporary-user-access/', $loginUrl);
        $plainToken = Str::after($loginUrl, '/temporary-user-access/');

        $tokenRecord = UserImpersonationToken::query()->firstOrFail();
        $this->assertNotSame($plainToken, $tokenRecord->token_hash);
        $this->assertSame(hash('sha256', $plainToken), $tokenRecord->token_hash);

        $this->get(route('user-impersonation.confirm', $plainToken))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/ImpersonationConfirm')
                ->where('target.account', $target->account));

        $this->assertNull($tokenRecord->fresh()->consumed_at);

        $this->post(route('user-impersonation.redeem', $plainToken))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($target);
        $this->assertSame($administrator->id, session('impersonation.issued_by_user_id'));
        $this->assertNotNull($tokenRecord->fresh()->consumed_at);

        $this->post(route('user-impersonation.redeem', $plainToken))->assertNotFound();

        $this->post(route('user-impersonation.stop'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_temporary_link_is_rejected_after_expiry_or_when_target_is_inactive(): void
    {
        $administrator = $this->createAdminUser();
        $inactiveTarget = $this->createUser(['status' => 'deactivated']);

        $this->actingAs($administrator)
            ->post(route('control.users.temporary-login-link', $inactiveTarget))
            ->assertUnprocessable();

        $target = $this->createUser();
        $token = Str::random(64);
        $record = UserImpersonationToken::create([
            'token_hash' => hash('sha256', $token),
            'target_user_id' => $target->id,
            'issued_by_user_id' => $administrator->id,
            'expires_at' => now()->subSecond(),
        ]);

        $this->get(route('user-impersonation.confirm', $token))->assertNotFound();
        $this->post(route('user-impersonation.redeem', $token))->assertNotFound();
        $this->assertNull($record->fresh()->consumed_at);
    }
}