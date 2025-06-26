<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/optimize', function () {
    Artisan::call('optimize');
    Artisan::call('filament:optimize');

    echo 'Optimized!';
});