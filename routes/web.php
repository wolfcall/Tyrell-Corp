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

Route::get('/reservation/cancel/{id}/{room}/{timeslot}', 'ReservationController@cancelReservation')
        ->where(['timeslot' => '2[0-9]{3}-[0-9]{2}-[0-9]{2}T[0-9]{2}'])
        ->name('reservationCancel');

Route::get('/reservation/requestCancel/{id}', 'ReservationController@requestCancel')
        ->name('requestCancel');
