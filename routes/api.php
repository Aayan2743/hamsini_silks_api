<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CouponController;
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

    // product Tax Affinity Management Routes

    Route::post('product-tax-affinity/{product}/', [ProductTaxAffinityController::class, 'store']
    );

    // publish the product
    Route::post('publish-product/{id}', [ProductController::class, 'publish']);
});

// Route::get('/public/settings/brand', [BrandingController::class, 'show']);

// Route::get(
//     '/public/organizations/transactions',
//     [OrganizationSubscriptionController::class, 'transactions']);

// Route::get(
//     '/public/dashboard',
//     [OrganizationSubscriptionController::class, 'index']);

// Route::middleware(['api', 'jwt.auth'])->group(function () {
//     Route::get('/profile', [AuthController::class, 'profile']);
//     Route::post('/logout', [AuthController::class, 'logout']);

//     Route::post('/settings/profile', [SettingsController::class, 'updateProfile']);
//     Route::post('/settings/brand', [BrandingController::class, 'store']);
//     Route::get('/settings/brand', [BrandingController::class, 'show']);
//     Route::post('/settings/card-pricing', [CardPrincingController::class, 'store']);
//     Route::get('/settings/card-pricing', [CardPrincingController::class, 'show']);

//     Route::get('/organizations', [OrginazationController::class, 'index']);
//     Route::post('/organizations', [OrginazationController::class, 'store']);
//     Route::get('/organizations/{organization}', [OrginazationController::class, 'show']);

//     Route::put('/organizations/{organization}', [OrginazationController::class, 'update']);

//     Route::delete('/organizations/{organization}', [OrginazationController::class, 'destroy']);

//     Route::get('/organizations/{organization}/card-stats',
//         [OrginazationController::class, 'stats']);

//     Route::post(
//         '/organizations/{organization}/add-cards',
//         [OrganizationSubscriptionController::class, 'store']);

//     Route::get(
//         '/organizations/{organization}/subscription',
//         [OrganizationSubscriptionController::class, 'show']);

//     Route::get(
//         '/organizations/transactions',
//         [OrganizationSubscriptionController::class, 'transactions']);

//     // Orginazation Employeer Routes

//     Route::get('/settings-orginization', [OrginazationController::class, 'showOrginizationBrandSettings']);
//     Route::post('/organizations/brand-settings', [OrginazationController::class, 'save']);

// });
