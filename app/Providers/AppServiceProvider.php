<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // SMTP details saved in Admin → System → Site settings override .env.
        \App\Support\SystemSettings::applyMail();

        Gate::before(function (User $user) {
            try {
                return $user->isSuperAdmin() ? true : null;
            } catch (\Throwable $e) {
                return null;
            }
        });
    }
}
