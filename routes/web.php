<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Services\MinioApiService;
use App\Http\Controllers\MinioUploadController;
use App\Http\Controllers\MinioFileController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/minio-test', function () {
    Storage::disk('minio')->put(
        'hello.txt',
        'Hello from Laravel + MinIO'
    );

    return 'File uploaded to MinIO!';
});

Route::get('/minio-list', function (MinioApiService $service) {
    return response()->json($service->list());
});


Route::get('/php-info', function () {
    phpinfo();
});
