<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function () {
    return ['version' => app()->version()];
});

Route::get('/events', 'Controller@getEvents');
Route::get('/add-event', 'Controller@addEvent');
Route::get('/get-client', 'Controller@getClient');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
