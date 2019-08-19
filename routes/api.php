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

Route::namespace('Api')->group(function () {
    // main events source
    Route::get('/events/category/{slug}/{start_date}/{end_date}', 'EventsController@getByPeriodAndCategory')
        ->middleware('cacheResponse:300,eventsByPeriodAndCategory');

    Route::get('/events/location/{slug}/{start_date}/{end_date}', 'EventsController@getByPeriodAndLocation')
        ->middleware('cacheResponse:300,eventsByPeriodAndLocation');

    Route::get('/events/tag/{slug}/{start_date}/{end_date}', 'EventsController@getByPeriodAndTag')
        ->middleware('cacheResponse:300,eventsByPeriodAndTag');

    Route::get('/events/index/{start_date}/{end_date}', 'EventsController@indexByPeriod')
        ->middleware('cacheResponse:300,eventsIndexByPeriod');

    // misc actions
    Route::post('/newsletter/subscribe', 'NewsletterController@subscribe');
    Route::post('/events/submit', 'EventsController@submit');
    Route::get('/events/search', 'EventsController@search');
    Route::post('/locations/submit', 'LocationsController@submit');
    Route::post('/contact/submit', 'ContactSubmissionsController@submit');
    Route::get('/routes', 'MetaController@routes');

    // users
    Route::middleware('auth:api')->get('/user', 'UsersController@show');
});
