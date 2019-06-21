<?php

namespace App;

use Geocoder\Query\GeocodeQuery;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

use App\Facades\Geocoder;

class Location extends Model implements HasMedia
{
    use HasMediaTrait,
        HasSlug,
        HasTags,
        Searchable;

    /**
    * @var string
    */
    protected $connection = 'mysql';

    /**
    * @var array
    */
    protected $fillable = [
        'name',
        'slug',
        'address',
        'city',
        'state',
        'zip',
        'latitude',
        'longitude',
        'description',
        'category_id',
        'website',
        'is_family_friendly'
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
        'is_family_friendly' => 'boolean'
    ];

    /**
    * Events
    *
    * @return Collection
    */
    public function events()
    {
        return $this->hasMany(Event::class, 'location_id');
    }

    /**
    * Provider
    *
    * @return Provider
    */
    public function provider()
    {
        return $this->hasOne(Provider::class, 'location_id');
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
     * Geocode Address
     *
     * @return Location
     */
    public function geocodeAddress()
    {
        $parts = [
            $this->address,
            $this->city,
            $this->state,
            $this->zip
        ];

        $addressString = implode(', ', $parts);

        $gQuery = GeocodeQuery::create($addressString);
        $geoResults = Geocoder::geocodeQuery($gQuery);

        $address = $geoResults->first();

        $coords = $address->getCoordinates();

        $this->latitude = $coords->getLatitude();
        $this->longitude = $coords->getLongitude();

        $this->save();
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
     * To Searchable Array
     *
     * @return array
     */
    public function toSearchableArray()
    {
        // location data
        $fields = [
            'id',
            'name',
            'slug',
            'category_id',
            'website',
            'address',
            'city',
            'state',
            'zip',
            'latitude',
            'longitude',
            'description',
            'is_family_friendly'
        ];

        $location = [];
        foreach($fields as $field) {
            $location[$field] = $this->$field;
        }

        $location['photo'] = $this->photo_url;
        $location['tags'] = $this->list_tags;
        $location['created_at'] = $this->created_at->toAtomString();
        $location['updated_at'] = $this->updated_at->toAtomString();

        // category
        if (!empty($this->category)) {
            $category = [];

            $category['id'] = $this->category->id;
            $category['slug'] = $this->category->slug;
            $category['name'] = $this->category->name;
            $category['active'] = $this->category->active;
            $category['is_default'] = $this->category->is_default;
            $category['created_at'] = $this->category->created_at->toAtomString();
            $category['updated_at'] = $this->category->updated_at->toAtomString();

            $location['category'] = $category;
        }

        return $location;
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
        // location data
        $fields = [
            'id',
            'name',
            'slug',
            'category_id',
            'website',
            'address',
            'city',
            'state',
            'zip',
            'latitude',
            'longitude',
            'description',
            'is_family_friendly'
        ];

        $location = [];
        foreach($fields as $field) {
            $location[$field] = $this->$field;
        }

        $location['photo'] = $this->photo_url;
        $location['tags'] = $this->list_tags;
        $location['created_at'] = $this->created_at->toAtomString();
        $location['updated_at'] = $this->updated_at->toAtomString();

        // category
        if ($includeRelationships && !empty($this->category)) {
            $category = [];

            $category['id'] = $this->category->id;
            $category['slug'] = $this->category->slug;
            $category['name'] = $this->category->name;
            $category['active'] = $this->category->active;
            $category['is_default'] = $this->category->is_default;
            $category['created_at'] = $this->category->created_at->toAtomString();
            $category['updated_at'] = $this->category->updated_at->toAtomString();

            $location['category'] = $category;
        }

        // events
        if ($includeRelationships) {
            $events = [];
            foreach($this->events as $event) {
                $events[] = $event->getMongoArray(false);
            }

            $location['events'] = $events;
        }

        return $location;
    }
}
