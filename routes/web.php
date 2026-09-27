<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\TenantPortalController;
use App\Http\Controllers\PayMongoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
| Only OWNER and EMPLOYEE can self-register (RegisterRequest enforces this
| at the validation level). Tenants are created by staff — see
| RegisterController's class doc — and only ever use the login route below.
*/
Route::get('/', fn () => view('welcome'));

// PayMongo webhook — no CSRF, no auth (called by PayMongo's servers)
Route::post('/webhooks/paymongo', [PayMongoController::class, 'webhook'])
    ->name('paymongo.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Connectivity test — confirms your server is reachable (remove after testing)
Route::get('/webhooks/paymongo/ping', fn () => response()->json([
    'ok'  => true,
    'url' => url('/webhooks/paymongo'),
    'ts'  => now()->toISOString(),
]))->name('paymongo.ping');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // Password reset (also used as tenant activation)
    Route::get('/forgot-password',          [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password',         [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}',   [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password',          [PasswordResetController::class, 'reset'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Everyone lands here right after login; redirects by role.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Owner + Employee (staff) area ────────────────────────────────────
    Route::middleware('role:owner|employee')->group(function () {

        // Dashboard (both owner and employee share the same controller method)
        Route::get('/owner/dashboard',  [DashboardController::class, 'show'])->name('owner.dashboard');
        Route::get('/staff/dashboard',  [DashboardController::class, 'show'])->name('staff.dashboard');

        // Rooms
        Route::resource('rooms', RoomController::class)->only(['index','create','store','edit','update']);
        Route::get('rooms/{room}/invoice', [InvoiceController::class, 'roomInvoice'])->name('rooms.invoice');

        // Tenants
        Route::resource('tenants', TenantController::class)->only(['index','create','store','show']);

        // Contracts
        Route::resource('contracts', ContractController::class)->only(['index','create','store','show']);

        // Invoices
        Route::get('invoices',                  [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}',        [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('invoices/generate',        [InvoiceController::class, 'generate'])->name('invoices.generate');
        Route::post('invoices/cancel',          [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('invoices/{invoice}/void',  [InvoiceController::class, 'void'])->name('invoices.void')
            ->middleware('role:owner'); // owner-only

        // Payments
        Route::get('payments',          [PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments',         [PaymentController::class, 'store'])->name('payments.store');

        // Meter Readings
        Route::get('meter-readings',    [MeterReadingController::class, 'index'])->name('meter-readings.index');
        Route::post('meter-readings',   [MeterReadingController::class, 'store'])->name('meter-readings.store');
    });

    // ── Owner-only area ──────────────────────────────────────────────────
    Route::middleware('role:owner')->group(function () {
        Route::get('reports',                   [ReportsController::class, 'index'])->name('reports.index');
        Route::get('reports/export-csv',        [ReportsController::class, 'exportCsv'])->name('reports.csv');
        Route::get('reports/export-pdf',        [ReportsController::class, 'exportPdf'])->name('reports.pdf');

        Route::get('employees',                 [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('employees/create',          [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('employees',                [EmployeeController::class, 'store'])->name('employees.store');
        Route::patch('employees/{user}/toggle', [EmployeeController::class, 'toggle'])->name('employees.toggle');

        Route::get('settings',                  [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings/rate',            [SettingsController::class, 'storeRate'])->name('settings.rate');
        Route::post('settings/billing',         [SettingsController::class, 'storeBilling'])->name('settings.billing');

        Route::get('audit-logs',                [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('email-notifications',       [\App\Http\Controllers\EmailNotificationController::class, 'index'])->name('email-notifications.index');
    });

    // ── Tenant-only area ─────────────────────────────────────────────────
    Route::middleware('role:tenant')->prefix('my')->name('tenant.')->group(function () {
        Route::get('dashboard',         [TenantPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('bill',              [TenantPortalController::class, 'bill'])->name('bill');
        Route::get('payments',          [TenantPortalController::class, 'payments'])->name('payments');
        Route::get('deposit',           [TenantPortalController::class, 'deposit'])->name('deposit');
        Route::get('contract',          [TenantPortalController::class, 'contract'])->name('contract');
        Route::post('pay/{invoice}',    [PayMongoController::class, 'createLink'])->name('pay');
    });
});
