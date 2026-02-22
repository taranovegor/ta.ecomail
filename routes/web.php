<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('contacts.index'));

Route::resource('contacts', ContactController::class);
