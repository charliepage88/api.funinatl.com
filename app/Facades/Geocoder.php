<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade as BaseFacade;

class Geocoder extends BaseFacade
{
    protected static function getFacadeAccessor()
    {
        return 'geocoder';
    }
}
