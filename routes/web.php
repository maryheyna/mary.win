<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

// The OMT symbols reference that previously lived at '/'.
Route::get('/omt', function () {
    return view('welcome');
})->name('omt');

require __DIR__ . '/auth.php';
