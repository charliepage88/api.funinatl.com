<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use ScoutElastic\Searchable;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

use App\Collections\CategoryCollection;
use App\Traits\SlugExtend;

class Category extends Model implements HasMedia
{
    use HasMediaTrait,
        HasSlug,
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
    * @var string
    */
    protected $indexConfigurator = CategoryIndexConfigurator::class;

    /**
    * @var array
    */
    protected $searchRules = [
        SearchCategoriesRule::class
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
                'type' => 'text',
                'fields' => [
                    'raw' => [
                        'type' => 'keyword'
                    ]
                ],
                'analyzer' => 'category_analyzer'
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
    * Active Events
    *
    * @return Collection
    */
    public function activeEvents()
    {
        return $this->events()->shouldShow()->get();
    }

    /**
    * Active Locations
    *
    * @return Collection
    */
    public function activeLocations()
    {
        return $this->locations()->isActive()->get();
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
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    /**
    * Get Photo Url Attribute
    *
    * @return stirng|null
    */
    public function getPhotoUrlAttribute()
    {
        $photos = $this->getMedia('categories');

        if ($photos->count()) {
            $photo = config('filesystems.disks.spaces.url') . '/' . $photos->first()->getPath();
        } else {
            $photo = null;
        }

        return $photo;
    }

    /**
    * Register Media Collections
    *
    * @return void
    */
    public function registerMediaCollections()
    {
        $this
           ->addMediaCollection('categories')
           ->useDisk('spaces');
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
        // category data
        $fields = [
            'id',
            'name',
            'slug',
            'is_default'
        ];

        $category = [];
        foreach($fields as $field) {
            $category[$field] = $this->$field;
        }

        $category['photo'] = $this->photo_url;
        $category['created_at'] = $this->created_at->toAtomString();
        $category['updated_at'] = $this->updated_at->toAtomString();

        return $category;
    }

    /**
     * Get Mongo Array
     *
     * @param bool $includeRelationships
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
            'is_default'
        ];

        $category = [];
        foreach($fields as $field) {
            $category[$field] = $this->$field;
        }

        $category['photo'] = $this->photo_url;
        $category['created_at'] = $this->created_at->toAtomString();
        $category['updated_at'] = $this->updated_at->toAtomString();

        // events
        if ($includeRelationships) {
            $events = [];
            foreach($this->activeEvents() as $event) {
                $events[] = $event->getMongoArray(false);
            }

            $category['events'] = $events;
        }

        // locations
        if ($includeRelationships) {
            $locations = [];
            foreach($this->activeLocations() as $location) {
                $locations[] = $location->getMongoArray(false);
            }

            $category['locations'] = $locations;
        }

        return $category;
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new CategoryCollection($models);
    }
}
