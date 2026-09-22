<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InventoryAuditController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - SweetNest OIMS
|--------------------------------------------------------------------------
*/

// Root Redirect
Route::get('/', function () {
    return redirect()->route('login');
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Customer, Order & Payment Management (Owner & Staff)
    Route::middleware('role:owner,staff')->group(function () {
        Route::resource('customers', CustomerController::class);
        Route::resource('orders', OrderController::class);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');

        // Payment Management
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('orders/{order}/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
    });

    // Production Management (Owner & Staff)
    Route::middleware('role:owner,staff')->group(function () {
        Route::get('productions', [ProductionController::class, 'index'])->name('productions.index');
        Route::get('productions/create', [ProductionController::class, 'create'])->name('productions.create');
        Route::post('productions', [ProductionController::class, 'store'])->name('productions.store');
        Route::get('productions/{production}', [ProductionController::class, 'show'])->name('productions.show');
        Route::patch('productions/{production}/status', [ProductionController::class, 'updateStatus'])->name('productions.updateStatus');
        Route::post('productions/{production}/ingredients', [ProductionController::class, 'logIngredient'])->name('productions.logIngredient');
        Route::delete('productions/{production}/ingredients/{productionIngredient}', [ProductionController::class, 'removeIngredient'])->name('productions.removeIngredient');
    });

    // Inventory & Audit Management (Owner & Staff)
    Route::middleware('role:owner,staff')->group(function () {
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
        Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::get('inventory/{ingredient}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('inventory/{ingredient}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::get('inventory/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stockIn');
        Route::post('inventory/stock-in', [InventoryController::class, 'storeStockIn'])->name('inventory.storeStockIn');
        Route::get('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::post('inventory/adjust', [InventoryController::class, 'storeAdjust'])->name('inventory.storeAdjust');
        Route::get('inventory/transactions', [InventoryController::class, 'transactions'])->name('inventory.transactions');

        // Physical Count Audits (Twice-Daily Shift Counts)
        Route::get('audits', [InventoryAuditController::class, 'index'])->name('audits.index');
        Route::get('audits/create', [InventoryAuditController::class, 'create'])->name('audits.create');
        Route::post('audits', [InventoryAuditController::class, 'store'])->name('audits.store');
        Route::get('audits/{audit}', [InventoryAuditController::class, 'show'])->name('audits.show');
    });

    // Owner-Only: Reports Module & User Accounts Administration (Document Ch. 3 Obj #5, Security Plan & Table 9)
    Route::middleware('role:owner')->group(function () {
        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/orders', [ReportController::class, 'orders'])->name('reports.orders');
        Route::get('reports/consumption', [ReportController::class, 'consumption'])->name('reports.consumption');
        Route::get('reports/inventory-history', [ReportController::class, 'inventoryHistory'])->name('reports.inventoryHistory');

        // User Accounts
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
        Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
    });
});
