<?php

namespace App;

use Geocoder\Query\GeocodeQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use ScoutElastic\Searchable;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

use App\Tag;
use App\Facades\Geocoder;
use App\Traits\SlugExtend;

class Location extends Model implements HasMedia
{
    use HasMediaTrait,
        HasSlug,
        HasTags,
        Searchable,
        SlugExtend;

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
        'is_family_friendly',
        'active'
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
        'is_family_friendly' => 'boolean',
        'active'             => 'boolean'
    ];

    /**
    * @var string
    */
    protected $indexConfigurator = LocationIndexConfigurator::class;

    /**
    * @var array
    */
    protected $searchRules = [
        SearchLocationsRule::class
    ];

    /**
    * @var array
    */
    protected $mapping = [
        'properties' => [
            'id' => [
                'type' => 'integer'
            ],

            'name' => [
                'type' => 'text'
            ],

            'slug' => [
                'type' => 'text'
            ],

            'category_id' => [
                'type' => 'integer'
            ],

            'website' => [
                'type' => 'text'
            ],

            'address' => [
                'type' => 'text'
            ],

            'city' => [
                'type' => 'text'
            ],

            'state' => [
                'type' => 'text'
            ],

            'zip' => [
                'type' => 'text'
            ],

            'geo' => [
                'type' => 'geo_point'
            ],

            'description' => [
                'type' => 'text'
            ],

            'is_family_friendly' => [
                'type' => 'boolean'
            ],

            'photo' => [
                'type' => 'text'
            ],

            'thumb_small' => [
                'type' => 'text'
            ],

            'thumb_medium' => [
                'type' => 'text'
            ],

            'created_at' => [
                'type' => 'date'
            ],

            'updated_at' => [
                'type' => 'date'
            ],

            'tags' => [
                'type' => 'nested',

                'properties' => [
                    'name' => [
                        'type' => 'text'
                    ],

                    'slug' => [
                        'type' => 'text'
                    ]
                ]
            ],

            'category' => [
                'properties' => [
                    'id' => [
                        'type' => 'integer'
                    ],

                    'name' => [
                        'type' => 'text'
                    ],

                    'slug' => [
                        'type' => 'text'
                    ],

                    'is_default' => [
                        'type' => 'boolean'
                    ],

                    'photo' => [
                        'type' => 'text'
                    ],

                    'created_at' => [
                        'type' => 'date'
                    ],

                    'updated_at' => [
                        'type' => 'date'
                    ]
                ]
            ]
        ]
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
    * Active Events
    *
    * @return Collection
    */
    public function activeEvents()
    {
        return $this->events()->shouldShow()->get();
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
        $photos = $this->getMedia('locations');

        if ($photos->count()) {
            $photo = env('DO_SPACES_URL') . '/' . $photos->first()->getPath();
        } else {
            $photo = null;
        }

        return $photo;
    }

    /**
    * Get Thumb Small Url Attribute
    *
    * @return stirng|null
    */
    public function getThumbSmallUrlAttribute()
    {
        $photos = $this->getMedia('locations');

        if ($photos->count()) {
            $photo = env('DO_SPACES_URL') . '/' . $photos->first()->getPath('thumb_small');
        } else {
            $photo = null;
        }

        return $photo;
    }

    /**
    * Get Thumb Medium Url Attribute
    *
    * @return stirng|null
    */
    public function getThumbMediumUrlAttribute()
    {
        $photos = $this->getMedia('locations');

        if ($photos->count()) {
            $photo = env('DO_SPACES_URL') . '/' . $photos->first()->getPath('thumb_medium');
        } else {
            $photo = null;
        }

        return $photo;
    }

    /**
    * Get Tag Class Name
    *
    * @return string
    */
    public static function getTagClassName(): string
    {
        return Tag::class;
    }

    /**
    * Tags
    *
    * @return MorphToMany
    */
    public function tags(): MorphToMany
    {
        return $this
            ->morphToMany(self::getTagClassName(), 'taggable', 'taggables', null, 'tag_id')
            ->orderBy('order_column');
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
    * Get List Tags String Attribute
    *
    * @return array
    */
    public function getListTagsStringAttribute()
    {
        $data = $this->tags;

        $tags = [];
        foreach($data as $tag) {
            $tags[] = $tag->name;
        }

        return implode(', ', $tags);
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
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
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
            'description',
            'is_family_friendly'
        ];

        $location = [];
        foreach($fields as $field) {
            $location[$field] = $this->$field;
        }

        $location['geo'] = [
            'lat' => $this->latitude,
            'lon' => $this->longitude
        ];
        $location['photo'] = $this->photo_url;
        $location['thumb_small'] = $this->thumb_small_url;
        $location['thumb_medium'] = $this->thumb_medium_url;
        $location['tags'] = $this->list_tags;
        $location['created_at'] = $this->created_at->toAtomString();
        $location['updated_at'] = $this->updated_at->toAtomString();

        // category
        if (!empty($this->category)) {
            $location['category'] = $this->category->toSearchableArray();
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
        $location['thumb_small'] = $this->thumb_small_url;
        $location['thumb_medium'] = $this->thumb_medium_url;
        $location['tags'] = $this->list_tags;
        $location['created_at'] = $this->created_at->toAtomString();
        $location['updated_at'] = $this->updated_at->toAtomString();

        // category
        if (!empty($this->category)) {
            $category = [];

            $category['id'] = $this->category->id;
            $category['name'] = $this->category->name;
            $category['slug'] = $this->category->slug;
            $category['is_default'] = $this->category->is_default;
            $category['photo'] = $this->category->photo_url;
            $category['created_at'] = $this->category->created_at->toAtomString();
            $category['updated_at'] = $this->category->updated_at->toAtomString();

            $location['category'] = $category;
        }

        // events
        if ($includeRelationships) {
            $events = [];
            foreach($this->activeEvents() as $event) {
                $events[] = $event->getMongoArray(false);
            }

            $location['events'] = $events;
        }

        return $location;
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
    * Register Media Conversions
    *
    * @param Media|null $media
    *
    * @return void
    */
    public function registerMediaConversions(Media $media = null)
    {
        $this->addMediaConversion('thumb_small')
              ->optimize()
              ->fit(Manipulations::FIT_CROP, 96, 96);

        $this->addMediaConversion('thumb_medium')
              ->optimize()
              ->fit(Manipulations::FIT_CROP, 128, 128);
    }
}
