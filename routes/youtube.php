<?php

use App\Http\Controllers\VideosController;
use App\Http\Controllers\YoutubeAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/youtube/connect', [YoutubeAuthController::class, 'connect'])->name('youtube.connect');
    Route::get('/youtube/callback', [YoutubeAuthController::class, 'callback'])->name('youtube.callback');
    Route::delete('/youtube/disconnect', [YoutubeAuthController::class, 'disconnect'])->name('youtube.disconnect');

    Route::get('/videos', [VideosController::class, 'index'])->name('videos.index');
});
