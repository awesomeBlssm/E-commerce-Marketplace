<?php

namespace Tests\Feature;

use App\Models\Auth\UserSession;
use App\Models\Shopper\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $token = 'test-token-'.$user->id;
        UserSession::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHour(),
        ]);

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_customer_can_update_email_full_name_and_phone_and_sync_with_user(): void
    {
        $user = User::factory()->create([
            'email' => 'old_email@example.com',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'full_name' => 'Old Name',
            'phone' => '09123456789',
        ]);

        $response = $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'email' => 'new_email@example.com',
                'full_name' => 'New Name',
                'phone' => '09987654321',
            ],
            $this->authHeaders($user)
        );

        $response->assertOk();
        $response->assertJsonPath('email', 'new_email@example.com');
        $response->assertJsonPath('full_name', 'New Name');
        $response->assertJsonPath('phone', '09987654321');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'email' => 'new_email@example.com',
            'full_name' => 'New Name',
            'phone' => '09987654321',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new_email@example.com',
        ]);
    }

    public function test_cannot_update_email_to_another_users_email(): void
    {
        $otherUser = User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'myemail@example.com',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'full_name' => 'My Name',
        ]);

        $response = $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'email' => 'taken@example.com',
            ],
            $this->authHeaders($user)
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_can_update_profile_without_changing_email(): void
    {
        $user = User::factory()->create([
            'email' => 'keep_email@example.com',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'full_name' => 'Original Name',
            'phone' => '09111111111',
        ]);

        $response = $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'email' => 'keep_email@example.com',
                'full_name' => 'Updated Name Only',
            ],
            $this->authHeaders($user)
        );

        $response->assertOk();
        $response->assertJsonPath('full_name', 'Updated Name Only');
        $response->assertJsonPath('email', 'keep_email@example.com');
    }
}
