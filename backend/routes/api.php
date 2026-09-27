<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
|
| Every write is a queue flush, so the whole surface is versioned from day
| one. Field devices in the field hold this contract for years, which means
| additive-only changes and explicit version bumps.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('sync', [SyncController::class, 'store'])->name('sync.store');
    Route::get('submissions', [SyncController::class, 'index'])->name('submissions.index');
    Route::get('sync-runs', [SyncController::class, 'runs'])->name('sync.runs');
});
