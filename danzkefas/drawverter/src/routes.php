<?php

use Danzkefas\Drawverter\Classes\Drawverter;
use Illuminate\Support\Facades\Route;

Route::get('/test', function() {
    echo "Hello World! This is your package using ServiceProviders";
});

Route::get('/convert/{filename}', function($filename) {
    $obj = new Drawverter;
    return $obj->start($filename);
});