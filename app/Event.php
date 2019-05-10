<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

class Event extends Model implements HasMedia
{
    use HasMediaTrait,
        HasSlug,
        HasTags,
        Searchable;

    /*
    * @var array
    */
    protected $fillable = [
        'name',
        'slug',
        'location_id',
        'user_id',
        'category_id',
        'date',
        'price',
        'start_time',
        'end_time',
        'short_description',
        'description',
        'featured',
        'active',
        'website'
    ];

    /*
    * @var array
    */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /*
    * @var array
    */
    protected $casts = [
        'date'     => 'date',
        'featured' => 'boolean',
        'active'   => 'boolean'
    ];

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
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs()
    {
        return 'events';
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $array = $this->toArray();

        return $array;
    }
}
