<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pretend to be the SPA so Sanctum starts a session (SANCTUM_STATEFUL_DOMAINS).
     */
    private function fromSpa(): static
    {
        return $this->withHeaders(['Origin' => 'http://localhost:5173', 'Referer' => 'http://localhost:5173/']);
    }

    public function test_register_logs_the_user_in_and_sends_verification_email(): void
    {
        Notification::fake();

        $this->fromSpa()->postJson('/api/register', [
            'name' => 'Anna',
            'email' => 'anna@example.com',
            'password' => 'secret123',
        ])->assertCreated()->assertJsonPath('user.language', 'lv');

        $user = User::firstWhere('email', 'anna@example.com');
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_register_without_spa_session_gives_clear_error_and_creates_no_user(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Anna',
            'email' => 'anna@example.com',
            'password' => 'secret123',
        ])->assertStatus(400);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_with_valid_and_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->fromSpa()->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnauthorized();

        $this->fromSpa()->postJson('/api/login', ['email' => $user->email, 'password' => 'secret123'])->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_forgot_password_sends_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            return str_contains($notification->toMail($user)->actionUrl, '/reset-password?token=');
        });
    }
}
