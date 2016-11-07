<?php

use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('calendar');
    }

    return redirect('login');
});

Route::get('/test', function (Illuminate\Http\Request $request) {
    $mapper = \App\Data\Mappers\UserMapper::getInstance();
    $mapper->create(10000000, "Test Account", Illuminate\Support\Facades\Hash::make('password'));
    $mapper->done();

    return view('welcome');
});

// authentication
Route::get('/login', 'LoginController@showLoginForm')
    ->name('login');

Route::post('/login', 'LoginController@login');

Route::post('/logout', 'LoginController@logout')
    ->name('logout');

// calendar
Route::get('/calendar', 'CalendarController@viewCalendar')
    ->name('calendar');

