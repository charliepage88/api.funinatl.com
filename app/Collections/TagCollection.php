<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

use App\Tag;

class TagCollection extends Collection
{
    /**
    * To Searchable Array
    *
    * @return array
    */
    public function toSearchableArray()
    {
        // get array of tag data
        $mapped = $this->map(function (Tag $tag) {
            return $tag->toSearchableArray();
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
        $mapped = $this->map(function (Tag $tag) use ($includeRelationships) {
            return $tag->getMongoArray($includeRelationships);
        });

        // format into array
        return $mapped->all();
    }
}
