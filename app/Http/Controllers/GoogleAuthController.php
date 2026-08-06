<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Existing accounts log straight in; new visitors continue to the
     * role-selection step with their Google identity held in the session.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            // Denied grant, expired state, or provider error.
            return to_route('login')->withErrors(['email' => __('auth.failed')]);
        }

        $user = User::query()->where('email', $googleUser->getEmail())->first();

        if ($user !== null) {
            Auth::login($user, remember: true);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        $request->session()->put('google_registration', [
            'name' => $googleUser->getName() ?: $googleUser->getEmail(),
            'email' => $googleUser->getEmail(),
        ]);

        return to_route('google.complete');
    }

    public function complete(Request $request): Response|RedirectResponse
    {
        $pending = $request->session()->get('google_registration');

        if ($pending === null) {
            return to_route('register');
        }

        return Inertia::render('auth/CompleteRegistration', [
            'name' => $pending['name'],
            'email' => $pending['email'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('google_registration');

        if ($pending === null) {
            return to_route('register');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(['customer', 'transporter'])],
            'phone' => ['required_if:role,transporter', 'nullable', 'string', 'max:30'],
        ]);

        $user = DB::transaction(function () use ($pending, $validated) {
            $user = User::query()->firstOrCreate(
                ['email' => $pending['email']],
                ['name' => $pending['name'], 'password' => Str::password(32)],
            );

            if ($user->wasRecentlyCreated) {
                $user->markEmailAsVerified();
                $user->assignRole($validated['role']);

                if ($validated['role'] === 'transporter') {
                    $user->transporterProfile()->create(['phone' => $validated['phone']]);
                }
            }

            return $user;
        });

        $request->session()->forget('google_registration');

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
