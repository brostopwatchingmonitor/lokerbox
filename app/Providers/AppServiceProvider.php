<?php

namespace App\Providers;

use App\Models\Passkey;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passkeys\Passkeys;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Repositories\TransactionRepositoryInterface::class, \App\Repositories\TransactionRepository::class);
        $this->app->singleton(\App\Repositories\LockerStationRepositoryInterface::class, \App\Repositories\LockerStationRepository::class);
        $this->app->singleton(\App\Services\Payment\MidtransServiceInterface::class, \App\Services\Payment\MidtransService::class);
        $this->app->singleton(\App\Services\IoT\ESP32ServiceInterface::class, \App\Services\IoT\ESP32Service::class);
        $this->app->singleton(\App\Services\LockerRentalService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Passkeys::usePasskeyModel(Passkey::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
