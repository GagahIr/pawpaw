<?php

namespace App\Services;

use App\DTOs\GoogleClientData;
use App\DTOs\GoogleOAuthContextData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GoogleOAuthService
{
    public function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    public function createContext(bool $registration, bool $remember): GoogleOAuthContextData
    {
        return new GoogleOAuthContextData(
            state: Str::random(64),
            verifier: Str::random(64),
            startedAt: now()->timestamp,
            remember: $remember,
            registration: $registration,
            termsAccepted: $registration,
        );
    }

    public function authorizationUrl(GoogleOAuthContextData $context): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code', 'scope' => 'openid email profile',
            'state' => $context->state, 'prompt' => 'select_account',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $context->verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function clientData(string $code, GoogleOAuthContextData $context): GoogleClientData
    {
        $token = Http::asForm()->timeout(15)->withOptions(['allow_redirects' => false])
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code', 'code' => $code,
                'code_verifier' => $context->verifier,
            ])->throw()->json();
        Validator::make($token, ['access_token' => ['required', 'string']])->validate();

        $profile = Http::withToken($token['access_token'])->timeout(15)->withOptions(['allow_redirects' => false])
            ->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
        Validator::make($profile, [
            'sub' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'],
            'email_verified' => ['required', 'boolean'], 'hd' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return new GoogleClientData(
            googleId: $profile['sub'],
            email: strtolower(trim($profile['email'])),
            emailVerified: (bool) $profile['email_verified'],
            hostedDomain: $profile['hd'] ?? null,
            remember: $context->remember,
            name: $profile['name'] ?? '',
        );
    }
}
