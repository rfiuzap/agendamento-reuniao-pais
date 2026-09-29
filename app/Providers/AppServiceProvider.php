<?php

namespace App\Providers;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Paginator::defaultView('partials.pagination');

        View::composer('*', function ($view) {
            $settings = Setting::allValues();
            $view->with([
                'schoolName' => $settings['school_name'],
                'contactEmail' => $settings['contact_email'],
                'contactPhone' => $settings['contact_phone'],
            ]);
        });
    }
}
