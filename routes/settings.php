<?php

use App\Http\Controllers\ChannelProfileController;
use App\Http\Controllers\ThumbnailTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('settings')->name('settings.')->group(function () {
    Route::get('/thumbnail-templates', [ThumbnailTemplateController::class, 'index'])->name('thumbnail-templates.index');
    Route::get('/thumbnail-templates/create', [ThumbnailTemplateController::class, 'create'])->name('thumbnail-templates.create');
    Route::post('/thumbnail-templates', [ThumbnailTemplateController::class, 'store'])->name('thumbnail-templates.store');
    Route::post('/thumbnail-templates/preview', [ThumbnailTemplateController::class, 'preview'])->name('thumbnail-templates.preview');
    Route::get('/thumbnail-templates/{thumbnailTemplate}/edit', [ThumbnailTemplateController::class, 'edit'])->name('thumbnail-templates.edit');
    Route::put('/thumbnail-templates/{thumbnailTemplate}', [ThumbnailTemplateController::class, 'update'])->name('thumbnail-templates.update');
    Route::delete('/thumbnail-templates/{thumbnailTemplate}', [ThumbnailTemplateController::class, 'destroy'])->name('thumbnail-templates.destroy');
    Route::post('/thumbnail-templates/{thumbnailTemplate}/default', [ThumbnailTemplateController::class, 'makeDefault'])->name('thumbnail-templates.default');

    Route::get('/channel-profile', [ChannelProfileController::class, 'edit'])->name('channel-profile.edit');
    Route::put('/channel-profile', [ChannelProfileController::class, 'update'])->name('channel-profile.update');
});
