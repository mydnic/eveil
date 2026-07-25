<?php

use App\Http\Controllers\ChannelChatController;
use App\Http\Controllers\DescriptionController;
use App\Http\Controllers\ThumbnailController;
use App\Http\Controllers\VideoChatController;
use App\Http\Controllers\VideosController;
use App\Http\Controllers\YoutubeAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/youtube/connect', [YoutubeAuthController::class, 'connect'])->name('youtube.connect');
    Route::get('/youtube/callback', [YoutubeAuthController::class, 'callback'])->name('youtube.callback');
    Route::delete('/youtube/disconnect', [YoutubeAuthController::class, 'disconnect'])->name('youtube.disconnect');

    Route::get('/videos', [VideosController::class, 'index'])->name('videos.index');
    Route::get('/videos/{video}', [VideosController::class, 'show'])->name('videos.show');

    Route::post('/videos/{video}/thumbnail/candidates', [ThumbnailController::class, 'candidates'])->name('videos.thumbnail.candidates');
    Route::post('/videos/{video}/thumbnail/publish', [ThumbnailController::class, 'publish'])->name('videos.thumbnail.publish');
    Route::post('/thumbnail/preview', [ThumbnailController::class, 'preview'])->name('thumbnail.preview');
    Route::post('/thumbnail/search', [ThumbnailController::class, 'search'])->name('thumbnail.search');

    Route::post('/videos/{video}/description/generate', [DescriptionController::class, 'generate'])->name('videos.description.generate');
    Route::post('/videos/{video}/description/publish', [DescriptionController::class, 'publish'])->name('videos.description.publish');

    Route::post('/videos/{video}/chat', [VideoChatController::class, 'stream'])->name('videos.chat');

    Route::post('/chat', [ChannelChatController::class, 'stream'])->name('chat');
});
