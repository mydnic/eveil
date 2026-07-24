<?php

use App\Http\Controllers\ThumbnailTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('settings')->name('settings.')->group(function () {
    Route::get('/thumbnail-template', [ThumbnailTemplateController::class, 'edit'])->name('thumbnail-template.edit');
    Route::put('/thumbnail-template', [ThumbnailTemplateController::class, 'update'])->name('thumbnail-template.update');
    Route::post('/thumbnail-template/preview', [ThumbnailTemplateController::class, 'preview'])->name('thumbnail-template.preview');
});
