<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\menuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProductSeoMetaController;
use App\Http\Controllers\ProductTaxAffinityController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ProductVariationController;
use App\Http\Controllers\ProductVariationValueController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\WhatsappSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    // ================= AUTH =================
    Route::get('sample', [AuthController::class, 'sample']);
    Route::post('admin-register', [AuthController::class, 'admin_register']);
    Route::post('admin-login', [AuthController::class, 'admin_login']);
    Route::post('user-register', [AuthController::class, 'register']);
    Route::post('user-login', [AuthController::class, 'login']);

    // ================= PASSWORD / OTP =================
    Route::post('forgot-password', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::get('app-logo-settings', [SettingController::class, 'show']);

    Route::post('organization/forgot-password', [AuthController::class, 'OrgsendOtp']);

    Route::get(
        '/razorpay-key',
        [CartController::class, 'razorpayKey']
    );

});

Route::prefix('admin-dashboard')->middleware(['api', 'jwt.auth'])->group(function () {

    // ================= AUTH =================
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::get('/app-logo-settings', [SettingController::class, 'show']);
    Route::post('/app-logo-settings', [SettingController::class, 'update']);

    //===================== SOCIAL MEDIA SETTINGS =====================
    Route::get('/social-media-settings', [SettingController::class, 'show_social_media']);
    Route::post('/social-media-settings', [SettingController::class, 'store_social_media']);

    //===================== PAYMENT GATEWAY SETTINGS =====================

    Route::get('/payment-gateways', [PaymentGatewayController::class, 'show']);
    Route::post('/payment-gateways', [PaymentGatewayController::class, 'store']);
    Route::delete('/payment-gateways', [PaymentGatewayController::class, 'destroy']);

    // Product Variations
    Route::get('/get-variations', [ProductVariationController::class, 'index']);
    Route::post('/add-variation', [ProductVariationController::class, 'store']);
    Route::put('/update-variations/{id}', [ProductVariationController::class, 'update']);
    Route::delete('/delete-variations/{id}', [ProductVariationController::class, 'destroy']);

    // VARIATION VALUES
    Route::get('/get-variations', [ProductVariationValueController::class, 'index']);
    Route::post('/add-variation-value/{variationId}', [ProductVariationValueController::class, 'store']);
    Route::put('/update-variation-value/{id}', [ProductVariationValueController::class, 'update']);
    Route::delete('/delete-variation-value/{id}', [ProductVariationValueController::class, 'destroy']);

    // Whats App Integration VALUES
    Route::get('/whatsapp-settings', [WhatsappSettingController::class, 'show']);
    Route::post('/whatsapp-settings', [WhatsappSettingController::class, 'store']);

    // Coupon Management
    Route::get('/cart/list-coupon', [CouponController::class, 'index']);
    Route::post('/cart/create-coupon', [CouponController::class, 'store']);
    Route::put('/cart/update-coupon/{id}', [CouponController::class, 'update']);
    Route::delete('/cart/delete-coupon/{id}', [CouponController::class, 'destroy']);

    // Category Management
    Route::get('/list-category', [CategoryController::class, 'index']);
    Route::get('/list-category-all', [CategoryController::class, 'index_all']);
    Route::post('/add-category', [CategoryController::class, 'store']);
    Route::post('/update-category/{id}', [CategoryController::class, 'update']);
    Route::delete('/delete-category/{id}', [CategoryController::class, 'destroy']);

    // Brand Management
    Route::get('list-brand', [BrandController::class, 'index']);
    Route::post('add-brand', [BrandController::class, 'store']);
    Route::post('update-brand/{id}', [BrandController::class, 'update']);
    Route::delete('delete-brand/{id}', [BrandController::class, 'destroy']);

    // Product Management
    Route::get('products', [ProductController::class, 'index']);
    Route::get('/product/fetch-products-by-id/{id}', [ProductController::class, 'fetchById']);
    Route::post('create-product', [ProductController::class, 'store']);
    Route::post('update-product/{id}', [ProductController::class, 'update']);
    Route::delete('delete-product/{id}', [ProductController::class, 'destroy']);

    Route::post('/product/bulk-upload', [ProductController::class, 'upload']);

    // Product Gallery Management
    Route::post('product/{product}/gallery', [ProductImageController::class, 'store']);
    Route::post('product/{product}/gallery', [ProductImageController::class, 'update']);

    // update product variations and variant combinations
    Route::post('/product/{product}/images', [ProductImageController::class, 'addImages']);
    Route::delete('/product/image/{image}', [ProductImageController::class, 'deleteImage']);
    Route::post('/product/{product}/set-main-image', [ProductImageController::class, 'setMainImage']);
    Route::post('/product/{product}/videos', [ProductImageController::class, 'updateVideos']);

    // product variant routes
    Route::post('product/create-variation/{product}', [ProductVariantController::class, 'store']);
    Route::post('product/update-variation/{product}', [ProductVariantController::class, 'syncVariations']);

// Product SEO Meta Management

    Route::post('product-seo-meta/{product}', [ProductSeoMetaController::class, 'store']);
    Route::post('product-seo-meta/update-meta/{product}', [ProductSeoMetaController::class, 'update']);

    // product Tax Affinity Management Routes

    Route::post('product-tax-affinity/{product}/', [ProductTaxAffinityController::class, 'store']);
    Route::post('product-tax-affinity/update-tax/{product}', [ProductTaxAffinityController::class, 'update']);

    // publish the product
    Route::post('publish-product/{id}', [ProductController::class, 'publish']);
});

Route::prefix('ecom')->group(function () {
    Route::get('menu', [menuController::class, 'menu']);
    Route::get('products', [menuController::class, 'products']);
    Route::get('products-main', [menuController::class, 'products_main']);

    // app settion globel
    Route::get('/app-logo-settings', [SettingController::class, 'show']);
    Route::get('/list-brand', [BrandController::class, 'index_no_pagination']);

});

Route::prefix('user-dashboard')->middleware(['api', 'jwt.auth'])->group(function () {

    // Cart Functionalities

    Route::post('/cart/sync', [CartController::class, 'sync']);
    Route::get('/cart', [CartController::class, 'get']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);

    // address functionalies

    Route::get('/cart/get-address', [AddressController::class, 'index']);
    Route::post('/cart/add-address', [AddressController::class, 'store']);
    Route::put('/cart/update-address/{id}', [AddressController::class, 'update']);
    Route::delete('/cart/delete-address/{id}', [AddressController::class, 'destroy']);
    Route::post('/cart/set-default-address/{id}', [AddressController::class, 'setDefault']);

    // payent
    Route::post('/cart/create-order', [CartController::class, 'createOrder']);
    Route::post('/cart/verify-payment', [CartController::class, 'verifyPayment']);
    Route::post('/cart/save-order', [CartController::class, 'saveOrder']);

    // coupen check
    Route::post('/cart/apply-coupon', [CouponController::class, 'apply']);

    // order item save
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
});
