<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\HybridRelations;
use ScoutElastic\Searchable;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

use App\Collections\EventCollection;

use DB;

class Event extends Model implements HasMedia
{
    use HasMediaTrait,
        HasSlug,
        HasTags,
        Searchable,
        SoftDeletes;

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
        'is_family_friendly',
        'is_explicit'
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
        'start_date'         => 'date',
        'end_date'           => 'date',
        'featured'           => 'boolean',
        'active'             => 'boolean',
        'is_sold_out'        => 'boolean',
        'is_family_friendly' => 'boolean'
    ];

    /**
    * @var boolean
    */
    public $asYouType = true;

    /**
    * @var string
    */
    protected $indexConfigurator = EventIndexConfigurator::class;

    /**
    * @var array
    */
    protected $searchRules = [
        SearchEventsRule::class
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
                'analyzer' => 'event_analyzer'
            ],
            'slug' => [
                'type' => 'text'
            ],
            'location_id' => [
                'type' => 'integer'
            ],
            'user_id' => [
                'type' => 'integer'
            ],
            'category_id' => [
                'type' => 'integer'
            ],
            'event_type_id' => [
                'type' => 'integer'
            ],
            'start_date' => [
                'type' => 'date'
            ],
            'end_date' => [
                'type' => 'date'
            ],
            'start_time' => [
                'type' => 'text'
            ],
            'end_time' => [
                'type' => 'text'
            ],
            'price' => [
                'type' => 'text'
            ],
            'short_description' => [
                'type' => 'text'
            ],
            'description' => [
                'type' => 'text'
            ],
            'featured' => [
                'type' => 'boolean'
            ],
            'is_sold_out' => [
                'type' => 'boolean'
            ],
            'website' => [
                'type' => 'text'
            ],
            'is_family_friendly' => [
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
            ],
            'event_type' => [
                'type' => 'text'
            ],

            // relations
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
            ],

            'location' => [
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
                    ]
                ]
            ],

            'bands' => [
                'type' => 'nested',

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

                    'photo' => [
                        'type' => 'text'
                    ],

                    'spotify_url' => [
                        'type' => 'text'
                    ]
                ]
            ]
        ]
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
    * Bands
    *
    * @return Collection
    */
    public function bands()
    {
        return $this->belongsToMany(MusicBand::class, 'event_music_bands', 'event_id', 'music_band_id');
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
    * Should Show
    *
    * @param object $query
    *
    * @return object
    */
    public function scopeShouldShow($query)
    {
        $now = Carbon::now()->format('Y-m-d');

        return $query->where('active', '=', true)
            ->where('is_explicit', '=', false)
            ->where('start_date', '>=', $now);
    }

    /**
    * Get Photo Url Attribute
    *
    * @return stirng|null
    */
    public function getPhotoUrlAttribute()
    {
        $photos = $this->getMedia('events');

        if ($photos->count()) {
            $photo = env('DO_SPACES_URL') . '/' . $photos->first()->getPath();
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
            ->generateSlugsFrom(function ($event) {
                $name = Str::slug($event->name, '-');
                $date = Str::slug($event->start_date->format('F-j-Y'), '-');
                $category = Str::slug($event->category->name, '-');
                $location = Str::slug($event->location->name, '-');

                return $name . '-' . $date . '-' . $category . '-' . $location;
            })
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
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        // event data
        $fields = [
            'id',
            'name',
            'slug',
            'location_id',
            'user_id',
            'category_id',
            'event_type_id',
            'start_date',
            'end_date',
            'price',
            'start_time',
            'end_time',
            'short_description',
            'description',
            'featured',
            'is_sold_out',
            'website',
            'is_family_friendly'
        ];

        $event = [];
        foreach($fields as $field) {
            $event[$field] = $this->$field;
        }

        $event['photo'] = $this->photo_url;
        $event['tags'] = $this->list_tags;
        $event['created_at'] = $this->created_at->toAtomString();
        $event['updated_at'] = $this->updated_at->toAtomString();
        $event['start_date'] = $this->start_date->format('Y-m-d');

        if (!empty($event['end_date'])) {
            $event['end_date'] = $this->end_date->format('Y-m-d');
        }

        $event['category'] = $this->category->toSearchableArray();
        $event['event_type'] = $this->eventType->name;
        $event['location'] = $this->location->toSearchableArray();

        if ($this->bands->count()) {
            $event['bands'] = $this->bands->toSearchableArray();
        }

        return $event;
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
        // event data
        $fields = [
            'id',
            'name',
            'slug',
            'location_id',
            'user_id',
            'category_id',
            'event_type_id',
            'start_date',
            'end_date',
            'price',
            'start_time',
            'end_time',
            'short_description',
            'description',
            'featured',
            'is_sold_out',
            'website',
            'is_family_friendly'
        ];

        $event = [];
        foreach($fields as $field) {
            $event[$field] = $this->$field;
        }

        $event['photo'] = $this->photo_url;
        $event['tags'] = $this->list_tags;
        $event['created_at'] = $this->created_at->toAtomString();
        $event['updated_at'] = $this->updated_at->toAtomString();
        $event['start_date'] = $this->start_date->format('Y-m-d');

        if (!empty($event['end_date'])) {
            $event['end_date'] = $this->end_date->format('Y-m-d');
        }

        $event['category'] = [
            'name' => $this->category->name,
            'slug' => $this->category->slug
        ];

        $event['category_slug'] = $this->category->slug;
        $event['location_slug'] = $this->location->slug;

        $event['event_type'] = $this->eventType->name;
        $event['location'] = $this->location->getMongoArray(false);

        if ($this->bands->count()) {
            $event['bands'] = $this->bands->getMongoArray();
        }

        return $event;
    }

    /**
    * Sync Bands
    *
    * @param array $bands
    *
    * @return Event
    */
    public function syncBands(array $bands)
    {
        if (!empty($bands)) {
            // lookup current band names first
            // if exact match, don't do anything
            $currentBands = $this->bands()->pluck('name')->toArray();

            if (!empty($currentBands) && $currentBands === $bands) {
                return $this;
            }

            // create band records or retrieve model
            // put ID's into array to sync after
            $bandIds = [];
            foreach($bands as $bandName) {
                $band = MusicBand::firstOrCreate([ 'name' => $bandName ]);

                if (!empty($band)) {
                    $bandIds[] = $band->id;
                }
            }

            // get unique band ids
            // and sync to event
            if (!empty($bandIds)) {
                $bandIds = array_unique($bandIds);

                $this->bands()->sync($bandIds);
            }
        }

        return $this;
    }

    /**
    * Get First Band With Image
    *
    * @return MusicBand|null
    */
    public function getFirstBandWithImage()
    {
        $band = null;
        if ($this->bands->count()) {
            $bands = $this->bands()->get();
            $bandIds = $bands->pluck('id')->toArray();

            $mediaFind = DB::table('media')
                ->where('model_type', '=', 'App\MusicBand')
                ->whereIn('model_id', $bandIds)
                ->orderBy('order_column', 'asc')
                ->first();

            if (!empty($mediaFind)) {
                $band = $bands->where('id', '=', $mediaFind->model_id)->first();
            }
        }

        return $band;
    }

    /**
    * Get Start Time Formatted Attribute
    *
    * @return string
    */
    public function getStartTimeFormattedAttribute()
    {
        $value = null;

        if (!empty($this->start_time) && !empty($this->start_date)) {
            $date = $this->start_date->format('Y-m-d');

            $value = Carbon::parse($date . ' ' . $this->start_time);
            $value = $value->format('Y-m-d H:i:s');
        }

        return $value;
    }

    /**
    * Get End Time Formatted Attribute
    *
    * @return string
    */
    public function getEndTimeFormattedAttribute()
    {
        $value = null;
        $date = null;

        if (!empty($this->end_time)) {
            if (!empty($this->end_date)) {
                $date = $this->end_date->format('Y-m-d');
            } elseif (!empty($this->start_date)) {
                $date = $this->start_date->format('Y-m-d');
            }

            if (!empty($date)) {
                $value = Carbon::parse($date . ' ' . $this->end_time);
                $value = $value->format('Y-m-d H:i:s');
            }
        }

        return $value;
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new EventCollection($models);
    }
}
