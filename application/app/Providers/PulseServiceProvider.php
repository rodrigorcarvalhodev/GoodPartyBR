<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PulseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Este gate controla o acesso ao /pulse
        Gate::define('viewPulse', function (User $user) {
            // Opção A: Apenas e-mails específicos
            return in_array($user->email, [
                'seu-email@dominio.com',
            ]);

            // Opção B: Baseado em uma coluna 'is_admin' no banco
            // return $user->is_admin;
        });
    }
}
