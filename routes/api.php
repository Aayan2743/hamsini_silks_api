<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\CardPrincingController;
use App\Http\Controllers\OrganizationSubscriptionController;
use App\Http\Controllers\OrginazationController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    // ================= AUTH =================
    Route::post('admin-register', [AuthController::class, 'admin_register']);
    Route::post('admin-login', [AuthController::class, 'admin_login']);
    Route::post('user-register', [AuthController::class, 'register']);
    Route::post('user-login', [AuthController::class, 'login']);

    // ================= PASSWORD / OTP =================
    Route::post('forgot-password', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::post('organization/forgot-password', [AuthController::class, 'OrgsendOtp']);

});

Route::prefix('admin-dashboard')->middleware(['api', 'jwt.auth'])->group(function () {

    // ================= AUTH =================
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::get('/app-logo-settings', [SettingController::class, 'show']);
    Route::post('/app-logo-settings', [SettingController::class, 'update']);

});

Route::get('/public/settings/brand', [BrandingController::class, 'show']);

Route::get(
    '/public/organizations/transactions',
    [OrganizationSubscriptionController::class, 'transactions']);

Route::get(
    '/public/dashboard',
    [OrganizationSubscriptionController::class, 'index']);

Route::middleware(['api', 'jwt.auth'])->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/settings/profile', [SettingsController::class, 'updateProfile']);
    Route::post('/settings/brand', [BrandingController::class, 'store']);
    Route::get('/settings/brand', [BrandingController::class, 'show']);
    Route::post('/settings/card-pricing', [CardPrincingController::class, 'store']);
    Route::get('/settings/card-pricing', [CardPrincingController::class, 'show']);

    Route::get('/organizations', [OrginazationController::class, 'index']);
    Route::post('/organizations', [OrginazationController::class, 'store']);
    Route::get('/organizations/{organization}', [OrginazationController::class, 'show']);

    Route::put('/organizations/{organization}', [OrginazationController::class, 'update']);

    Route::delete('/organizations/{organization}', [OrginazationController::class, 'destroy']);

    Route::get('/organizations/{organization}/card-stats',
        [OrginazationController::class, 'stats']);

    Route::post(
        '/organizations/{organization}/add-cards',
        [OrganizationSubscriptionController::class, 'store']);

    Route::get(
        '/organizations/{organization}/subscription',
        [OrganizationSubscriptionController::class, 'show']);

    Route::get(
        '/organizations/transactions',
        [OrganizationSubscriptionController::class, 'transactions']);

    // Orginazation Employeer Routes

    Route::get('/settings-orginization', [OrginazationController::class, 'showOrginizationBrandSettings']);
    Route::post('/organizations/brand-settings', [OrginazationController::class, 'save']);

});
