<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Employee;
use App\Observers\UserObserver;
use App\Observers\EmployeeObserver;
use App\Services\PdfCompressionService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PdfCompressionService::class, function ($app) {
            return new PdfCompressionService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        User::observe(UserObserver::class);
        Employee::observe(EmployeeObserver::class);
        \App\Models\Ticket::observe(\App\Observers\TicketObserver::class);
    }
}
