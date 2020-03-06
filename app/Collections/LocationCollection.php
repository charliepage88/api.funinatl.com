<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

use App\Location;

class LocationCollection extends Collection
{
    /**
    * To Searchable Array
    *
    * @return array
    */
    public function toSearchableArray()
    {
        // get array of location data
        $mapped = $this->map(function (Location $location) {
            return $location->toSearchableArray();
        });

        // format into array
        return $mapped->all();
    }

    /**
     * Get Formatted Array
     *
     * @param bool $includeRelationships
     *
     * @return array
     */
    public function getFormattedArray($includeRelationships = true)
    {
        // get array of band data
        $mapped = $this->map(function (Location $location) use ($includeRelationships) {
            return $location->getFormattedArray($includeRelationships);
        });

        // format into array
        return $mapped->all();
    }
}
