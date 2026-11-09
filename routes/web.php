<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataCenterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SpeedTestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman publik (tanpa login)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/peta', [MapController::class, 'index'])->name('map');
Route::get('/peta/data', [MapController::class, 'data'])->name('map.data');
Route::get('/analitik', [AnalyticsController::class, 'index'])->name('analytics');
Route::get('/speedtest', [SpeedTestController::class, 'index'])->name('speedtest');
Route::get('/speedtest/ping', [SpeedTestController::class, 'ping'])->name('speedtest.ping');
Route::get('/speedtest/download', [SpeedTestController::class, 'download'])->name('speedtest.download');
Route::post('/speedtest/upload', [SpeedTestController::class, 'upload'])->name('speedtest.upload');
Route::get('/api/regions', [ReportController::class, 'regions'])->name('api.regions');
Route::post('/api/severity-preview', [ReportController::class, 'severityPreview'])->name('api.severity');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register']);
});
Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Area pengguna terautentikasi
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Laporan publik (semua peran)
    Route::get('/laporan/ekspor', [ReportController::class, 'export'])->name('reports.export');
    Route::resource('laporan', ReportController::class)
        ->parameters(['laporan' => 'report'])
        ->names('reports');

    // Proyek (lihat: semua peran)
    Route::get('/proyek', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/proyek/ekspor', [ProjectController::class, 'export'])->name('projects.export');

    // Notifikasi & profil
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/{id}/baca', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::delete('/notifikasi/hapus-dibaca', [NotificationController::class, 'clear'])->name('notifications.clear');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/kata-sandi', [ProfileController::class, 'password'])->name('profile.password');

    /*
    | Staf: Admin & Analis
    */
    Route::middleware('role:admin,analyst')->group(function () {
        Route::get('/verifikasi', [VerificationController::class, 'index'])->name('verification.index');
        Route::post('/verifikasi/{report}/setujui', [VerificationController::class, 'verify'])->name('verification.verify');
        Route::post('/verifikasi/{report}/tolak', [VerificationController::class, 'reject'])->name('verification.reject');
        Route::post('/verifikasi/massal', [VerificationController::class, 'bulk'])->name('verification.bulk');
        Route::post('/laporan/{report}/tautkan-proyek', [ReportController::class, 'linkProject'])->name('reports.linkProject');

        Route::get('/prioritas', [PriorityController::class, 'index'])->name('priorities.index');

        Route::get('/proyek/baru', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/proyek', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/proyek/{project}/ubah', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/proyek/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::post('/proyek/{project}/transisi', [ProjectController::class, 'transition'])->name('projects.transition');
        Route::post('/proyek/{project}/progres', [ProjectController::class, 'progress'])->name('projects.progress');

        Route::get('/indikator/ekspor', [IndicatorController::class, 'export'])->name('indicators.export');
        Route::resource('indikator', IndicatorController::class)
            ->parameters(['indikator' => 'indicator'])
            ->except('show')
            ->names('indicators');

        Route::get('/pusat-data', [DataCenterController::class, 'index'])->name('data.index');
        Route::get('/pusat-data/template/{type}', [DataCenterController::class, 'template'])->name('data.template');
        Route::post('/pusat-data/impor/{type}', [DataCenterController::class, 'import'])->name('data.import');
        Route::get('/pusat-data/ringkasan-cetak', [DataCenterController::class, 'print'])->name('data.print');
        Route::get('/pusat-data/backup-json', [DataCenterController::class, 'backup'])->name('data.backup');

        Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('activity.index');
    });

    Route::get('/proyek/{project}', [ProjectController::class, 'show'])->name('projects.show');

    /*
    | Khusus Administrator
    */
    Route::middleware('role:admin')->group(function () {
        Route::delete('/proyek/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/pengguna/{user}/status', [UserController::class, 'toggle'])->name('users.toggle');
        Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
