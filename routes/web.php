<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SubcategoryController as AdminSubcategoryController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Admin\PurchaseController as AdminPurchaseController;
use App\Http\Controllers\Admin\StockAdjustmentController as AdminStockAdjustmentController;
use App\Http\Controllers\Admin\BulkProductController as AdminBulkProductController;
use App\Http\Controllers\Admin\PaymentMethodController as AdminPaymentMethodController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\StockManagementController as AdminStockManagementController;
use App\Http\Controllers\Admin\SaleProductController as AdminSaleProductController;
use App\Http\Controllers\Admin\ReturnController as AdminReturnController;
use App\Http\Controllers\ReturnRequestController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\AttributeController as AdminAttributeController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\WarehouseController as AdminWarehouseController;
use App\Http\Controllers\Admin\WarehouseStockController as AdminWarehouseStockController;
use App\Http\Controllers\Admin\StockTransferController as AdminStockTransferController;
use App\Http\Controllers\Admin\LowStockController as AdminLowStockController;
use App\Http\Controllers\Admin\BatchController as AdminBatchController;
use App\Http\Controllers\Admin\BarcodeController as AdminBarcodeController;
use App\Http\Controllers\Admin\SkuManagementController as AdminSkuManagementController;
use Illuminate\Support\Facades\Route;

// Frontend Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/category/{category}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{product}/review', [ProductController::class, 'storeReview'])->middleware('auth')->name('products.review');

// Cart Routes
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{cart}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{cart}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
Route::delete('/cart/coupon/remove', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

// Checkout Routes
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Return requests
    Route::get('/orders/{order}/return', [ReturnRequestController::class, 'create'])->name('orders.return.create');
    Route::post('/orders/{order}/return', [ReturnRequestController::class, 'store'])->name('orders.return.store');

    // Wishlist
    Route::post('/wishlist/toggle/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', AdminCategoryController::class);
    // Subcategories — manual routes (bypass slug-based route model binding)
    Route::get('subcategories',           [AdminSubcategoryController::class, 'index'])->name('subcategories.index');
    Route::get('subcategories/create',    [AdminSubcategoryController::class, 'create'])->name('subcategories.create');
    Route::post('subcategories',          [AdminSubcategoryController::class, 'store'])->name('subcategories.store');
    Route::get('subcategories/{id}/edit', [AdminSubcategoryController::class, 'edit'])->name('subcategories.edit');
    Route::put('subcategories/{id}',      [AdminSubcategoryController::class, 'update'])->name('subcategories.update');
    Route::delete('subcategories/{id}',   [AdminSubcategoryController::class, 'destroy'])->name('subcategories.destroy');
    // Bulk upload must be before resource to prevent slug binding conflict
    Route::get('products/bulk-upload', [AdminBulkProductController::class, 'index'])->name('products.bulk-upload');
    Route::get('products/bulk-upload/template', [AdminBulkProductController::class, 'template'])->name('products.bulk-upload.template');
    Route::post('products/bulk-upload', [AdminBulkProductController::class, 'import'])->name('products.bulk-upload.import');
    Route::resource('products', AdminProductController::class);

    // Brands
    Route::resource('brands', AdminBrandController::class);

    // Attributes
    Route::get('attributes', [AdminAttributeController::class, 'index'])->name('attributes.index');
    Route::post('attributes', [AdminAttributeController::class, 'store'])->name('attributes.store');
    Route::patch('attributes/{attribute}', [AdminAttributeController::class, 'update'])->name('attributes.update');
    Route::delete('attributes/{attribute}', [AdminAttributeController::class, 'destroy'])->name('attributes.destroy');
    Route::patch('attributes/{attribute}/toggle', [AdminAttributeController::class, 'toggle'])->name('attributes.toggle');

    // Tags
    Route::resource('tags', AdminTagController::class)->except(['show']);
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::resource('users', AdminUserController::class);
    Route::resource('roles', AdminRoleController::class)->except(['show']);
    Route::resource('coupons', AdminCouponController::class);
    Route::get('reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    // Suppliers
    Route::resource('suppliers', AdminSupplierController::class)->except(['show']);

    // Purchases
    Route::resource('purchases', AdminPurchaseController::class);
    Route::post('purchases/{purchase}/receive', [AdminPurchaseController::class, 'receive'])->name('purchases.receive');

    // Payment methods (admin CRUD)
    Route::resource('payment-methods', AdminPaymentMethodController::class)->except(['show']);
    Route::post('payment-methods/{paymentMethod}/toggle', [AdminPaymentMethodController::class, 'toggle'])->name('payment-methods.toggle');

    // Payments / Transactions
    Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::post('payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->name('payments.verify');
    Route::post('payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('payments.reject');
    Route::post('payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->name('payments.refund');

    // Stock Adjustments (history + manual)
    Route::get('stock-adjustments', [AdminStockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::get('stock-adjustments/create', [AdminStockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('stock-adjustments', [AdminStockAdjustmentController::class, 'store'])->name('stock-adjustments.store');

    // Stock Management (bulk quick update)
    Route::get('stock-management', [AdminStockManagementController::class, 'index'])->name('stock-management.index');
    Route::post('stock-management', [AdminStockManagementController::class, 'update'])->name('stock-management.update');
    Route::patch('products/{product}/quick-stock', [AdminStockManagementController::class, 'quickUpdate'])->name('products.quick-stock');

    // Sale Products
    Route::get('sale-products', [AdminSaleProductController::class, 'index'])->name('sale-products.index');
    Route::patch('sale-products/{product}', [AdminSaleProductController::class, 'update'])->name('sale-products.update');
    Route::post('sale-products/clear-all', [AdminSaleProductController::class, 'clearAll'])->name('sale-products.clear-all');

    // Returns
    Route::get('returns', [AdminReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/{id}', [AdminReturnController::class, 'show'])->name('returns.show');
    Route::post('returns/{id}/approve', [AdminReturnController::class, 'approve'])->name('returns.approve');
    Route::post('returns/{id}/reject', [AdminReturnController::class, 'reject'])->name('returns.reject');

    // Warehouses
    Route::resource('warehouses', AdminWarehouseController::class)->except(['show']);

    // Multi-Warehouse Stock
    Route::get('warehouse-stock', [AdminWarehouseStockController::class, 'index'])->name('warehouse-stock.index');
    Route::post('warehouse-stock', [AdminWarehouseStockController::class, 'update'])->name('warehouse-stock.update');

    // Stock Transfers
    Route::get('stock-transfers', [AdminStockTransferController::class, 'index'])->name('stock-transfers.index');
    Route::get('stock-transfers/create', [AdminStockTransferController::class, 'create'])->name('stock-transfers.create');
    Route::post('stock-transfers', [AdminStockTransferController::class, 'store'])->name('stock-transfers.store');
    Route::get('stock-transfers/{stockTransfer}', [AdminStockTransferController::class, 'show'])->name('stock-transfers.show');
    Route::patch('stock-transfers/{stockTransfer}/dispatch', [AdminStockTransferController::class, 'dispatch'])->name('stock-transfers.dispatch');
    Route::patch('stock-transfers/{stockTransfer}/complete', [AdminStockTransferController::class, 'complete'])->name('stock-transfers.complete');
    Route::patch('stock-transfers/{stockTransfer}/cancel', [AdminStockTransferController::class, 'cancel'])->name('stock-transfers.cancel');

    // Low Stock Alerts
    Route::get('low-stock', [AdminLowStockController::class, 'index'])->name('low-stock.index');
    Route::patch('low-stock/{product}/threshold', [AdminLowStockController::class, 'updateThreshold'])->name('low-stock.threshold');

    // Batch / Lot Management
    Route::resource('batches', AdminBatchController::class)->except(['show']);

    // Barcode Management
    Route::get('barcodes', [AdminBarcodeController::class, 'index'])->name('barcodes.index');
    Route::post('barcodes/bulk-update', [AdminBarcodeController::class, 'bulkUpdate'])->name('barcodes.bulk-update');
    Route::patch('barcodes/{product}', [AdminBarcodeController::class, 'update'])->name('barcodes.update');

    // SKU Management
    Route::get('sku-management', [AdminSkuManagementController::class, 'index'])->name('sku-management.index');
    Route::post('sku-management', [AdminSkuManagementController::class, 'update'])->name('sku-management.update');
    Route::post('sku-management/generate', [AdminSkuManagementController::class, 'generate'])->name('sku-management.generate');

});

require __DIR__.'/auth.php';
