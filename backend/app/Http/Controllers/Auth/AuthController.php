<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\UserSession;
use App\Models\Shopper\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'full_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'accepts_marketing' => ['sometimes', 'boolean'],
        ]);

        $result = DB::transaction(function () use ($validated, $request): array {
            $user = User::create([
                'email' => $validated['email'],
                'password_hash' => Hash::make($validated['password']),
                'status' => 'active',
                'type' => 'user',
            ]);

            $customer = Customer::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'full_name' => $validated['full_name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'accepts_marketing' => $validated['accepts_marketing'] ?? false,
                'created_at' => now(),
            ]);

            return [$user, $customer, $this->issueToken($user, $request)];
        });

        return response()->json([
            'user' => $result[0]->load('customer'),
        ], 201)->withCookie($this->authCookie($result[2]));
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password_hash)) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 422);
        }

        if (in_array($user->status, ['suspended', 'deactivated'], true)) {
            return response()->json(['message' => 'This account cannot sign in.'], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $this->issueToken($user, $request);

        return response()->json([
            'user' => $user->load('customer'),
        ])->withCookie($this->authCookie($token));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('customer'));
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update([
            'avatar_path' => $request->file('avatar')->store('avatars', 'public'),
        ]);

        return response()->json($user->fresh()->load('customer'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('auth_session')?->forceFill([
            'revoked_at' => now(),
        ])->save();

        return response()->json(['message' => 'Signed out successfully.'])
            ->withCookie(cookie()->forget(config('auth.api_cookie')));
    }

    private function authCookie(string $token): \Symfony\Component\HttpFoundation\Cookie
    {
        return cookie(
            config('auth.api_cookie'),
            $token,
            config('auth.api_cookie_minutes'),
            config('session.path', '/'),
            config('session.domain'),
            (bool) config('session.secure', false),
            true,
            false,
            config('session.same_site', 'lax'),
        );
    }

    private function issueToken(User $user, Request $request): string
    {
        $plainToken = Str::random(80);

        UserSession::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return $plainToken;
    }
}
