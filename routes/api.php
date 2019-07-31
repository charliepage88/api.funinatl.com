<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::namespace('Api')->group(function () {
    Route::post('/newsletter/subscribe', 'NewsletterController@subscribe');
    Route::post('/events/submit', 'EventsController@submit');
    Route::get('/events/search', 'EventsController@search');
    Route::post('/locations/submit', 'LocationsController@submit');
    Route::post('/contact/submit', 'ContactSubmissionsController@submit');
    Route::get('/routes', 'MetaController@routes');
});
