<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleIdTokenVerifier
{
    /**
     * Verify a Google ID token via the tokeninfo endpoint and return its
     * payload (email, name, sub, ...) or null when invalid.
     *
     * @return array<string, mixed>|null
     */
    public function verify(string $idToken): ?array
    {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if ($response->failed()) {
            return null;
        }

        $payload = $response->json();

        if (($payload['aud'] ?? null) !== config('services.google.client_id')) {
            return null;
        }

        if (($payload['email_verified'] ?? 'false') !== 'true') {
            return null;
        }

        return $payload;
    }
}
