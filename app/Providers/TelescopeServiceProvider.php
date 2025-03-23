<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        // $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) {
            if ($this->app->environment('production')) {
                return $entry->isReportableException() || // Log exceptions
                       $entry->isFailedJob() ||           // Log failed jobs
                       $entry->hasMonitoredTag();         // Log tagged entries
            }
            return true; // Log everything in local
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return $user->role === 'admin';
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->gate();
        
        parent::boot();
    }
}
