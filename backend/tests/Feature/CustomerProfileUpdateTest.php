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
        $token = 'test-token-'.$user->id.'-'.\Illuminate\Support\Str::random(16);
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

        $freshCustomer = Customer::find($customer->id);
        $this->assertNotNull($freshCustomer->full_name_updated_at);
        $this->assertNotNull($freshCustomer->email_updated_at);
        $this->assertNotNull($freshCustomer->phone_updated_at);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new_email@example.com',
        ]);
    }

    public function test_cannot_update_email_to_another_users_email(): void
    {
        User::factory()->create([
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

    public function test_cannot_update_phone_to_another_customers_phone(): void
    {
        $otherUser = User::factory()->create();
        Customer::create([
            'user_id' => $otherUser->id,
            'email' => 'other@example.com',
            'phone' => '09111111111',
        ]);

        $user = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $user->id,
            'email' => 'me@example.com',
            'phone' => '09222222222',
        ]);

        $response = $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'phone' => '09111111111',
            ],
            $this->authHeaders($user)
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    public function test_validates_phone_format(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $user->id,
            'email' => 'me@example.com',
        ]);

        $response = $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'phone' => 'not-a-number',
            ],
            $this->authHeaders($user)
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    public function test_enforces_once_a_month_cooldown_for_profile_updates(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'full_name' => 'Current Name',
            'phone' => '09123456789',
            'full_name_updated_at' => now()->subDays(5),
            'email_updated_at' => now()->subDays(10),
            'phone_updated_at' => now()->subDays(15),
        ]);

        // Attempt to update full_name within 30 days
        $response = $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'full_name' => 'Brand New Name',
            ],
            $this->authHeaders($user)
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['full_name']);
        $this->assertStringContainsString('once a month', $response->json('errors.full_name.0'));

        // Attempt to update email within 30 days
        $responseEmail = $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'email' => 'brandnew@example.com',
            ],
            $this->authHeaders($user)
        );

        $responseEmail->assertStatus(422);
        $responseEmail->assertJsonValidationErrors(['email']);
        $this->assertStringContainsString('once a month', $responseEmail->json('errors.email.0'));

        // Attempt to update phone within 30 days
        $responsePhone = $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'phone' => '09999999999',
            ],
            $this->authHeaders($user)
        );

        $responsePhone->assertStatus(422);
        $responsePhone->assertJsonValidationErrors(['phone']);
        $this->assertStringContainsString('once a month', $responsePhone->json('errors.phone.0'));
    }

    public function test_allows_update_after_30_days(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'full_name' => 'Old Name',
            'phone' => '09123456789',
            'full_name_updated_at' => now()->subDays(31),
            'email_updated_at' => now()->subDays(31),
            'phone_updated_at' => now()->subDays(31),
        ]);

        $response = $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'full_name' => 'Allowed New Name',
                'email' => 'allowed_new@example.com',
                'phone' => '09888888888',
            ],
            $this->authHeaders($user)
        );

        $response->assertOk();
        $response->assertJsonPath('full_name', 'Allowed New Name');
        $response->assertJsonPath('email', 'allowed_new@example.com');
        $response->assertJsonPath('phone', '09888888888');
    }

    public function test_submitting_identical_values_does_not_trigger_cooldown_error(): void
    {
        $user = User::factory()->create([
            'email' => 'keep_email@example.com',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'full_name' => 'Same Name',
            'phone' => '09111111111',
            'full_name_updated_at' => now()->subDays(2),
            'email_updated_at' => now()->subDays(2),
            'phone_updated_at' => now()->subDays(2),
        ]);

        // Submit same name, email, and phone, but toggling marketing
        $response = $this->patchJson(
            "/api/customers/{$customer->id}",
            [
                'email' => 'keep_email@example.com',
                'full_name' => 'Same Name',
                'phone' => '09111111111',
                'accepts_marketing' => true,
            ],
            $this->authHeaders($user)
        );

        $response->assertOk();
        $response->assertJsonPath('accepts_marketing', true);
    }
}
