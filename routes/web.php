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

// Route::get('/test', function (Illuminate\Http\Request $request) {
//     $mapper = \App\Data\Mappers\UserMapper::getInstance();
    // $mapper->create(10000000, "Test Account", Illuminate\Support\Facades\Hash::make('password'));
    // $mapper->create(10000001, "Test Account", Illuminate\Support\Facades\Hash::make('password'));
    // $mapper->create(10000002, "Test Account 2", Illuminate\Support\Facades\Hash::make('password'));
    // $mapper->create(10000003, "Test Account 3", Illuminate\Support\Facades\Hash::make('password'));
    // $mapper->create(10000004, "Test Account 4", Illuminate\Support\Facades\Hash::make('password'));
    // $mapper->create(10000005, "Test Account 5", Illuminate\Support\Facades\Hash::make('password'));
    // $mapper->create(10000006, "Test Account 6", Illuminate\Support\Facades\Hash::make('password'));
    // $mapper->create(10000007, "Test Account 7", Illuminate\Support\Facades\Hash::make('password'));
    // $mapper->done();
    //
    // return view('welcome');
// });

// authentication
Route::get('/login', 'LoginController@showLoginForm')
    ->name('login');

Route::post('/login', 'LoginController@login');

Route::post('/logout', 'LoginController@logout')
    ->name('logout');

// calendar
Route::get('/calendar', 'CalendarController@viewCalendar')
    ->name('calendar');

// reservations
Route::get('/reservation/list', 'ReservationController@viewReservationList')
    ->name('reservationList');

Route::get('/reservation/{id}', 'ReservationController@viewReservation')
    ->name('reservation');

Route::get('/reservation/request/{room}/{timeslot}', 'ReservationController@showRequestForm')
    ->where(['timeslot' => '2[0-9]{3}-[0-9]{2}-[0-9]{2}T[0-9]{2}'])
    ->name('request');

Route::post('/reservation/request/{room}/{timeslot}', 'ReservationController@requestReservation')
    ->where(['timeslot' => '2[0-9]{3}-[0-9]{2}-[0-9]{2}T[0-9]{2}'])
    ->name('requestPost');

Route::get('/reservation/modify/{id}', 'ReservationController@showModifyForm')
    ->name('reservationModify');

Route::post('/reservation/modify/{id}', 'ReservationController@modifyReservation')
    ->name('reservationModifyPost');

Route::get('/reservation/cancel/{id}', 'ReservationController@cancelReservation')
    ->name('reservationCancel');
