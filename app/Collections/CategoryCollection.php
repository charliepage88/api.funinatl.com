<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

use App\Category;

class CategoryCollection extends Collection
{
    /**
    * To Searchable Array
    *
    * @return array
    */
    public function toSearchableArray()
    {
        // get array of category data
        $mapped = $this->map(function (Category $category) {
            return $category->toSearchableArray();
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
        $mapped = $this->map(function (Category $category) use ($includeRelationships) {
            return $category->getFormattedArray($includeRelationships);
        });

        // format into array
        return $mapped->all();
    }

    /**
    * Get List
    *
    * @return Collection
    */
    public function getList()
    {
        $items = $this->map(function (Category $category) {
            return $category;
        });

        $mapped = [];
        foreach($items as $item) {
            $mapped[$item->slug] = $item;
        }

        return $mapped;
    }
}
