<?php

namespace Tests\Browser;

use App\Enums\StaffPosition;
use App\Enums\VendorSubscriptionStatus;
use App\Enums\VendorVerificationStatus;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClient;
use App\Models\Vendor;
use App\Models\VendorStaff;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use RuntimeException;
use Tests\DuskTestCase;

abstract class AuthBrowserTestCase extends DuskTestCase
{
    use DatabaseTruncation;

    protected const PASSWORD = 'Passw0rd123!';

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $database = $app['config']->get('database.connections.sqlite.database');

        if (! $app->environment('testing')
            || $app['config']->get('database.default') !== 'sqlite'
            || realpath($database) !== realpath($app->databasePath('pawpaw-dusk.sqlite'))
            || ! is_file($app->databasePath('pawpaw-dusk.sqlite'))) {
            throw new RuntimeException('Dusk hanya boleh menggunakan APP_ENV=testing dan database/pawpaw-dusk.sqlite. Gunakan .env.dusk.local.example sebelum menjalankan tes.');
        }

        return $app;
    }

    protected function tearDown(): void
    {
        try {
            static::closeAll();
        } finally {
            parent::tearDown();
        }
    }

    protected function createClient(): User
    {
        $role = Role::firstOrCreate(['name' => 'client']);
        $user = User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create([
            'name' => 'Amanda Dealova', 'username' => 'amandadealova',
            'email' => 'amanda@example.com', 'phone_number' => '081234567890',
            'password' => self::PASSWORD, 'role_id' => $role->id,
        ]);
        UserClient::create(['user_id' => $user->id, 'count_fake_booking' => 0]);

        return $user;
    }

    /** @return array{Vendor, User} */
    protected function createMitra(): array
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Klinik Dusk', 'slug' => 'klinik-dusk',
            'email' => 'klinik@example.com', 'contact' => '081216178909',
            'status_verification' => VendorVerificationStatus::Approved,
            'status_subscription' => VendorSubscriptionStatus::Active,
            'subscription_expired_at' => now()->addMonth(),
        ]);
        $role = Role::firstOrCreate(['name' => 'owner']);
        $owner = User::factory()->afterMaking(function (User $user): void {
            unset($user->email_verified_at);
        })->create([
            'name' => 'Amanda Bella Safira', 'email' => 'owner@example.com',
            'phone_number' => '08292417484', 'password' => self::PASSWORD, 'role_id' => $role->id,
        ]);
        VendorStaff::create(['user_id' => $owner->id, 'vendor_id' => $vendor->id, 'position' => StaffPosition::Owner]);

        return [$vendor, $owner];
    }

    protected function mitraUrl(Vendor $vendor, string $routeName = 'vendor.login'): string
    {
        return route($routeName, ['subdomain' => $vendor->slug]);
    }

    protected function open(Browser $browser, string $url): void
    {
        $browser->visit($url)->waitUntil('typeof window.Livewire !== "undefined"');
    }

    protected function field(string $name): string
    {
        return ':is([wire\\:model="'.$name.'"], [wire\\:model\\.blur="'.$name.'"])';
    }

    protected function fill(Browser $browser, string $name, string $value): void
    {
        $browser->type($this->field($name), $value);
    }

    protected function toggle(Browser $browser, string $name): void
    {
        $browser->scrollIntoView('ui-checkbox'.$this->field($name))->click('ui-checkbox'.$this->field($name));
    }

    protected function submit(Browser $browser): void
    {
        $browser->scrollIntoView('button[type="submit"]')->click('button[type="submit"]');
    }

    /** @param list<string> $fields */
    protected function assertRejected(Browser $browser, array $fields, string $path): void
    {
        foreach ($fields as $field) {
            $selector = $this->field($field);
            $browser->waitUsing(8, 100, function () use ($browser, $selector): bool {
                $element = $browser->element($selector);
                if ($element === null) {
                    return false;
                }

                return $element->getAttribute('aria-invalid') === 'true'
                    || $browser->driver->executeScript(
                        'const field = arguments[0]; const error = field.closest("[data-flux-field]")?.querySelector("[data-flux-error]"); return Boolean(error?.textContent.trim()) || (field.checkValidity ? !field.checkValidity() : false);',
                        [$element],
                    );
            }, 'Validasi tidak tampil pada '.$field);
        }
        $browser->assertPathIs($path);
    }

    protected function assertCheckbox(Browser $browser, string $name, bool $checked): void
    {
        $browser->assertAttribute('ui-checkbox'.$this->field($name), 'aria-checked', $checked ? 'true' : 'false');
    }

    protected function assertBrowserUser(Browser $browser, ?User $user, string $guard): void
    {
        $result = $browser->driver->executeAsyncScript(
            'const done = arguments[arguments.length - 1]; fetch("/_dusk/user/" + arguments[0], {credentials: "same-origin"}).then(response => response.json()).then(done).catch(error => done({error: error.message}));',
            [$guard],
        );
        $this->assertSame($user === null ? [] : ['id' => $user->id, 'className' => User::class], $result);
    }

    protected function togglePassword(Browser $browser, string $field): void
    {
        $selector = $this->field($field);
        $element = $browser->element($selector);
        $button = $element->findElement(WebDriverBy::xpath('..'))->findElement(WebDriverBy::cssSelector('button[aria-label]'));
        $button->click();
    }

    /**
     * Reopen a fresh WebDriver with persistent cookies only, as a browser restart would.
     * Session cookies are deliberately not restored, so remember-me is actually exercised.
     */
    protected function restartBrowser(Browser $browser, string $url): Browser
    {
        $cookies = array_values(array_filter($browser->driver->manage()->getCookies(), fn (array $cookie): bool => isset($cookie['expiry'])));
        $driver = $this->driver();
        $reopened = new Browser($driver);
        try {
            $driver->get($url);
            $driver->manage()->deleteAllCookies();
            foreach ($cookies as $cookie) {
                $driver->manage()->addCookie($cookie);
            }
            $reopened->visit($url);
        } catch (\Throwable $exception) {
            $reopened->quit();
            throw $exception;
        }
        $this->beforeApplicationDestroyed(fn () => $reopened->quit());

        return $reopened;
    }
}
