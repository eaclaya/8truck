<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleIdTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, CreateNewUser $createNewUser): JsonResponse
    {
        $user = $createNewUser->create($request->all());

        return response()->json([
            'token' => $user->createToken($this->deviceName($request))->plainTextToken,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        return response()->json([
            'token' => $user->createToken($this->deviceName($request))->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Native Google Sign-In: the app sends the ID token, we verify it against
     * Google and mint a Sanctum token. New users must include a role (and a
     * phone when registering as a transporter).
     */
    public function google(Request $request, GoogleIdTokenVerifier $verifier): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'role' => ['sometimes', Rule::in(['customer', 'transporter'])],
            'phone' => ['required_if:role,transporter', 'nullable', 'string', 'max:30'],
        ]);

        $payload = $verifier->verify($validated['id_token']);

        if ($payload === null) {
            throw ValidationException::withMessages(['id_token' => __('auth.failed')]);
        }

        $user = User::query()->where('email', $payload['email'])->first();

        if ($user === null) {
            if (! isset($validated['role'])) {
                return response()->json([
                    'message' => __('A role is required to create the account.'),
                    'needs_registration' => true,
                ], 422);
            }

            $user = DB::transaction(function () use ($payload, $validated) {
                $user = User::create([
                    'name' => $payload['name'] ?? $payload['email'],
                    'email' => $payload['email'],
                    'password' => Str::password(32),
                ]);
                $user->markEmailAsVerified();
                $user->assignRole($validated['role']);

                if ($validated['role'] === 'transporter') {
                    $user->transporterProfile()->create(['phone' => $validated['phone']]);
                }

                return $user;
            });
        }

        return response()->json([
            'token' => $user->createToken($this->deviceName($request))->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ok']);
    }

    private function deviceName(Request $request): string
    {
        return (string) $request->input('device_name', 'mobile');
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'is_transporter' => $user->isTransporter(),
        ];
    }
}
