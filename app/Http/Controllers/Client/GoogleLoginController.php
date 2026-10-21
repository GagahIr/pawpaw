<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\LoginGoogleClientAction;
use App\Actions\Client\RegisterGoogleClientAction;
use App\DTOs\GoogleOAuthContextData;
use App\Enums\SystemMessage;
use App\Http\Controllers\Controller;
use App\Services\GoogleOAuthService;
use App\Support\ClientFlash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GoogleLoginController extends Controller
{
    public function __construct(private GoogleOAuthService $googleOAuthService) {}

    public function redirect(Request $request): RedirectResponse
    {
        $registration = $request->query('intent') === 'register';
        if (! $this->googleOAuthService->configured()) {
            return $this->failure('Login Google belum dikonfigurasi. Silakan gunakan email/nomor telepon dan password.', $registration);
        }

        if ($registration) {
            $request->session()->forget('google_client_registration');
            if (! $request->session()->pull('google_registration_terms', false)) {
                return $this->failure('Setujui Syarat & Ketentuan serta Kebijakan Privasi sebelum mendaftar dengan Google.', true);
            }
        }

        $context = $this->googleOAuthService->createContext($registration, $request->boolean('remember'));
        $request->session()->put('client_google_oauth', $context->toArray());

        return redirect()->away($this->googleOAuthService->authorizationUrl($context));
    }

    public function callback(Request $request, LoginGoogleClientAction $action, RegisterGoogleClientAction $registrationAction): RedirectResponse
    {
        $context = GoogleOAuthContextData::fromSession($request->session()->pull('client_google_oauth'));
        $state = $request->query('state');
        $registration = $context?->registration ?? false;

        if ($context === null || ! is_string($state)
            || ! hash_equals($context->state, $state) || now()->timestamp - $context->startedAt > 600) {
            return $this->failure('Sesi login Google tidak valid atau sudah kedaluwarsa. Silakan coba lagi.', $registration);
        }

        if ($request->has('error')) {
            return $this->failure('Login Google dibatalkan atau izin akses tidak diberikan.', $registration);
        }

        if (! $this->googleOAuthService->configured()) {
            return $this->failure('Login Google belum dikonfigurasi. Silakan gunakan email/nomor telepon dan password.', $registration);
        }

        try {
            $validated = $request->validate(['code' => ['required', 'string', 'max:4096']]);
            $data = $this->googleOAuthService->clientData($validated['code'], $context);

            if ($registration) {
                $registrationAction->execute($data, $data->name, $context->termsAccepted);

                ClientFlash::registrationSuccess(SystemMessage::CLIENT_REGISTER_SUCCESS->value);

                return redirect()->route('client.dashboard');
            }

            $action->execute($data);
            if ($action->registered) {
                ClientFlash::registrationSuccess(SystemMessage::CLIENT_REGISTER_SUCCESS->value);

                return redirect()->route('client.dashboard');
            }
        } catch (ValidationException $e) {
            return $this->failure($e->validator->errors()->first(), $registration);
        } catch (\Throwable $e) {
            Log::warning('Login Google gagal.', ['exception' => $e::class]);

            return $this->failure(SystemMessage::ERROR_SYSTEM->value, $registration);
        }

        ClientFlash::loginSuccess(SystemMessage::CLIENT_LOGIN_SUCCESS->value);

        return redirect()->route('client.dashboard');
    }

    private function failure(string $message, bool $registration = false): RedirectResponse
    {
        if ($registration) {
            ClientFlash::registrationError($message);

            return redirect()->route('client.register');
        }

        ClientFlash::loginError($message);

        return redirect()->route('client.login');
    }
}
