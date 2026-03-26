<?php

use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ContinentController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\SpecialCategoryController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);

// Public endpoints
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Public read-only endpoints for configuration
Route::get('/continents', [ContinentController::class, 'index']);
Route::get('/continents/{continent}', [ContinentController::class, 'show']);
Route::get('/countries', [CountryController::class, 'index']);
Route::get('/countries/{country}', [CountryController::class, 'show']);
Route::get('/teams', [TeamController::class, 'index']);
Route::get('/teams/{team}', [TeamController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/sizes', [SizeController::class, 'index']);
Route::get('/sizes/{size}', [SizeController::class, 'show']);
Route::get('/special-categories', [SpecialCategoryController::class, 'index']);
Route::get('/special-categories/{specialCategory}', [SpecialCategoryController::class, 'show']);
Route::get('/leagues', [LeagueController::class, 'index']);
Route::get('/leagues/{league}', [LeagueController::class, 'show']);

Route::post('/orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment']);
Route::post('/webhooks/wompi', [OrderController::class, 'wompiWebhook'])->withoutMiddleware(['auth:api'])->name('wompi.webhook');
Route::post('/webhooks/nowpayments', [OrderController::class, 'nowPaymentsWebhook'])->withoutMiddleware(['auth:api'])->name('nowpayments.webhook');
Route::post('/webhooks/stripe', [OrderController::class, 'stripeWebhook'])->name('stripe.webhook');
Route::get('/crypto-currencies', [OrderController::class, 'getCryptoCurrencies']);
Route::post('/crypto-estimate', [OrderController::class, 'getCryptoEstimate']);
Route::post('/test-wompi-signature', [OrderController::class, 'testWompiSignature']);

Route::post('/coupons/validate', [CouponController::class, 'validateCoupon']);


Route::get('/import/wc/{category_id}/{team_id}', [ImportController::class, 'runImport']);


Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Protected product endpoints
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    
    Route::apiResource('addresses', AddressController::class);
    Route::patch('/addresses/{address}/set-primary', [AddressController::class, 'setPrimary']);
    
    // Images
    Route::post('/images/upload', [ImageController::class, 'upload']);
    Route::post('/images/delete', [ImageController::class, 'delete']);
    Route::post('/images/optimize', [ImageController::class, 'optimize']);
    
    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{product}', [FavoriteController::class, 'destroy']);
    Route::post('/favorites/{product}/toggle', [FavoriteController::class, 'toggle']);
    
    // Coupons
    Route::get('/coupons', [CouponController::class, 'index']);
    Route::post('/coupons', [CouponController::class, 'store']);
    Route::get('/coupons/{coupon}', [CouponController::class, 'show']);
    Route::put('/coupons/{coupon}', [CouponController::class, 'update']);
    Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy']);
    
    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/orders/{order}/payment-status', [OrderController::class, 'checkPaymentStatus']);
    Route::get('/orders/{order}/checkout-url', [OrderController::class, 'getOrderCheckoutUrl']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
});

// Admin-only endpoints
Route::middleware(['auth:api', 'admin'])->group(function () {
    // Product management
    Route::post('/products/bulk-price-update', [ProductController::class, 'bulkPriceUpdate']);
    
    // Product images management
    Route::post('/products/{product}/images', [ProductController::class, 'addImage']);
    Route::delete('/products/{product}/images/{image}', [ProductController::class, 'removeImage']);
    Route::patch('/products/{product}/images/{image}/primary', [ProductController::class, 'setPrimaryImage']);
    Route::patch('/products/{product}/images/reorder', [ProductController::class, 'reorderImages']);
    
    // Product variants management
    Route::post('/products/{product}/variants', [ProductController::class, 'addVariant']);
    Route::put('/products/{product}/variants/{variant}', [ProductController::class, 'updateVariant']);
    Route::delete('/products/{product}/variants/{variant}', [ProductController::class, 'removeVariant']);
    
    // Geography management
    Route::post('/continents', [ContinentController::class, 'store']);
    Route::put('/continents/{continent}', [ContinentController::class, 'update']);
    Route::delete('/continents/{continent}', [ContinentController::class, 'destroy']);
    
    Route::post('/countries', [CountryController::class, 'store']);
    Route::put('/countries/{country}', [CountryController::class, 'update']);
    Route::delete('/countries/{country}', [CountryController::class, 'destroy']);
    
    // Team management
    Route::post('/teams', [TeamController::class, 'store']);
    Route::put('/teams/{team}', [TeamController::class, 'update']);
    Route::delete('/teams/{team}', [TeamController::class, 'destroy']);
    
    // Category management
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    
    // Size management
    Route::post('/sizes', [SizeController::class, 'store']);
    Route::put('/sizes/{size}', [SizeController::class, 'update']);
    Route::delete('/sizes/{size}', [SizeController::class, 'destroy']);
    
    // Special category management
    Route::post('/special-categories', [SpecialCategoryController::class, 'store']);
    Route::put('/special-categories/{specialCategory}', [SpecialCategoryController::class, 'update']);
    Route::delete('/special-categories/{specialCategory}', [SpecialCategoryController::class, 'destroy']);
    
    // League management
    Route::post('/leagues', [LeagueController::class, 'store']);
    Route::put('/leagues/{league}', [LeagueController::class, 'update']);
    Route::delete('/leagues/{league}', [LeagueController::class, 'destroy']);
    
    // User management
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/statistics', [UserController::class, 'statistics']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::patch('/users/{user}/toggle-admin', [UserController::class, 'toggleAdmin']);
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword']);
    Route::post('/users/bulk-delete', [UserController::class, 'bulkDelete']);
    Route::get('/users/{user}/orders', [UserController::class, 'orders']);
    Route::get('/users/{user}/addresses', [UserController::class, 'addresses']);
    Route::get('/users/{user}/favorites', [UserController::class, 'favorites']);

    // Newsletter
    Route::get('/newsletter/subscribers', [NewsletterController::class, 'index']);
});
