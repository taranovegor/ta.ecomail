<?php

use App\Http\Controllers;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('contacts.index'));

Route::resource('contacts', Controllers\ContactController::class);

Route::prefix('imports')->name('imports.')->group(function () {
    Route::get('/create', [ImportController::class, 'create'])->name('create');
    Route::post('/', [ImportController::class, 'store'])->name('store');
    Route::get('/{import}', [ImportController::class, 'show'])->name('show');
    Route::get('/{import}/issues', [ImportController::class, 'issues'])->name('issues');
});
