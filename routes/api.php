<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware(['detect.suspicious', 'throttle:contact-submit'])->post('/contact', [ContactController::class, 'store']);

