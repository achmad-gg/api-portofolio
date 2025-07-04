<?php

use Illuminate\Support\Facades\Route;

// use Illuminate\Support\Facades\Config;



Route::get('/', function () {
    return view('welcome');
});

