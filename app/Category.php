<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasSlug,
        Searchable;

    protected $connection = 'mysql';

    /**
    * @var array
    */
    protected $fillable = [
        'name',
        'slug',
        'active',
        'is_default'
    ];

    /**
    * @var array
    */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
    * @var array
    */
    protected $casts = [
        'is_default' => 'boolean',
        'active'     => 'boolean'
    ];

    /**
    * Events
    *
    * @return Collection
    */
    public function events()
    {
        return $this->hasMany(Event::class, 'category_id');
    }

    /**
    * Locations
    *
    * @return Collection
    */
    public function locations()
    {
        return $this->hasMany(Location::class, 'category_id');
    }

    /**
    * Is Active
    *
    * @param object $query
    *
    * @return object
    */
    public function scopeIsActive($query)
    {
        return $query->where('active', '=', true);
    }

    /**
     * Get Slug options
     *
     * @return SlugOptions
     */
    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * To Searchable Array
     *
     * @return array
     */
    public function toSearchableArray()
    {
        // category data
        $fields = [
            'id',
            'name',
            'slug',
            'active',
            'is_default'
        ];

        $category = [];
        foreach($fields as $field) {
            $category[$field] = $this->$field;
        }

        $category['created_at'] = $this->created_at->toAtomString();
        $category['updated_at'] = $this->updated_at->toAtomString();

        return $category;
    }

    /**
     * Get Mongo Array
     *
     * @param boolean $includeRelationships
     *
     * @return array
     */
    public function getMongoArray($includeRelationships = true)
    {
        // category data
        $fields = [
            'id',
            'name',
            'slug',
            'active',
            'is_default'
        ];

        $category = [];
        foreach($fields as $field) {
            $category[$field] = $this->$field;
        }

        $category['created_at'] = $this->created_at->toAtomString();
        $category['updated_at'] = $this->updated_at->toAtomString();

        // events
        $events = [];
        foreach($this->events as $event) {
            $events[] = $event->getMongoArray(false);
        }

        $category['events'] = $events;

        // locations
        $locations = [];
        foreach($this->locations as $location) {
            $locations[] = $location->getMongoArray(false);
        }

        $category['locations'] = $locations;

        return $category;
    }
}
