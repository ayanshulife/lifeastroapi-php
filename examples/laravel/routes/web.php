<?php

// Add this route to your app's routes/web.php (or routes/api.php).

use App\Http\Controllers\PanchangController;
use Illuminate\Support\Facades\Route;

Route::get('/panchang', [PanchangController::class, 'show']);
