<?php

namespace App;

use Spatie\Tags\Tag as ParentModel;

use App\Collections\TagCollection;

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
    * Find Ids By Model Id
    *
    * @param object $model
    *
    * @return array
    */
    public function findIdsByModelId($model)
    {
        $ids = DB::table('taggables')
            ->where('tag_id', $this->id)
            ->where('taggable_type', get_class($model))
            ->pluck('taggable_id');

        return $ids->toArray();
    }

    /**
     * To Searchable Array
     *
     * @return array
     */
    public function toSearchableArray()
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

    /**
     * Get Formatted Array
     *
     * @param bool $includeRelationships
     *
     * @return array
     */
    public function getFormattedArray($includeRelationships = true)
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

    /**
    * Scope By Slug
    *
    * @param object $query
    * @param string $slug
    *
    * @return object
    */
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug->en', $slug)->first();
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new TagCollection($models);
    }
}
