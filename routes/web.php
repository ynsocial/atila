<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['detect.suspicious', 'throttle:contact-submit'])
    ->post('/contact', [ContactController::class, 'store']);
