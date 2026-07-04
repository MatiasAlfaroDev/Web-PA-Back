<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_register_verify_login_flow(): void
    {
        Mail::fake();

        $this->postJson('/api/register', [
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'ci' => '1.234.567-2',
            'email' => 'ana@test.com',
            'password' => 'Password1!',
        ])->assertCreated();

        $user = User::where('email', 'ana@test.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertSame('12345672', $user->ci); // normalized
        Mail::assertSent(VerificationCodeMail::class);

        // Unverified user can login but cannot use the API.
        $token = $this->postJson('/api/login', [
            'email' => 'ana@test.com',
            'password' => 'Password1!',
        ])->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/courses')->assertForbidden();

        // Wrong code rejected.
        $this->postJson('/api/verify-email', ['email' => 'ana@test.com', 'code' => '000000'])
            ->assertStatus(422);

        // Right code verifies.
        $this->postJson('/api/verify-email', [
            'email' => 'ana@test.com',
            'code' => $user->verification_code,
        ])->assertOk();

        // The guard caches the (stale, unverified) user between in-test requests.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/courses')->assertOk();
        $this->withToken($token)->getJson('/api/profile')->assertOk()
            ->assertJsonPath('email', 'ana@test.com')
            ->assertJsonMissingPath('verification_code');
    }

    public function test_register_rejects_invalid_ci(): void
    {
        $this->postJson('/api/register', [
            'first_name' => 'X',
            'last_name' => 'Y',
            'ci' => '12345671',
            'email' => 'x@test.com',
            'password' => 'Password1!',
        ])->assertStatus(422)->assertJsonValidationErrors('ci');
    }

    public function test_profile_update(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/profile', ['bio' => 'Hola, soy yo.'])
            ->assertOk()
            ->assertJsonPath('bio', 'Hola, soy yo.');
    }
}
