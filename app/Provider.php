<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Provider extends Model
{
    use HasSlug;

    /*
    * @var array
    */
    protected $fillable = [
        'name',
        'slug',
        'website',
        'last_scraped',
        'scrape_url',
        'active'
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
        'last_scraped' => 'datetime',
        'active'       => 'boolean'
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
}
