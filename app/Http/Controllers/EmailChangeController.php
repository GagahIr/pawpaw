<?php

namespace App\Http\Controllers;

use App\Actions\Account\ConfirmEmailChangeAction;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class EmailChangeController extends Controller
{
    public function show(Request $request, User $user, string $token, ConfirmEmailChangeAction $action): Response
    {
        if (! $request->hasValidSignature()) {
            return $this->invalid();
        }
        try {
            $action->ensureValid($user, $token);
        } catch (ValidationException) {
            return $this->invalid();
        }

        return response()->view('account.confirm-email-change', [
            'email' => $user->pending_email,
            'actionUrl' => $request->fullUrl(),
            'success' => false,
            'error' => null,
        ])->header('Referrer-Policy', 'no-referrer')->header('Cache-Control', 'no-store');
    }

    public function confirm(Request $request, User $user, string $token, ConfirmEmailChangeAction $action): Response
    {
        if (! $request->hasValidSignature()) {
            return $this->invalid();
        }
        $vendorId = $user->email_change_vendor_id;
        try {
            $action->execute($user->id, $token);
        } catch (ValidationException $exception) {
            return $this->invalid($exception->validator->errors()->first());
        }
        $profileUrl = $vendorId === null
            ? route('client.profile')
            : route('vendor.admin.my_profile', ['subdomain' => Vendor::findOrFail($vendorId)->slug]);

        return response()->view('account.confirm-email-change', [
            'success' => true, 'error' => null, 'profileUrl' => $profileUrl,
        ])->header('Referrer-Policy', 'no-referrer')->header('Cache-Control', 'no-store');
    }

    private function invalid(?string $message = null): Response
    {
        return response()->view('account.confirm-email-change', [
            'success' => false,
            'error' => $message ?? 'Tautan tidak valid, sudah digunakan, atau kedaluwarsa. Silakan ajukan perubahan email lagi melalui profil.',
        ], 410)->header('Referrer-Policy', 'no-referrer')->header('Cache-Control', 'no-store');
    }
}
