<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class EventType extends Model
{
    use HasSlug;

    /*
    * @var array
    */
    protected $fillable = [
        'name',
        'slug'
    ];

    /**
    * Events
    *
    * @return Collection
    */
    public function events()
    {
        return $this->hasMany(Event::class, 'event_type_id');
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
}
