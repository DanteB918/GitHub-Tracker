<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Models\GithubApi;

Route::get('/', function () {
    if (GithubApi::first()){
        return view('home');
    }else{
        return redirect()->to('/settings');
    }
})->name('home');

Route::get('/settings', fn() => view('settings'))->name('settings');