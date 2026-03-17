<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', [\App\Http\Controllers\DocsController::class, 'swaggerUi']);
Route::get('/docs/openapi', [\App\Http\Controllers\DocsController::class, 'openapi']);
