<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestPortalController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomBoardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & SEO Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicController::class, 'home'])->name('public.home');
Route::get('/rooms', [PublicController::class, 'rooms'])->name('public.rooms');
Route::get('/features', [PublicController::class, 'features'])->name('public.features');
Route::get('/contact', [PublicController::class, 'contact'])->name('public.contact');
Route::get('/sitemap.xml', [PublicController::class, 'sitemap'])->name('public.sitemap');
Route::get('/robots.txt', [PublicController::class, 'robots'])->name('public.robots');
Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Token-based Guest Self-Service Portal
|--------------------------------------------------------------------------
*/
Route::get('/guest/portal/{token}', [GuestPortalController::class, 'show'])->name('guest.portal');
Route::post('/guest/portal/{token}/request', [GuestPortalController::class, 'submitRequest'])->name('guest.portal.request');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Management Application
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::post('/switch-hotel', [AuthController::class, 'switchHotel'])->name('hotel.switch');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Live Room Board
    Route::get('/board', [RoomBoardController::class, 'index'])->name('board.index');
    Route::get('/board/status', [RoomBoardController::class, 'status'])->name('board.status');
    Route::post('/board/room/{id}/status', [RoomBoardController::class, 'updateStatus'])->name('board.room.status');

    // Reservations
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/{id}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::post('/reservations/{id}/change-dates', [ReservationController::class, 'changeDates'])->name('reservations.change_dates');
    Route::post('/reservations/{id}/check-in', [ReservationController::class, 'checkIn'])->name('reservations.check_in');
    Route::post('/reservations/{id}/check-out', [ReservationController::class, 'checkOut'])->name('reservations.check_out');
    Route::post('/reservations/{id}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    // Housekeeping
    Route::get('/housekeeping', [HousekeepingController::class, 'index'])->name('housekeeping.index');
    Route::post('/housekeeping/tasks', [HousekeepingController::class, 'store'])->name('housekeeping.store');
    Route::post('/housekeeping/tasks/{id}/status', [HousekeepingController::class, 'updateStatus'])->name('housekeeping.update_status');

    // Analytics (Manager & SuperAdmin)
    Route::middleware(['role:superadmin,manager'])->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/occupancy-data', [AnalyticsController::class, 'occupancyData'])->name('analytics.occupancy');
        Route::get('/analytics/revenue-data', [AnalyticsController::class, 'revenueData'])->name('analytics.revenue');
        Route::get('/analytics/distribution-data', [AnalyticsController::class, 'distributionData'])->name('analytics.distribution');

        // CSV Import
        Route::get('/csv-import', [CsvImportController::class, 'index'])->name('csv.index');
        Route::post('/csv-import', [CsvImportController::class, 'import'])->name('csv.import');
        Route::get('/csv-import/template', [CsvImportController::class, 'downloadTemplate'])->name('csv.template');
    });
});
