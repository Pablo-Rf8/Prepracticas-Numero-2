<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::view('/', 'welcome')->name('home');

Route::get('/assets/app.css', fn (): BinaryFileResponse => response()->file(
    base_path('../frontend/resources/css/app.css'),
    ['Content-Type' => 'text/css; charset=UTF-8'],
))->name('frontend.styles');

Route::get('/assets/app.js', fn (): BinaryFileResponse => response()->file(
    base_path('../frontend/resources/js/app.js'),
    ['Content-Type' => 'application/javascript; charset=UTF-8'],
))->name('frontend.script');
