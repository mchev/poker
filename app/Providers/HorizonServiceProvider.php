<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Configure the Horizon authorization services.
     */
    protected function authorization(): void
    {
        Horizon::auth(function (Request $request): bool {
            if (app()->environment(['local', 'testing'])) {
                return true;
            }

            $allowedEmails = config('horizon.allowed_emails', []);

            if ($request->user() !== null && in_array($request->user()->email, $allowedEmails, true)) {
                return true;
            }

            $email = (string) config('horizon.allowed_email');
            $password = (string) config('horizon.password');

            if ($email === '' || $password === '') {
                return false;
            }

            return hash_equals($email, (string) $request->getUser())
                && hash_equals($password, (string) $request->getPassword());
        });
    }
}
