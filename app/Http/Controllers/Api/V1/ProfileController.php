<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use PasswordValidationRules, ProfileValidationRules;

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            ...$this->profileRules($user->id),
            'phone' => ['sometimes', 'string', 'max:30'],
        ]);

        $user->fill(['name' => $validated['name'], 'email' => $validated['email']])->save();

        if (isset($validated['phone']) && $user->transporterProfile !== null) {
            $user->transporterProfile->update(['phone' => $validated['phone']]);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->transporterProfile?->phone,
            ],
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => $this->passwordRules(),
        ]);

        $request->user()->forceFill(['password' => $validated['password']])->save();

        return response()->json(['message' => 'ok']);
    }
}
