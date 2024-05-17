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

// CRUD
Route::get('/events/create', 'Controller@create');
Route::get('/events/read', 'Controller@read');
Route::get('/events/list', 'Controller@list');
Route::get('/events/find/{id}', 'Controller@find');
Route::get('/events/update', 'Controller@update');
Route::get('/events/delete', 'Controller@delete');
Route::get('/events/clear', 'Controller@clear');

Route::get('/get-client', 'Controller@getClient');
Route::get('/json', 'Controller@json');
Route::get('/force-update-token', 'Controller@forceUpdateToken');
Route::get('/force-update-token/{count}', 'Controller@forceUpdateToken');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
