<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\AttributeController as AdminAttributeController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\BarcodeController as AdminBarcodeController;
use App\Http\Controllers\Admin\BatchController as AdminBatchController;
use App\Http\Controllers\Admin\BlogCategoryController as AdminBlogCategoryController;
use App\Http\Controllers\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\BulkProductController as AdminBulkProductController;
use App\Http\Controllers\Admin\BundleController as AdminBundleController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\CrossSellController as AdminCrossSellController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailCampaignController as AdminEmailCampaignController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\FlashSaleController as AdminFlashSaleController;
use App\Http\Controllers\Admin\LowStockController as AdminLowStockController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\NotificationSeederController as AdminNotificationSeederController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PaymentMethodController as AdminPaymentMethodController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\PromoCodeController as AdminPromoCodeController;
use App\Http\Controllers\Admin\PurchaseController as AdminPurchaseController;
use App\Http\Controllers\Admin\ReferralProgramController as AdminReferralProgramController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReturnController as AdminReturnController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SaleProductController as AdminSaleProductController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SkuManagementController as AdminSkuManagementController;
use App\Http\Controllers\Admin\StockAdjustmentController as AdminStockAdjustmentController;
use App\Http\Controllers\Admin\StockManagementController as AdminStockManagementController;
use App\Http\Controllers\Admin\StockReasonController as AdminStockReasonController;
use App\Http\Controllers\Admin\StockTransferController as AdminStockTransferController;
use App\Http\Controllers\Admin\SubcategoryController as AdminSubcategoryController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Admin\WarehouseController as AdminWarehouseController;
use App\Http\Controllers\Admin\WarehouseStockController as AdminWarehouseStockController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerNotificationController;
use App\Http\Controllers\CustomerReferralController;
use App\Http\Controllers\CustomerReviewController;
use App\Http\Controllers\EmailUnsubscribeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccountReturnController;
use App\Http\Controllers\ReturnRequestController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\VendorRegistrationController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

// Search suggest (public)
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

// Blog (public)
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{blogCategory}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{blogPost}', [BlogController::class, 'show'])->name('blog.show');

// Pages (public)
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'sendContact'])->name('contact.send');
Route::get('/pages/{page}', [PageController::class, 'show'])->name('pages.show');

// Newsletter (public)
Route::post('/newsletter/subscribe', [NewsletterSubscriptionController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterSubscriptionController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::get('/email/unsubscribe/{token}', [EmailUnsubscribeController::class, 'unsubscribe'])->name('email.unsubscribe');

// Frontend Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/categories', [ShopController::class, 'categories'])->name('categories.index');
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
Route::post('/cart/points', [CartController::class, 'applyPoints'])->name('cart.points')->middleware('auth');
Route::delete('/cart/points/remove', [CartController::class, 'removePoints'])->name('cart.points.remove')->middleware('auth');

// Checkout Routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/buy-now', [CheckoutController::class, 'buyNow'])->name('checkout.buy-now');

// Guest Order Tracking
Route::get('/track-order', [CheckoutController::class, 'guestTrackForm'])->name('guest.order.track.form');
Route::post('/track-order', [CheckoutController::class, 'guestTrackLookup'])->name('guest.order.track.lookup');
Route::get('/track-order/{order_number}/{token}', [CheckoutController::class, 'guestTrack'])->name('guest.order.track');

Route::middleware('auth')->group(function () {

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Return requests
    Route::get('/orders/{order}/return', [ReturnRequestController::class, 'create'])->name('orders.return.create');
    Route::post('/orders/{order}/return', [ReturnRequestController::class, 'store'])->name('orders.return.store');
    Route::get('/account/returns', [AccountReturnController::class, 'index'])->name('account.returns.index');
    Route::get('/account/returns/{return}', [AccountReturnController::class, 'show'])->name('account.returns.show');

    // Wishlist
    Route::post('/wishlist/toggle/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');

    // Profile (legacy — keep for compatibility)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Customer Account Panel ─────────────────────────────────────────────────
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [AccountController::class, 'profileEdit'])->name('profile');
        Route::patch('/profile', [AccountController::class, 'profileUpdate'])->name('profile.update');
        Route::patch('/password', [AccountController::class, 'passwordUpdate'])->name('password.update');

        // Addresses
        Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::get('/addresses/create', [AddressController::class, 'create'])->name('addresses.create');
        Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::get('/addresses/{address}/edit', [AddressController::class, 'edit'])->name('addresses.edit');
        Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
        Route::patch('/addresses/{address}/default', [AddressController::class, 'setDefault'])->name('addresses.default');

        // Reviews
        Route::get('/reviews', [CustomerReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/{review}/edit', [CustomerReviewController::class, 'edit'])->name('reviews.edit');
        Route::put('/reviews/{review}', [CustomerReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/reviews/{review}', [CustomerReviewController::class, 'destroy'])->name('reviews.destroy');

        // Support Tickets
        Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
        Route::get('/support/create', [SupportTicketController::class, 'create'])->name('support.create');
        Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
        Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');
        Route::patch('/support/{ticket}/close', [SupportTicketController::class, 'close'])->name('support.close');

        // Notifications
        Route::get('/notifications', [CustomerNotificationController::class, 'index'])->name('notifications');
        Route::patch('/notifications/read-all', [CustomerNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::patch('/notifications/{notification}/read', [CustomerNotificationController::class, 'markRead'])->name('notifications.read');
        Route::get('/notifications/preferences', [CustomerNotificationController::class, 'preferences'])->name('notifications.preferences');
        Route::patch('/notifications/preferences', [CustomerNotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');

        // Referral
        Route::get('/referral', [CustomerReferralController::class, 'index'])->name('referral');

        // Security / Login Activity
        Route::get('/security', [SecurityController::class, 'index'])->name('security');
    });

    // Become a Seller
    Route::get('/sell', [VendorRegistrationController::class, 'create'])->name('vendor.apply');
    Route::post('/sell', [VendorRegistrationController::class, 'store'])->name('vendor.apply.store');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Admin search suggest
    Route::get('search/suggest', [SearchController::class, 'adminSuggest'])->name('search.suggest');

    Route::resource('categories', AdminCategoryController::class);
    // Subcategories — manual routes (bypass slug-based route model binding)
    Route::get('subcategories', [AdminSubcategoryController::class, 'index'])->name('subcategories.index');
    Route::get('subcategories/create', [AdminSubcategoryController::class, 'create'])->name('subcategories.create');
    Route::post('subcategories', [AdminSubcategoryController::class, 'store'])->name('subcategories.store');
    Route::get('subcategories/{id}/edit', [AdminSubcategoryController::class, 'edit'])->name('subcategories.edit');
    Route::put('subcategories/{id}', [AdminSubcategoryController::class, 'update'])->name('subcategories.update');
    Route::delete('subcategories/{id}', [AdminSubcategoryController::class, 'destroy'])->name('subcategories.destroy');
    // Bulk upload must be before resource to prevent slug binding conflict
    Route::get('products/bulk-upload', [AdminBulkProductController::class, 'index'])->name('products.bulk-upload');
    Route::get('products/bulk-upload/template', [AdminBulkProductController::class, 'template'])->name('products.bulk-upload.template');
    Route::post('products/bulk-upload', [AdminBulkProductController::class, 'import'])->name('products.bulk-upload.import');
    Route::get('products/bulk-upload/{bulkImport}/status', [AdminBulkProductController::class, 'status'])->name('products.bulk-upload.status');
    Route::get('products/bulk-upload/{bulkImport}/status-data', [AdminBulkProductController::class, 'statusData'])->name('products.bulk-upload.status-data');
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
    Route::post('tags/quick-create', [AdminTagController::class, 'quickCreate'])->name('tags.quick-create');
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::resource('users', AdminUserController::class);
    Route::resource('roles', AdminRoleController::class)->except(['show']);
    Route::resource('coupons', AdminCouponController::class);
    Route::get('reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    // Vendors
    Route::resource('vendors', AdminVendorController::class)->only(['index', 'show']);
    Route::post('vendors/{vendor}/approve', [AdminVendorController::class, 'approve'])->name('vendors.approve');
    Route::post('vendors/{vendor}/reject', [AdminVendorController::class, 'reject'])->name('vendors.reject');
    Route::post('vendors/{vendor}/suspend', [AdminVendorController::class, 'suspend'])->name('vendors.suspend');

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

    // Stock Reasons (manageable list used by Stock Adjustments/Management)
    Route::get('stock-reasons', [AdminStockReasonController::class, 'index'])->name('stock-reasons.index');
    Route::post('stock-reasons', [AdminStockReasonController::class, 'store'])->name('stock-reasons.store');
    Route::put('stock-reasons/{stockReason}', [AdminStockReasonController::class, 'update'])->name('stock-reasons.update');
    Route::delete('stock-reasons/{stockReason}', [AdminStockReasonController::class, 'destroy'])->name('stock-reasons.destroy');
    Route::patch('stock-reasons/{stockReason}/toggle', [AdminStockReasonController::class, 'toggle'])->name('stock-reasons.toggle');

    // Sale Products
    Route::get('sale-products', [AdminSaleProductController::class, 'index'])->name('sale-products.index');
    Route::patch('sale-products/{product}', [AdminSaleProductController::class, 'update'])->name('sale-products.update');
    Route::post('sale-products/clear-all', [AdminSaleProductController::class, 'clearAll'])->name('sale-products.clear-all');

    // Returns
    Route::get('returns', [AdminReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/{id}', [AdminReturnController::class, 'show'])->name('returns.show');
    Route::post('returns/{id}/approve', [AdminReturnController::class, 'approve'])->name('returns.approve');
    Route::post('returns/{id}/reject', [AdminReturnController::class, 'reject'])->name('returns.reject');

    // Support Tickets
    Route::get('support-tickets', [AdminSupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('support-tickets/{ticket}', [AdminSupportTicketController::class, 'show'])->name('support-tickets.show');
    Route::post('support-tickets/{ticket}/reply', [AdminSupportTicketController::class, 'reply'])->name('support-tickets.reply');
    Route::patch('support-tickets/{ticket}/status', [AdminSupportTicketController::class, 'updateStatus'])->name('support-tickets.status');

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

    // Reports & Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [AdminReportController::class, 'index'])->name('index');
        Route::get('/sales', [AdminReportController::class, 'sales'])->name('sales');
        Route::get('/revenue', [AdminReportController::class, 'revenue'])->name('revenue');
        Route::get('/products', [AdminReportController::class, 'products'])->name('products');
        Route::get('/customers', [AdminReportController::class, 'customers'])->name('customers');
        Route::get('/inventory', [AdminReportController::class, 'inventory'])->name('inventory');
        Route::get('/orders', [AdminReportController::class, 'orders'])->name('orders');
        Route::get('/payments', [AdminReportController::class, 'payments'])->name('payments');
        Route::get('/marketing', [AdminReportController::class, 'marketing'])->name('marketing');
        Route::get('/shipping', [AdminReportController::class, 'shipping'])->name('shipping');
        Route::get('/returns', [AdminReportController::class, 'returns'])->name('returns');
        // Excel downloads
        Route::get('/sales/download', [AdminReportController::class, 'downloadSales'])->name('sales.download');
        Route::get('/revenue/download', [AdminReportController::class, 'downloadRevenue'])->name('revenue.download');
        Route::get('/products/download', [AdminReportController::class, 'downloadProducts'])->name('products.download');
        Route::get('/customers/download', [AdminReportController::class, 'downloadCustomers'])->name('customers.download');
        Route::get('/inventory/download', [AdminReportController::class, 'downloadInventory'])->name('inventory.download');
    });

    // Order invoice PDF + fraud re-check
    Route::get('orders/{order}/invoice', [AdminOrderController::class, 'invoice'])->name('orders.invoice');
    Route::patch('orders/{order}/fraud-recheck', [AdminOrderController::class, 'recheckFraud'])->name('orders.fraud-recheck');

    // Audit Logs
    Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');

    // CMS — Blog
    Route::resource('blog/categories', AdminBlogCategoryController::class)->except(['show'])->names([
        'index' => 'blog.categories.index',
        'create' => 'blog.categories.create',
        'store' => 'blog.categories.store',
        'edit' => 'blog.categories.edit',
        'update' => 'blog.categories.update',
        'destroy' => 'blog.categories.destroy',
    ]);
    Route::resource('blog/posts', AdminBlogPostController::class)->except(['show'])->names([
        'index' => 'blog.posts.index',
        'create' => 'blog.posts.create',
        'store' => 'blog.posts.store',
        'edit' => 'blog.posts.edit',
        'update' => 'blog.posts.update',
        'destroy' => 'blog.posts.destroy',
    ]);

    // CMS — Banners
    Route::resource('banners', AdminBannerController::class)->except(['show']);
    Route::patch('banners/{banner}/toggle', [AdminBannerController::class, 'toggle'])->name('banners.toggle');

    // CMS — Pages
    Route::resource('pages', AdminPageController::class)->except(['show']);

    // CMS — FAQs
    Route::get('faqs', [AdminFaqController::class, 'index'])->name('faqs.index');
    Route::post('faqs', [AdminFaqController::class, 'store'])->name('faqs.store');
    Route::put('faqs/{faq}', [AdminFaqController::class, 'update'])->name('faqs.update');
    Route::delete('faqs/{faq}', [AdminFaqController::class, 'destroy'])->name('faqs.destroy');
    Route::patch('faqs/{faq}/toggle', [AdminFaqController::class, 'toggle'])->name('faqs.toggle');

    // Marketing — Overview
    Route::get('marketing', fn () => view('admin.marketing.index'))->name('marketing.index');

    // Marketing — Flash Sales
    Route::resource('flash-sales', AdminFlashSaleController::class)->except(['show']);
    Route::post('flash-sales/{flashSale}/products', [AdminFlashSaleController::class, 'addProduct'])->name('flash-sales.products.add');
    Route::delete('flash-sales/{flashSale}/products/{product}', [AdminFlashSaleController::class, 'removeProduct'])->name('flash-sales.products.remove');

    // Marketing — Promo Code Batches
    Route::get('promo-codes', [AdminPromoCodeController::class, 'index'])->name('promo-codes.index');
    Route::get('promo-codes/create', [AdminPromoCodeController::class, 'create'])->name('promo-codes.create');
    Route::post('promo-codes', [AdminPromoCodeController::class, 'store'])->name('promo-codes.store');
    Route::get('promo-codes/{promoCode}', [AdminPromoCodeController::class, 'show'])->name('promo-codes.show');
    Route::delete('promo-codes/{promoCode}', [AdminPromoCodeController::class, 'destroy'])->name('promo-codes.destroy');
    Route::get('promo-codes/{promoCode}/download', [AdminPromoCodeController::class, 'download'])->name('promo-codes.download');
    Route::patch('promo-codes/{promoCode}/toggle', [AdminPromoCodeController::class, 'toggle'])->name('promo-codes.toggle');

    // Marketing — Bundle Products
    Route::get('bundles', [AdminBundleController::class, 'index'])->name('bundles.index');
    Route::get('bundles/{product}/manage', [AdminBundleController::class, 'manage'])->name('bundles.manage');
    Route::post('bundles/{product}/items', [AdminBundleController::class, 'addItem'])->name('bundles.items.add');
    Route::delete('bundles/{product}/items/{item}', [AdminBundleController::class, 'removeItem'])->name('bundles.items.remove');
    Route::patch('bundles/{product}/items/{item}', [AdminBundleController::class, 'updateItem'])->name('bundles.items.update');

    // Marketing — Cross-Sell / Upsell
    Route::get('cross-sell', [AdminCrossSellController::class, 'index'])->name('cross-sell.index');
    Route::get('cross-sell/{product}/manage', [AdminCrossSellController::class, 'manage'])->name('cross-sell.manage');
    Route::post('cross-sell/{product}', [AdminCrossSellController::class, 'store'])->name('cross-sell.store');
    Route::delete('cross-sell/{product}/{recommendation}', [AdminCrossSellController::class, 'destroy'])->name('cross-sell.destroy');

    // Marketing — Referral Program
    Route::get('referrals', [AdminReferralProgramController::class, 'index'])->name('referrals.index');
    Route::patch('referrals/rewards/{reward}', [AdminReferralProgramController::class, 'updateReward'])->name('referrals.reward');
    Route::get('referrals/settings', [AdminReferralProgramController::class, 'settings'])->name('referrals.settings');
    Route::post('referrals/settings', [AdminReferralProgramController::class, 'updateSettings'])->name('referrals.settings.update');

    // Marketing — Email Campaigns
    Route::resource('email-campaigns', AdminEmailCampaignController::class)->except(['show']);
    Route::get('email-campaigns/{emailCampaign}', [AdminEmailCampaignController::class, 'show'])->name('email-campaigns.show');
    Route::post('email-campaigns/{emailCampaign}/send', [AdminEmailCampaignController::class, 'send'])->name('email-campaigns.send');

    // Marketing — Newsletter Subscribers
    Route::get('newsletter', [AdminNewsletterController::class, 'index'])->name('newsletter.index');
    Route::delete('newsletter/{subscriber}', [AdminNewsletterController::class, 'destroy'])->name('newsletter.destroy');

    // Notifications
    Route::get('notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/settings', [AdminNotificationController::class, 'settings'])->name('notifications.settings');
    Route::get('notifications/logs', [AdminNotificationController::class, 'logs'])->name('notifications.logs');
    Route::get('notifications/templates', [AdminNotificationController::class, 'templates'])->name('notifications.templates');
    Route::get('notifications/templates/create', [AdminNotificationController::class, 'createTemplate'])->name('notifications.templates.create');
    Route::post('notifications/templates', [AdminNotificationController::class, 'storeTemplate'])->name('notifications.templates.store');
    Route::get('notifications/templates/{template}/edit', [AdminNotificationController::class, 'editTemplate'])->name('notifications.templates.edit');
    Route::put('notifications/templates/{template}', [AdminNotificationController::class, 'updateTemplate'])->name('notifications.templates.update');
    Route::delete('notifications/templates/{template}', [AdminNotificationController::class, 'destroyTemplate'])->name('notifications.templates.destroy');
    Route::get('notifications/seed', [AdminNotificationSeederController::class, 'seed'])->name('notifications.seed');

    // Settings
    Route::get('settings', fn () => redirect()->route('admin.settings.show', 'general'))->name('settings');
    Route::get('settings/{group}', [AdminSettingController::class, 'show'])->name('settings.show');
    Route::patch('settings/{group}', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::post('settings/test-email', [AdminSettingController::class, 'testEmail'])->name('settings.test-email');

});

require __DIR__.'/auth.php';
