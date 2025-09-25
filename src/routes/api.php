<?php

declare(strict_types=1);

use App\Http\Controllers\Api\FillBinController;
use App\Http\Middleware\VerifySignature;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', VerifySignature::class])->group(function () {
    Route::post('/file/fill-bin', [FillBinController::class, 'upload'])->name('file.fill-bin');
});
