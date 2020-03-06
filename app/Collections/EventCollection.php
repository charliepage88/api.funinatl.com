<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

use App\Event;

class EventCollection extends Collection
{
    /**
    * To Searchable Array
    *
    * @return array
    */
    public function toSearchableArray()
    {
        // get array of event data
        $mapped = $this->map(function (Event $event) {
            return $event->toSearchableArray();
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
        $mapped = $this->map(function (Event $event) use ($includeRelationships) {
            return $event->getFormattedArray($includeRelationships);
        });

        // format into array
        return $mapped->all();
    }
}
