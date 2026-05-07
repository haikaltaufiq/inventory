<?php

use App\Http\Controllers\SpecValuePresetController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PcBuilderController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/auth/login');
Route::post('/api/midtrans/webhook', [MidtransWebhookController::class, 'handle'])->name('midtrans.webhook');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::post('/grid-save', [ProductController::class, 'gridSave'])->name('grid-save');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::get('/spec-options', [ProductController::class, 'specOptions'])->name('spec-options');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Customers
    |--------------------------------------------------------------------------
    */
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Supplier
    |--------------------------------------------------------------------------
    */
    Route::prefix('supplier')->name('supplier.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('/create', [SupplierController::class, 'create'])->name('create');
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
    });
    Route::get('/api/products/{product}/suppliers', [TransactionController::class, 'getSuppliersByProduct'])
        ->name('products.suppliers');
    /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Transactions
    |--------------------------------------------------------------------------
    */
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/create', [TransactionController::class, 'create'])->name('create');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
        Route::get('/{transaction}/document/{type}', [TransactionController::class, 'downloadDocument'])->name('document');
        Route::post('/{transaction}/desc', [TransactionController::class, 'updateDesc'])->name('updateDesc');
        Route::post('/{transaction}/warranty', [TransactionController::class, 'updateWarranty'])->name('updateWarranty');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('destroy');

        Route::get('/{transaction}/snap-token', [TransactionController::class, 'getSnapToken'])->name('transactions.snap-token');
        Route::get('/{transaction}/payment-status', [TransactionController::class, 'checkPaymentStatus'])->name('transactions.payment-status');
    });

    /*
    |--------------------------------------------------------------------------
    | Report
    |--------------------------------------------------------------------------
    */
    Route::get('/report', [TransactionController::class, 'report'])->name('report');
    Route::get('/report/download', [TransactionController::class, 'downloadReport'])->name('report.download');
    Route::get('/report-product', [ProductController::class, 'reportProduct'])->name('report.product');
    Route::get('/report-product/download', [ProductController::class, 'downloadProductReport'])->name('report.product.download');

    /*
    |--------------------------------------------------------------------------
    | pc-builder
    |--------------------------------------------------------------------------
    */
    Route::get('/pc-builder', [PcBuilderController::class, 'index'])->name('pc-builder.index');
    Route::get('/pc-builder/compatible', [PcBuilderController::class, 'getCompatible'])->name('pc-builder.compatible');

    /*
    |--------------------------------------------------------------------------
    | Setting
    |--------------------------------------------------------------------------
    */
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/midtrans', [SettingsController::class, 'saveMidtrans'])->name('settings.midtrans.save');
    Route::get('/settings/midtrans-test', [SettingsController::class, 'testConnection'])->name('settings.midtrans.test');
    
    /*
    |--------------------------------------------------------------------------
    | Spec Value Presets
    |--------------------------------------------------------------------------
    */
    Route::prefix('spec-presets')->name('spec-presets.')->group(function () {
        Route::get('/',          [SpecValuePresetController::class, 'index'])->name('index');
        Route::post('/',         [SpecValuePresetController::class, 'store'])->name('store');
        Route::post('/import',   [SpecValuePresetController::class, 'importFromProducts'])->name('import');
        Route::delete('/{specValuePreset}', [SpecValuePresetController::class, 'destroy'])->name('destroy');
    });
});
