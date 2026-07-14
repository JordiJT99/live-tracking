<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => ['service' => 'live-tracking-backend', 'status' => 'ok']);
