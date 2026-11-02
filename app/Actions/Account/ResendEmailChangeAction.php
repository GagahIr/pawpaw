<?php

namespace App\Actions\Account;

use App\DTOs\RequestEmailChangeData;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ResendEmailChangeAction
{
    public function __construct(private RequestEmailChangeAction $requestEmailChange) {}

    public function execute(User $user, string $password, ?int $vendorId = null): void
    {
        $user->refresh();
        abort_unless($user->canChangeEmail($vendorId), 403);
        if (blank($user->pending_email)) {
            throw ValidationException::withMessages(['formData.email' => 'Tidak ada perubahan email yang menunggu konfirmasi.']);
        }
        $this->requestEmailChange->execute($user, new RequestEmailChangeData($user->pending_email, $password, $vendorId));
    }
}
