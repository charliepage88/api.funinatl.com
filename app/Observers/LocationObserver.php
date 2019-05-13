<?php

namespace App\Observers;

use App\Location;

class LocationObserver
{
    /**
    * Created
    *
    * @param Location $location
    *
    * @return Location
    */
    public function created(Location $location)
    {
        $location->geocodeAddress();
    }
}
