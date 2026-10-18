<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'DemarchesTchad API',
        'version' => '1.0.0',
        'status' => 'operational',
        'country' => 'Tchad',
    ]);
});
