<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

class MusicBandCollection extends Collection
{
    /**
    * To Searchable Array
    *
    * @return array
    */
    public function toSearchableArray()
    {
        // get array of band data
        $mapped = $this->map(function ($band) {
            $musicBand = [];

            $musicBand['id'] = $band->id;
            $musicBand['name'] = $band->name;
            $musicBand['slug'] = $band->slug;
            $musicBand['photo'] = $band->photo_url;

            return $musicBand;
        });

        // format into array
        return $mapped->collapse()->values()->all();
    }

    /**
     * Get Mongo Array
     *
     * @return array
     */
    public function getMongoArray()
    {
        // get array of band data
        $mapped = $this->map(function ($band) {
            $musicBand = [];

            $musicBand['id'] = $band->id;
            $musicBand['name'] = $band->name;
            $musicBand['slug'] = $band->slug;
            $musicBand['photo'] = $band->photo_url;

            return $musicBand;
        });

        // format into array
        return $mapped->collapse()->values()->all();
    }
}