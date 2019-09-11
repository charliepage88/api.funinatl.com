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

    Route::get('/events/bySlug/{slug}', 'EventsController@getBySlug')
        ->middleware('cacheResponse:300,eventsBySlug');

    // locations
    Route::get('/locations', 'LocationsController@index')
        ->middleware('cacheResponse:300,locationsIndex');

    // categories
    Route::get('/categories', 'CategoriesController@index')
        ->middleware('cacheResponse:300,categoriesIndex');

    // misc actions
    Route::post('/newsletter/subscribe', 'NewsletterController@subscribe');
    Route::post('/events/submit', 'EventsController@submit');
    Route::get('/events/search', 'EventsController@search');
    Route::post('/locations/submit', 'LocationsController@submit');
    Route::post('/contact/submit', 'ContactSubmissionsController@submit');
    Route::get('/routes', 'MetaController@routes')
        ->middleware('cacheResponse:300,routesList');

    // users
    Route::middleware('auth:api')->get('/user', 'UsersController@show');
    Route::post('/auth/login', 'UsersController@login');
    Route::post('/auth/register', 'UsersController@register');

    // Reports
    Route::get('/reports/daily-tweets', 'ReportsController@dailyTweets');
    Route::post('/reports/daily-tweets', 'ReportsController@updateDailyTweets');
});
