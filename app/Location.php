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

    /*
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
        $location = $this->toArray();

        $location['photo'] = $this->photo_url;
        $location['created_at'] = $this->created_at->toAtomString();
        $location['updated_at'] = $this->updated_at->toAtomString();
        $location['category'] = $this->category->name;

        // category
        $category = $this->category->toArray();

        $category['created_at'] = $this->category->created_at->toAtomString();
        $category['updated_at'] = $this->category->updated_at->toAtomString();

        $location['category'] = $category;

        // events
        $events = [];
        foreach($this->events as $event) {
            $events[] = $event->getMongoArray();
        }

        $location['events'] = $events;

        return $location;
    }

    /**
     * Get Mongo Array
     *
     * @return array
     */
    public function getMongoArray()
    {
        $location = $this->toArray();

        $location['photo'] = $this->photo_url;
        $location['created_at'] = $this->created_at->toAtomString();
        $location['updated_at'] = $this->updated_at->toAtomString();
        $location['category'] = $this->category->name;

        // category
        $category = $this->category->toArray();

        $category['created_at'] = $this->category->created_at->toAtomString();
        $category['updated_at'] = $this->category->updated_at->toAtomString();

        $location['category'] = $category;

        // events
        $events = [];
        foreach($this->events as $event) {
            $events[] = $event->getMongoArray();
        }

        $location['events'] = $events;

        return $location;
    }
}
