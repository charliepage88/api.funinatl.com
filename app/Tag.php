<?php

namespace App;

use Spatie\Tags\Tag as ParentModel;

use DB;

class Tag extends ParentModel
{
    /**
    * Related Count
    *
    * @return integer
    */
    public function relatedCount()
    {
        $count = DB::table('taggables')->where('tag_id', $this->id)->count();

        return $count;
    }

    /**
     * Get Mongo Array
     *
     * @return array
     */
    public function getMongoArray()
    {
        // tag data
        $fields = [
            'id',
            'name',
            'slug'
        ];

        $tag = [];
        foreach($fields as $field) {
            $tag[$field] = $this->$field;
        }

        $tag['created_at'] = $this->created_at->toAtomString();
        $tag['updated_at'] = $this->updated_at->toAtomString();

        return $tag;
    }
}
