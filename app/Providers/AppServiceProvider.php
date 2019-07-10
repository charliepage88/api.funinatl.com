<?php

namespace App\Providers;

use Geocoder\Provider\AlgoliaPlaces\AlgoliaPlaces;
use Http\Adapter\Guzzle6\Client;
use Illuminate\Support\ServiceProvider;

use App\Event;
use App\Location;
use App\Helpers\SiteHelper;
use App\Observers\EventObserver;
use App\Observers\LocationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('geocoder', function () {
            $adapter  = new Client();
            $provider = new AlgoliaPlaces(
                $adapter,
                env('ALGOLIA_PLACES_KEY'),
                env('ALGOLIA_PLACES_APP_ID')
            );
            $geocoder = new \Geocoder\StatefulGeocoder(
                $provider,
                'en'
            );

            return $geocoder;
        });

        $this->app->singleton('siteHelper', function () {
            return new SiteHelper();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Location::observe(LocationObserver::class);
        Event::observe(EventObserver::class);
    }
}
