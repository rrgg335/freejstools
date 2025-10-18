<?php

use Illuminate\Support\Facades\Route;

Route::view('/','index')->name('index');
Route::view('password-generator','password-generator')->name('password-generator');
Route::view('base64-encoder','base64-encoder')->name('base64-encoder');