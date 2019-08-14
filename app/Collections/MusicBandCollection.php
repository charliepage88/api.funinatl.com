<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

use App\MusicBand;

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
        $mapped = $this->map(function (MusicBand $band) {
            return $band->toSearchableArray();
        });

        // format into array
        return $mapped->all();
    }

    /**
     * Get Mongo Array
     *
     * @param bool $includeRelationships
     *
     * @return array
     */
    public function getMongoArray($includeRelationships = true)
    {
        // get array of band data
        $mapped = $this->map(function (MusicBand $band) use ($includeRelationships) {
            return $band->getMongoArray($includeRelationships);
        });

        // format into array
        return $mapped->all();
    }
}