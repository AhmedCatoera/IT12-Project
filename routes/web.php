<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\JobOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PhysicalCountController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SweetNest Order & Inventory Management System
|--------------------------------------------------------------------------
| Two roles: Owner and Staff. Staff can view stock and deduct the ingredients
| used on a job order; only the Owner can add stock (stock in), adjust stock,
| manage products, users and see reports. Nothing is ever deleted: customers
| are deactivated and orders are cancelled.
*/

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post'); // 5 tries per minute, see AuthController
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ---------------- Owner and Staff ----------------

    // Customers (deactivate instead of delete)
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::patch('customers/{customer}/status', [CustomerController::class, 'toggleStatus'])->name('customers.toggleStatus');

    // Orders (cancel instead of delete)
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::patch('orders/{order}/refund', [OrderController::class, 'markRefunded'])->name('orders.markRefunded');
    Route::put('order-items/{item}', [OrderController::class, 'updateItem'])->name('orderItems.update');
    Route::get('order-items/{item}/image', [OrderController::class, 'itemImage'])->name('orderItems.image');

    // Payments
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('orders/{order}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('payments/{payment}/proof', [PaymentController::class, 'proof'])->name('payments.proof');

    // Job orders and the ingredients used on them (stock out)
    Route::get('job-orders', [JobOrderController::class, 'index'])->name('jobOrders.index');
    Route::post('orders/{order}/job-order', [JobOrderController::class, 'store'])->name('jobOrders.store');
    Route::get('job-orders/{jobOrder}', [JobOrderController::class, 'show'])->name('jobOrders.show');
    Route::patch('job-orders/{jobOrder}/status', [JobOrderController::class, 'transition'])->name('jobOrders.transition');
    Route::post('job-orders/{jobOrder}/stock-outs', [StockOutController::class, 'store'])->name('stockOuts.store');

    // Address lookups for the map (OpenStreetMap), limited to the Davao area
    Route::middleware('throttle:40,1')->group(function () {
        Route::get('geo/reverse', [GeoController::class, 'reverse'])->name('geo.reverse');
        Route::get('geo/search', [GeoController::class, 'search'])->name('geo.search');
    });

    // Inventory: view stock levels and history
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/{ingredient}', [InventoryController::class, 'show'])->name('inventory.show');

    // Physical counts: Staff counts, the Owner applies corrections
    Route::get('physical-counts', [PhysicalCountController::class, 'index'])->name('counts.index');
    Route::get('physical-counts/create', [PhysicalCountController::class, 'create'])->name('counts.create');
    Route::post('physical-counts', [PhysicalCountController::class, 'store'])->name('counts.store');
    Route::get('physical-counts/{count}', [PhysicalCountController::class, 'show'])->name('counts.show');

    // ---------------- Owner only ----------------
    Route::middleware('role:owner')->group(function () {
        // Products (price list)
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');

        // Ingredients (add / edit)
        Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::put('inventory/{ingredient}', [InventoryController::class, 'update'])->name('inventory.update');

        // Stock in: one receipt, many ingredients
        Route::get('stock-ins', [StockInController::class, 'index'])->name('stockIns.index');
        Route::get('stock-ins/create', [StockInController::class, 'create'])->name('stockIns.create');
        Route::post('stock-ins', [StockInController::class, 'store'])->name('stockIns.store');
        Route::get('stock-ins/{stockIn}', [StockInController::class, 'show'])->name('stockIns.show');

        // Stock adjustments: spoilage, count corrections, fixes
        Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
        Route::get('stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
        Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
        Route::post('physical-counts/{count}/apply', [PhysicalCountController::class, 'apply'])->name('counts.apply');

        // Fix a wrong ingredient-used line on a job order
        Route::put('stock-out-items/{item}', [StockOutController::class, 'updateItem'])->name('stockOuts.updateItem');
        Route::delete('stock-out-items/{item}', [StockOutController::class, 'destroyItem'])->name('stockOuts.destroyItem');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/collections', [ReportController::class, 'collections'])->name('reports.collections');
        Route::get('reports/job-orders', [ReportController::class, 'jobOrders'])->name('reports.jobOrders');
        Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');

        // User accounts
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
        Route::patch('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
    });
});
