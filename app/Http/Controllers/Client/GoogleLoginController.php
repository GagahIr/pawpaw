<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\LoginGoogleClientAction;
use App\Actions\Client\RegisterGoogleClientAction;
use App\DTOs\GoogleClientData;
use App\Enums\SystemMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleLoginController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $registration = $request->query('intent') === 'register';
        if (! $this->configured()) {
            return $this->failure('Login Google belum dikonfigurasi. Silakan gunakan email/nomor telepon dan password.', $registration);
        }

        if ($registration) {
            $request->session()->forget('google_client_registration');
            if (! $request->session()->pull('google_registration_terms', false)) {
                return $this->failure('Setujui Syarat & Ketentuan serta Kebijakan Privasi sebelum mendaftar dengan Google.', true);
            }
        }

        $state = Str::random(64);
        $verifier = Str::random(64);
        $request->session()->put('client_google_oauth', [
            'state' => $state, 'verifier' => $verifier,
            'remember' => $request->boolean('remember'), 'started_at' => now()->timestamp,
            'intent' => $registration ? 'register' : 'login',
            'terms_accepted' => $registration,
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code', 'scope' => 'openid email profile',
            'state' => $state, 'prompt' => 'select_account',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request, LoginGoogleClientAction $action, RegisterGoogleClientAction $registrationAction): RedirectResponse
    {
        $context = $request->session()->pull('client_google_oauth');
        $state = $request->query('state');
        $registration = is_array($context) && ($context['intent'] ?? null) === 'register';

        if (! is_array($context) || ! is_string($state) || ! isset($context['state'], $context['verifier'], $context['started_at'])
            || ! hash_equals($context['state'], $state) || now()->timestamp - $context['started_at'] > 600) {
            return $this->failure('Sesi login Google tidak valid atau sudah kedaluwarsa. Silakan coba lagi.', $registration);
        }

        if ($request->has('error')) {
            return $this->failure('Login Google dibatalkan atau izin akses tidak diberikan.', $registration);
        }

        if (! $this->configured()) {
            return $this->failure('Login Google belum dikonfigurasi. Silakan gunakan email/nomor telepon dan password.', $registration);
        }

        try {
            $request->validate(['code' => ['required', 'string', 'max:4096']]);
            $token = Http::asForm()->timeout(15)->withOptions(['allow_redirects' => false])
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'redirect_uri' => config('services.google.redirect'),
                    'grant_type' => 'authorization_code', 'code' => $request->query('code'),
                    'code_verifier' => $context['verifier'],
                ])->throw()->json();
            Validator::make($token, ['access_token' => ['required', 'string']])->validate();

            $profile = Http::withToken($token['access_token'])->timeout(15)->withOptions(['allow_redirects' => false])
                ->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
            Validator::make($profile, [
                'sub' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'],
                'email_verified' => ['required', 'boolean'], 'hd' => ['nullable', 'string', 'max:255'],
                'name' => ['nullable', 'string', 'max:255'],
            ])->validate();

            $data = new GoogleClientData(
                googleId: $profile['sub'], email: strtolower(trim($profile['email'])),
                emailVerified: (bool) $profile['email_verified'], hostedDomain: $profile['hd'] ?? null,
                remember: (bool) ($context['remember'] ?? false),
            );

            if ($registration) {
                $registrationAction->execute($data, $profile['name'] ?? '', (bool) ($context['terms_accepted'] ?? false));

                return redirect('/')->with('client_registered', SystemMessage::CLIENT_REGISTER_SUCCESS->value);
            }

            $action->execute($data);
        } catch (ValidationException $e) {
            return $this->failure($e->validator->errors()->first(), $registration);
        } catch (\Throwable $e) {
            Log::warning('Login Google gagal.', ['exception' => $e::class]);

            return $this->failure(SystemMessage::ERROR_SYSTEM->value, $registration);
        }

        return redirect('/')->with('client_logged_in', SystemMessage::CLIENT_LOGIN_SUCCESS->value);
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function failure(string $message, bool $registration = false): RedirectResponse
    {
        if ($registration) {
            return redirect()->route('client.register')->with('client_register_error', $message);
        }

        return redirect()->route('client.login')->with('client_login_error', $message);
    }
}
