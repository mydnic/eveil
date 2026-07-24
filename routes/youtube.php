<?php

use App\Http\Controllers\DescriptionController;
use App\Http\Controllers\ThumbnailController;
use App\Http\Controllers\VideosController;
use App\Http\Controllers\YoutubeAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/youtube/connect', [YoutubeAuthController::class, 'connect'])->name('youtube.connect');
    Route::get('/youtube/callback', [YoutubeAuthController::class, 'callback'])->name('youtube.callback');
    Route::delete('/youtube/disconnect', [YoutubeAuthController::class, 'disconnect'])->name('youtube.disconnect');

    Route::get('/videos', [VideosController::class, 'index'])->name('videos.index');

    Route::post('/videos/{video}/thumbnail/candidates', [ThumbnailController::class, 'candidates'])->name('videos.thumbnail.candidates');
    Route::post('/videos/{video}/thumbnail/publish', [ThumbnailController::class, 'publish'])->name('videos.thumbnail.publish');
    Route::post('/thumbnail/preview', [ThumbnailController::class, 'preview'])->name('thumbnail.preview');

    Route::post('/videos/{video}/description/generate', [DescriptionController::class, 'generate'])->name('videos.description.generate');
    Route::post('/videos/{video}/description/publish', [DescriptionController::class, 'publish'])->name('videos.description.publish');
});
