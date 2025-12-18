<?php

use App\Http\Controllers\Api\MinioController;

Route::prefix('files')->group(function () {
    Route::post('/', [MinioController::class, 'upload']);
    Route::get('/', [MinioController::class, 'index']);
    Route::get('/show-by-name', [MinioController::class, 'showByName']);
    Route::get('/url', [MinioController::class, 'url']);
    Route::put('/', [MinioController::class, 'update']);
    Route::delete('/', [MinioController::class, 'destroy']);
});
