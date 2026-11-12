<?php

use App\Http\Controllers\ProgressNoteController;
use App\Http\Controllers\TrackerController;
use App\Http\Controllers\TrackerTargetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TrackerController::class, 'index'])->name('home');

Route::prefix('tracker')->name('tracker.')->group(function (): void {
    Route::post('/targets', [TrackerTargetController::class, 'store'])->name('targets.store');
    Route::put('/targets/{target}', [TrackerTargetController::class, 'update'])->name('targets.update');
    Route::patch('/targets/{target}/obstacle', [TrackerTargetController::class, 'toggleObstacle'])->name('targets.obstacle');
    Route::delete('/targets/{target}', [TrackerTargetController::class, 'destroy'])->name('targets.destroy');

    Route::post('/notes', [ProgressNoteController::class, 'store'])->name('notes.store');
    Route::match(['put', 'post'], '/notes/{note}', [ProgressNoteController::class, 'update'])->name('notes.update');
    Route::delete('/notes/{note}', [ProgressNoteController::class, 'destroy'])->name('notes.destroy');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
