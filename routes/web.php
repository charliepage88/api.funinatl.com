<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/', 'HomeController@index')->name('home');

// Admin Routes
Route::middleware('auth')->namespace('Admin')->prefix('admin')->group(function () {
    // Admin Dashboard
    Route::get('/', 'DashboardController@adminIndex');

    Route::get('/dashboard', 'DashboardController@adminDashboard')->name('admin.dashboard');
    Route::get('/logout', 'DashboardController@logout')->name('admin.logout');

    // Categories
    Route::get('/categories', 'CategoriesController@index')
        ->name('admin.categories.index');
    Route::any('/categories/create', 'CategoriesController@create')
        ->name('admin.categories.create');
    Route::any('/categories/edit/{category}', 'CategoriesController@edit')
        ->name('admin.categories.edit');

    // Locations
    Route::get('/locations', 'LocationsController@index')
        ->name('admin.locations.index');
    Route::any('/locations/create', 'LocationsController@create')
        ->name('admin.locations.create');
    Route::any('/locations/edit/{location}', 'LocationsController@edit')
        ->name('admin.locations.edit');

    // Providers
    Route::get('/providers', 'ProvidersController@index')
        ->name('admin.providers.index');
    Route::any('/providers/create', 'ProvidersController@create')
        ->name('admin.providers.create');
    Route::any('/providers/edit/{provider}', 'ProvidersController@edit')
        ->name('admin.providers.edit');

    // Users
    Route::get('/users', 'UsersController@index')
        ->name('admin.users.index');
    Route::any('/users/create', 'UsersController@create')
        ->name('admin.users.create');
    Route::any('/users/edit/{user}', 'UsersController@edit')
        ->name('admin.users.edit');

    // Tags
    Route::get('/tags', 'TagsController@index')
        ->name('admin.tags.index');
    Route::any('/tags/create', 'TagsController@create')
        ->name('admin.tags.create');
    Route::any('/tags/edit/{tag}', 'TagsController@edit')
        ->name('admin.tags.edit');

    // Bands
    Route::get('/bands', 'MusicBandsController@index')
        ->name('admin.bands.index');
    Route::any('/bands/create', 'MusicBandsController@create')
        ->name('admin.bands.create');
    Route::any('/bands/edit/{band}', 'MusicBandsController@edit')
        ->name('admin.bands.edit');

    // Events
    Route::get('/events', 'EventsController@index')
        ->name('admin.events.index');
    Route::any('/events/create', 'EventsController@create')
        ->name('admin.events.create');
    Route::any('/events/edit/{event}', 'EventsController@edit')
        ->name('admin.events.edit');

    // Contact Submissions
    Route::get('/contact-submissions', 'ContactSubmissionsController@index')
        ->name('admin.contact_submissions.index');
    Route::any('/contact-submissions/review/{submission}', 'ContactSubmissionsController@review')
        ->name('admin.contact_submissions.review');
    Route::get('/contact-submissions/delete/{submission}', 'ContactSubmissionsController@destroy')
        ->name('admin.contact_submissions.delete');

    // Reports
    Route::get('/reports/daily-tweets', 'ReportsController@dailyTweets')
        ->name('admin.reports.daily_tweets');
});

// Webhooks
Route::post('/webhook/sync', 'WebhooksController@sync');
