<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelEmailChangeAction
{
    public function execute(User $user, ?int $vendorId = null): void
    {
        DB::transaction(function () use ($user, $vendorId): void {
            $account = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($account->canChangeEmail($vendorId), 403);
            $account->forceFill(User::clearedEmailChangeAttributes())->save();
        });
        $user->refresh();
    }
}
