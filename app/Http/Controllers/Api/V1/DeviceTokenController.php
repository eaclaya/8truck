<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceTokenController extends Controller
{
    /**
     * Register (or reassign) an FCM device token for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', Rule::in(['android', 'ios'])],
        ]);

        DeviceToken::query()->updateOrCreate(
            ['token' => $validated['token']],
            ['user_id' => $request->user()->id, 'platform' => $validated['platform'] ?? null],
        );

        return response()->json(['message' => 'ok'], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        $request->user()->deviceTokens()->where('token', $validated['token'])->delete();

        return response()->json(['message' => 'ok']);
    }
}
