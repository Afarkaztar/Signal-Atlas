<?php

namespace App\Providers;

use App\Models\Report;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Paginator::defaultView('partials.pagination');

        // Data global untuk layout: jumlah notifikasi & antrean verifikasi
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            $view->with('navCounts', [
                'unread' => $user ? $user->unreadNotifications()->count() : 0,
                'pending' => $user && $user->isStaff() ? Report::where('status', 'pending')->count() : 0,
            ]);
        });
    }
}
