<?php

use App\Http\Controllers\DemoController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('invitation/{token}')->group(function (): void {
    Route::get('/', [InvitationController::class, 'showAccept']);
    Route::post('/set-password', [InvitationController::class, 'setPassword']);
    Route::get('/verify', [InvitationController::class, 'verify']);
});

Route::get('/demo/flow', [DemoController::class, 'flow']);
Route::get('/demo/navigation', [DemoController::class, 'navigation']);
