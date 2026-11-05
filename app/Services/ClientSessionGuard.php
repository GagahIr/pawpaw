<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;

class ClientSessionGuard extends SessionGuard
{
    /**
     * @param  array{provider: string}  $config
     */
    public static function create(Application $app, string $name, array $config): self
    {
        $guard = new self(
            $name, $app['auth']->createUserProvider($config['provider']), $app['session.store'],
            rehashOnLogin: $app['config']->get('hashing.rehash_on_login', true),
            timeboxDuration: $app['config']->get('auth.timebox_duration', 200000),
            hashKey: $app['config']->get('app.key'),
        );
        $guard->setCookieJar($app['cookie']);
        $guard->setDispatcher($app['events']);
        $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

        return $guard;
    }

    protected function userFromRecaller(mixed $recaller): ?Authenticatable
    {
        $alreadyAttempted = $this->recallAttempted;
        $user = parent::userFromRecaller($recaller);
        if ($user !== null || $alreadyAttempted || ! $this->viaRemember) {
            return $user;
        }

        $user = $this->provider->retrieveByToken($recaller->id(), $recaller->token());
        if ($user instanceof User && $user->password === null
            && filled($user->userClient?->google_id)
            && hash_equals($this->hashPasswordForCookie(null), $recaller->hash())) {
            return $user;
        }

        $this->viaRemember = false;

        return null;
    }
}
