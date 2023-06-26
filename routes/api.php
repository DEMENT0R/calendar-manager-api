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

Route::get('/events', 'Controller@read');
Route::get('/add-event', 'Controller@create');
Route::get('/get-event', 'Controller@read');
Route::get('/update-event', 'Controller@update');
Route::get('/delete-event', 'Controller@delete');

Route::get('/get-client', 'Controller@getClient');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
