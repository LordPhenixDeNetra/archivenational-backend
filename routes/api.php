<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::post('/auth/refresh', [\App\Http\Controllers\Api\V1\AuthController::class, 'refresh']);
    Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
    Route::get('/auth/me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me'])->middleware('auth:jwt');
    Route::post('/auth/forgot-password', [\App\Http\Controllers\Api\V1\AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [\App\Http\Controllers\Api\V1\AuthController::class, 'resetPassword']);

    Route::get('/fonds', [\App\Http\Controllers\Api\V1\FondsController::class, 'index']);
    Route::get('/fonds/{id}', [\App\Http\Controllers\Api\V1\FondsController::class, 'show']);

    Route::get('/documents', [\App\Http\Controllers\Api\V1\DocumentController::class, 'index']);
    Route::get('/documents/{id}', [\App\Http\Controllers\Api\V1\DocumentController::class, 'show']);
    Route::get('/documents/{id}/files', [\App\Http\Controllers\Api\V1\DocumentController::class, 'files']);
    Route::post('/documents/{id}/view', [\App\Http\Controllers\Api\V1\DocumentController::class, 'view']);

    Route::post('/requests', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'store']);
    Route::get('/requests', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'index'])->middleware('auth:jwt');
    Route::get('/requests/{id}', [\App\Http\Controllers\Api\V1\ServiceRequestController::class, 'show'])->middleware('auth:jwt');

    Route::prefix('admin')->middleware(['auth:jwt', 'permission:admin.access'])->group(function () {
        Route::get('/users', [\App\Http\Controllers\Api\V1\Admin\UserAdminController::class, 'index'])->middleware('permission:users.read');
        Route::patch('/users/{id}/status', [\App\Http\Controllers\Api\V1\Admin\UserAdminController::class, 'updateStatus'])->middleware('permission:users.write');
        Route::post('/users/{id}/roles', [\App\Http\Controllers\Api\V1\Admin\UserAdminController::class, 'assignRoles'])->middleware('permission:users.write');

        Route::get('/requests', [\App\Http\Controllers\Api\V1\Admin\ServiceRequestAdminController::class, 'index'])->middleware('permission:requests.read');
        Route::patch('/requests/{id}/status', [\App\Http\Controllers\Api\V1\Admin\ServiceRequestAdminController::class, 'updateStatus'])->middleware('permission:requests.manage');

        Route::post('/fonds', [\App\Http\Controllers\Api\V1\Admin\FondsAdminController::class, 'store'])->middleware('permission:fonds.write');
        Route::put('/fonds/{id}', [\App\Http\Controllers\Api\V1\Admin\FondsAdminController::class, 'update'])->middleware('permission:fonds.write');
        Route::delete('/fonds/{id}', [\App\Http\Controllers\Api\V1\Admin\FondsAdminController::class, 'destroy'])->middleware('permission:fonds.write');

        Route::post('/documents', [\App\Http\Controllers\Api\V1\Admin\DocumentAdminController::class, 'store'])->middleware('permission:documents.write');
        Route::put('/documents/{id}', [\App\Http\Controllers\Api\V1\Admin\DocumentAdminController::class, 'update'])->middleware('permission:documents.write');
        Route::delete('/documents/{id}', [\App\Http\Controllers\Api\V1\Admin\DocumentAdminController::class, 'destroy'])->middleware('permission:documents.write');
    });
});
