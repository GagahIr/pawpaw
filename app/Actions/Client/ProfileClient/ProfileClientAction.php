<?php

namespace App\Actions\Client\ProfileClient;

use App\Actions\Account\RequestEmailChangeAction;
use App\DTOs\RequestEmailChangeData;
use App\DTOs\UpdateProfileData;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProfileClientAction
{
    public function __construct(private RequestEmailChangeAction $requestEmailChange, private ImageService $imageService) {}

    public function execute(int $userId, UpdateProfileData $data): User
    {
        $user = User::findOrFail($userId);
        if ($data->email !== $user->email) {
            $this->requestEmailChange->validatePassword($user, new RequestEmailChangeData($data->email, $data->emailChangePassword, null));
        }
        $avatarPath = null;
        $oldAvatar = null;
        try {
            $account = DB::transaction(function () use ($userId, $data, &$avatarPath, &$oldAvatar): User {
                $account = User::query()->lockForUpdate()->findOrFail($userId);
                abort_unless($account->canChangeEmail(null), 403);
                if ($data->email !== $account->email) {
                    $this->requestEmailChange->execute($account, new RequestEmailChangeData($data->email, $data->emailChangePassword, null));
                }
                $attributes = $data->profileAttributes();
                if ($data->avatar !== null) {
                    $oldAvatar = $account->avatar;
                    $avatarPath = $this->imageService->uploadAndConvertToWebp($data->avatar, 'avatars');
                    $attributes['avatar'] = $avatarPath;
                }
                $account->update($attributes);

                return $account->refresh();
            });
        } catch (Throwable $exception) {
            if ($avatarPath !== null) {
                $this->imageService->delete($avatarPath);
            }
            throw $exception;
        }
        if ($avatarPath !== null && $oldAvatar !== null) {
            $this->imageService->delete($oldAvatar);
        }

        return $account;
    }
}
