<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class SiteHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'siteHelper';
    }
}
