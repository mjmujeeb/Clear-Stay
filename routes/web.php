<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingAnalyticsController;
use App\Http\Controllers\BulkEditorController;
use App\Http\Controllers\ChannelManagerController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceTrustController;
use App\Http\Controllers\EventTraceController;
use App\Http\Controllers\GuestPortalController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\InventoryReconciliationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\PartnerAssuranceController;
use App\Http\Controllers\PaymentReconciliationController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RateStudioController;
use App\Http\Controllers\ReadinessCentreController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomBoardController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\SecurityGovernanceController;
use App\Http\Controllers\SimpleModeController;
use App\Http\Controllers\StaffGovernanceController;
use App\Http\Controllers\TwoFactorController;
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
Route::post('/contact', [PublicController::class, 'submitContact'])->name('public.contact.post');
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
| Authentication & 2FA Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/login/2fa', [TwoFactorController::class, 'show'])->name('login.2fa');
Route::post('/login/2fa', [TwoFactorController::class, 'verify'])->name('login.2fa.post');
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

    // Management & Cybersecurity (Manager & SuperAdmin)
    Route::middleware(['role:superadmin,manager'])->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/occupancy-data', [AnalyticsController::class, 'occupancyData'])->name('analytics.occupancy');
        Route::get('/analytics/revenue-data', [AnalyticsController::class, 'revenueData'])->name('analytics.revenue');
        Route::get('/analytics/distribution-data', [AnalyticsController::class, 'distributionData'])->name('analytics.distribution');

        // CSV Import
        Route::get('/csv-import', [CsvImportController::class, 'index'])->name('csv.index');
        Route::post('/csv-import', [CsvImportController::class, 'import'])->name('csv.import');
        Route::get('/csv-import/template', [CsvImportController::class, 'downloadTemplate'])->name('csv.template');

        // Cybersecurity & Threat Intelligence Center
        Route::get('/security', [SecurityController::class, 'index'])->name('security.index');
        Route::post('/security/block-ip', [SecurityController::class, 'blockIp'])->name('security.block_ip');
        Route::delete('/security/unblock-ip/{id}', [SecurityController::class, 'unblockIp'])->name('security.unblock_ip');
        Route::post('/security/2fa/setup', [SecurityController::class, 'setup2fa'])->name('security.2fa.setup');
        Route::post('/security/2fa/disable', [SecurityController::class, 'disable2fa'])->name('security.2fa.disable');
    });

    /*
    |--------------------------------------------------------------------------
    | ClearStay 2.0 Enterprise Platform Routes
    |--------------------------------------------------------------------------
    */
    // Simple Mode Toggle
    Route::post('/simple-mode/toggle', [SimpleModeController::class, 'toggle'])->name('simple_mode.toggle');

    // Pillar 1: Enterprise Unified Overview
    Route::get('/overview', [OverviewController::class, 'index'])->name('overview.index');
    Route::get('/overview-dashboard', [OverviewController::class, 'index'])->name('overview');

    // Pillar 2 & 3: Multi-Channel Distribution & Centralized Outbox Sync
    Route::get('/channels', [ChannelManagerController::class, 'index'])->name('channels.index');
    Route::post('/channels/{channel}/connect', [ChannelManagerController::class, 'connect'])->name('channels.connect');
    Route::post('/channels/{channel}/disconnect', [ChannelManagerController::class, 'disconnect'])->name('channels.disconnect');
    Route::post('/channels/{channel}/sync', [ChannelManagerController::class, 'syncNow'])->name('channels.sync');
    Route::post('/channels/sync-all', [ChannelManagerController::class, 'syncAll'])->name('channels.sync_all');
    Route::post('/channels/{channel}/simulate-error', [ChannelManagerController::class, 'simulateError'])->name('channels.simulate_error');
    Route::post('/channels/retry-failed', [ChannelManagerController::class, 'retryFailed'])->name('channels.retry_failed');

    // Bulk Change Studio & 1-Click Rollback
    Route::get('/bulk-editor', [BulkEditorController::class, 'index'])->name('bulk_editor.index');
    Route::match(['get', 'post'], '/bulk-editor/preview', [BulkEditorController::class, 'preview'])->name('bulk_editor.preview');
    Route::post('/bulk-editor/commit', [BulkEditorController::class, 'commit'])->name('bulk_editor.commit');
    Route::post('/bulk-editor/rollback/{batch}', [BulkEditorController::class, 'rollback'])->name('bulk_editor.rollback');
    Route::post('/bulk-editor/seed-demo', [BulkEditorController::class, 'seedDemoInventory'])->name('bulk_editor.seed_demo');

    // Pillar 4: Smart Rate Studio & Calendar Intelligence
    Route::get('/rate-studio', [RateStudioController::class, 'index'])->name('rates.index');
    Route::post('/rate-studio/plans', [RateStudioController::class, 'storePlan'])->name('rates.store_plan');
    Route::post('/rate-studio/events', [RateStudioController::class, 'storeEvent'])->name('rates.store_event');
    Route::post('/rate-studio/scan-parity', [RateStudioController::class, 'scanParity'])->name('rates.scan_parity');

    // Pillar 5: Partner Assurance & Integration Quality Lab
    Route::get('/partner-assurance', [PartnerAssuranceController::class, 'index'])->name('partner_assurance.index');
    Route::get('/partner-assurance/{partner}', [PartnerAssuranceController::class, 'show'])->name('partner_assurance.show');
    Route::post('/partner-assurance/{partner}/contract', [PartnerAssuranceController::class, 'uploadContract'])->name('partner_assurance.upload_contract');
    Route::post('/partner-assurance/{partner}/validate-schema', [PartnerAssuranceController::class, 'validateSchema'])->name('partner_assurance.validate_schema');
    Route::post('/partner-assurance/{partner}/run-scenarios', [PartnerAssuranceController::class, 'runScenarios'])->name('partner_assurance.run_scenarios');

    // Pillar 8: EventTrace & Observability Hub
    Route::get('/event-traces', [EventTraceController::class, 'index'])->name('event_trace.index');
    Route::get('/event-traces/{trace}', [EventTraceController::class, 'show'])->name('event_trace.show');
    Route::post('/event-traces/{trace}/replay', [EventTraceController::class, 'replay'])->name('event_trace.replay');

    // Pillar 9: Partner Readiness Centre
    Route::get('/readiness', [ReadinessCentreController::class, 'index'])->name('readiness.index');
    Route::get('/readiness/{report}', [ReadinessCentreController::class, 'show'])->name('readiness.show');
    Route::post('/readiness/{partner}/generate', [ReadinessCentreController::class, 'generate'])->name('readiness.generate');
    Route::get('/readiness/{report}/export-csv', [ReadinessCentreController::class, 'exportCsv'])->name('readiness.export_csv');

    // Pillar 6: Hybrid Access Governance & Manager Employee Controls
    Route::get('/staff-governance', [StaffGovernanceController::class, 'index'])->name('staff.index');
    Route::get('/staff-governance/{user}/edit', [StaffGovernanceController::class, 'edit'])->name('staff.edit');
    Route::post('/staff-governance/{user}/role', [StaffGovernanceController::class, 'updateRole'])->name('staff.update_role');
    Route::post('/staff-governance/{user}/permissions', [StaffGovernanceController::class, 'updatePermissions'])->name('staff.update_permissions');
    Route::post('/staff-governance/{user}/expiry', [StaffGovernanceController::class, 'updateExpiry'])->name('staff.update_expiry');
    Route::post('/staff-governance/{user}/suspend', [StaffGovernanceController::class, 'suspend'])->name('staff.suspend');
    Route::post('/staff-governance/{user}/reactivate', [StaffGovernanceController::class, 'reactivate'])->name('staff.reactivate');
    Route::post('/staff-governance/{user}/revoke', [StaffGovernanceController::class, 'revoke'])->name('staff.revoke');

    // Device Trust Management
    Route::get('/device-trust', [DeviceTrustController::class, 'index'])->name('device_trust.index');
    Route::post('/device-trust/register-current', [DeviceTrustController::class, 'registerCurrent'])->name('device_trust.register_current');
    Route::post('/device-trust/{device}/revoke', [DeviceTrustController::class, 'revoke'])->name('device_trust.revoke');

    // Pillar 7: Security Intelligence & AI Governance
    Route::get('/security-governance', [SecurityGovernanceController::class, 'index'])->name('security_governance.index');
    Route::post('/security-governance/recommendations/{recommendation}', [SecurityGovernanceController::class, 'updateRecommendation'])->name('security_governance.update_recommendation');
    Route::post('/security-governance/ai-review', [SecurityGovernanceController::class, 'generateAiReview'])->name('security_governance.generate_ai_review');

    // TEMAIRAZU BA+ Style Booking Analytics & Historical Pace
    Route::get('/booking-analytics', [BookingAnalyticsController::class, 'index'])->name('booking_analytics.index');
    Route::post('/booking-analytics/capture', [BookingAnalyticsController::class, 'capture'])->name('booking_analytics.capture');
    Route::get('/booking-analytics/export-csv', [BookingAnalyticsController::class, 'exportCsv'])->name('booking_analytics.export_csv');

    // TEMAIRAZU-Oriented Reservation-to-Payment Reconciliation (Agoda & NTT DATA Style)
    Route::get('/payment-reconciliation', [PaymentReconciliationController::class, 'index'])->name('payment_reconciliation.index');
    Route::post('/payment-reconciliation/reconcile', [PaymentReconciliationController::class, 'reconcile'])->name('payment_reconciliation.reconcile');
    Route::post('/payment-reconciliation/{reconciliation}/resolve', [PaymentReconciliationController::class, 'resolve'])->name('payment_reconciliation.resolve');
    Route::get('/payment-reconciliation/export-csv', [PaymentReconciliationController::class, 'exportCsv'])->name('payment_reconciliation.export_csv');

    // Central Inventory Reconciliation (PMS vs OTAs Discrepancy Auditing)
    Route::get('/inventory-reconciliation', [InventoryReconciliationController::class, 'index'])->name('inventory_reconciliation.index');
    Route::post('/inventory-reconciliation/audit', [InventoryReconciliationController::class, 'audit'])->name('inventory_reconciliation.audit');
    Route::post('/inventory-reconciliation/{reconciliation}/resolve', [InventoryReconciliationController::class, 'resolve'])->name('inventory_reconciliation.resolve');

    // Executive Reports & CSV Exports
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/sync-logs', [ReportsController::class, 'exportSyncLogsCsv'])->name('reports.export_sync_logs');
    Route::get('/reports/export/audit-logs', [ReportsController::class, 'exportAuditLogsCsv'])->name('reports.export_audit_logs');
    Route::get('/reports/export/inventory', [ReportsController::class, 'exportInventoryCsv'])->name('reports.export_inventory');
});
