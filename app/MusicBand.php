<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class MusicBand extends Model implements HasMedia
{
    use HasMediaTrait,
        HasSlug;

    /**
    * @var array
    */
    protected $fillable = [
        'name',
        'slug',
        'spotify_artist_id',
        'spotify_json'
    ];

    /**
    * @var array
    */
    protected $casts = [
        'spotify_json' => 'array'
    ];

    /**
    * Events
    *
    * @return Collection
    */
    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_music_bands', 'music_band_id', 'event_id');
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
    * Get Photo Url Attribute
    *
    * @return stirng|null
    */
    public function getPhotoUrlAttribute()
    {
        $photos = $this->getMedia('bands');

        if ($photos->count()) {
            $photo = env('DO_SPACES_URL') . '/' . $photos->first()->getPath();
        } else {
            $photo = null;
        }

        return $photo;
    }
}
