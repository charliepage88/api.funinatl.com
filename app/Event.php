<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\HybridRelations;
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
        HybridRelations,
        Searchable;

    protected $connection = 'mysql';

    /*
    * @var array
    */
    protected $fillable = [
        'name',
        'slug',
        'location_id',
        'user_id',
        'category_id',
        'start_date',
        'end_date',
        'price',
        'start_time',
        'end_time',
        'short_description',
        'description',
        'featured',
        'active',
        'website',
        'is_sold_out',
        'spotify_artist_id'
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
        'start_date'  => 'date',
        'end_date'    => 'date',
        'featured'    => 'boolean',
        'active'      => 'boolean',
        'is_sold_out' => 'boolean'
    ];

    /**
    * Location
    *
    * @return Location
    */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
    * Category
    *
    * @return Category
    */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
    * EventType
    *
    * @return EventType
    */
    public function eventType()
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    /**
    * User
    *
    * @return User
    */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
    * Is Featured
    *
    * @param object $query
    *
    * @return object
    */
    public function scopeIsFeatured($query)
    {
        return $query->where('featured', '=', true);
    }

    /**
    * Get Photo Url Attribute
    *
    * @return stirng|null
    */
    public function getPhotoUrlAttribute()
    {
        $photos = $this->getMedia('images');

        if ($photos->count()) {
            $photo = env('DO_SPACES_URL') . '/' . $photos->first()->getPath();
        } else {
            $photo = null;
        }

        return $photo;
    }

    /**
    * Get List Tags Attribute
    *
    * @return array
    */
    public function getListTagsAttribute()
    {
        $data = $this->tags;

        $tags = [];
        foreach($data as $tag) {
            $tags[] = [
                'name' => $tag->name,
                'slug' => $tag->slug
            ];
        }

        return $tags;
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
    * Should Be Searchable
    *
    * @return boolean
    */
    public function shouldBeSearchable()
    {
        return $this->active;
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $event = $this->toArray();

        $event['photo'] = $this->photo_url;
        $event['tags'] = $this->list_tags;
        $event['created_at'] = $this->created_at->toAtomString();
        $event['updated_at'] = $this->updated_at->toAtomString();
        $event['category'] = $this->category->name;
        $event['event_type'] = $this->eventType->name;

        $location = $this->location->toArray();

        $location['created_at'] = $this->location->created_at->toAtomString();
        $location['updated_at'] = $this->location->updated_at->toAtomString();

        $event['location'] = $location;

        return $event;
    }

    /**
     * Get Mongo Array
     *
     * @return array
     */
    public function getMongoArray()
    {
        $event = $this->toArray();

        $event['photo'] = $this->photo_url;
        $event['tags'] = $this->list_tags;
        $event['created_at'] = $this->created_at->toAtomString();
        $event['updated_at'] = $this->updated_at->toAtomString();
        $event['category'] = $this->category->name;
        $event['event_type'] = $this->eventType->name;

        $location = $this->location->toArray();

        $location['created_at'] = $this->location->created_at->toAtomString();
        $location['updated_at'] = $this->location->updated_at->toAtomString();
        $location['photo'] = $this->location->photo_url;

        $event['location'] = $location;

        return $event;
    }
}
