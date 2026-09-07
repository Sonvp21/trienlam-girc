<?php

use Illuminate\Support\Facades\Route;

// Trang chính: kiosk triển lãm (camera + mic + video)
Route::get('/', function () {
    return view('kiosk');
});

// Trang danh sách 14 hệ thống thật (mở tab mới)
Route::get('/sites', function () {
    return view('sites');
});

// Trang hội thảo AI & IoT độc lập
Route::get('/hoi-thao-ai-iot', function () {
    return view('sites-standalone');
});
