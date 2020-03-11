<?php

namespace App;

use Appstract\Meta\Metable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use ScoutElastic\Searchable;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

use App\Collections\EventCollection;

use DB;
use Storage;

class Event extends Model implements HasMedia
{
    use InteractsWithMedia,
        HasSlug,
        HasTags,
        Metable,
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
        'is_explicit',
        'event_type_id'
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
            'thumb_mobile' => [
                'type' => 'text'
            ],
            'thumb_tablet' => [
                'type' => 'text'
            ],
            'thumb_desktop' => [
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
    * @var string
    */
    public $locationName;

    /**
    * @var string
    */
    public $categoryName;

    /**
    * @var boolean
    */
    // public $registerMediaConversionsUsingModelInstance = true;

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
        $tbl = 'event_music_bands';
        $class = MusicBand::class;

        return $this->belongsToMany($class, $tbl, 'event_id', 'music_band_id');
    }

    /**
    * Scope Is Active
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
    * Scope Is Featured
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
    * Scope Should Show
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
    * Scope Should Not Show
    *
    * @param object $query
    *
    * @return object
    */
    public function scopeShouldNotShow($query)
    {
        $now = Carbon::now()->format('Y-m-d');

        return $query->where('active', '=', true)
            // ->where('is_explicit', '=', false)
            ->where('start_date', '<', $now);
    }

    /**
    * Scope By Date
    *
    * @param object $query
    * @param string $date
    *
    * @return object
    */
    public function scopeByDate($query, string $date)
    {
        return $query->where('start_date', '=', $date)->get();
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
            $photo = config('filesystems.disks.spaces.url') . '/' . $photos->first()->getPath();
        } else {
            $photo = null;
        }

        return $photo;
    }

    /**
    * Get Thumb Mobile Url Attribute
    *
    * @return stirng|null
    */
    public function getThumbMobileUrlAttribute()
    {
        $photos = $this->getMedia('events');

        if ($photos->count()) {
            $photo = config('filesystems.disks.spaces.url') . '/' . $photos->first()->getPath('thumb_mobile');
        } else {
            $photo = null;
        }

        return $photo;
    }

    /**
    * Get Thumb Tablet Url Attribute
    *
    * @return stirng|null
    */
    public function getThumbTabletUrlAttribute()
    {
        $photos = $this->getMedia('events');

        if ($photos->count()) {
            $photo = config('filesystems.disks.spaces.url') . '/' . $photos->first()->getPath('thumb_tablet');
        } else {
            $photo = null;
        }

        return $photo;
    }

    /**
    * Get Thumb Desktop Url Attribute
    *
    * @return stirng|null
    */
    public function getThumbDesktopUrlAttribute()
    {
        $photos = $this->getMedia('events');

        if ($photos->count()) {
            $photo = config('filesystems.disks.spaces.url') . '/' . $photos->first()->getPath('thumb_desktop');
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
        $event['thumb_mobile'] = $this->thumb_mobile_url;
        $event['thumb_tablet'] = $this->thumb_tablet_url;
        $event['thumb_desktop'] = $this->thumb_desktop_url;
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
     * Get Formatted Array
     *
     * @param bool $includeRelationships
     *
     * @return array
     */
    public function getFormattedArray($includeRelationships = true)
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
        $event['thumb_mobile'] = $this->thumb_mobile_url;
        $event['thumb_tablet'] = $this->thumb_tablet_url;
        $event['thumb_desktop'] = $this->thumb_desktop_url;
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
        $event['location'] = $this->location->getFormattedArray(false);

        if ($this->bands->count()) {
            $event['bands'] = $this->bands->getFormattedArray();
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
        } else {
            $this->bands()->detach();
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

        // populate image for band
        if (!empty($band)) {
            $imageUrl = $band->photo_url;

            // get image contents
            $contents = file_get_contents($imageUrl);

            // get image info
            $info = pathinfo($imageUrl);

            // set filename & path
            if (!empty($info['extension'])) {
                $extension = $info['extension'];
            } else {
                $extension = '.jpeg';
            }

            $filename = $this->id . '-' . $this->slug;
            $filename = $filename . $extension;
            $tmpPath = storage_path('app') . '/' . $filename;

            // store locally for a moment
            Storage::disk('local')->put($filename, $contents);

            // then add the url
            $this->addMedia($tmpPath)->toMediaCollection('events');

            \Log::info('Uploaded image for event #' . $this->id);
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
    * Register Media Collections
    *
    * @return void
    */
    public function registerMediaCollections(): void
    {
        $this
           ->addMediaCollection('events')
           ->useDisk('spaces')
           ->singleFile();
    }

    /**
    * Register Media Conversions
    *
    * @param Media|null $media
    *
    * @return void
    */
    public function registerMediaConversions(?Media $media = NULL): void
    {
        // init vars
        $useDefaults = true;
        /*
        $photoUrl = $this->photo_url;

        // if has photo, then check size
        // if < min requirements, fit & fill
        if (!empty($photoUrl)) {
            // \Log::info('registerMediaConversions -> event `' . $this->id . '`');
            // \Log::info($photoUrl);

            list($width, $height) = getimagesize($photoUrl);

            // \Log::info($width . ' x ' . $height);

            if ($width < 726 || $height < 250) {
                $useDefaults = false;
            }
        }
        */

        if ($useDefaults) {
            // \Log::info('useDefaults -> event `' . $this->id . '` -> true');

            $this->addMediaConversion('thumb_mobile')
                ->optimize()
                ->fit(Manipulations::FIT_CROP, 372, 250);

            $this->addMediaConversion('thumb_tablet')
                ->optimize()
                ->fit(Manipulations::FIT_CROP, 726, 250);

            $this->addMediaConversion('thumb_desktop')
                ->optimize()
                ->fit(Manipulations::FIT_CROP, 608, 250);
        } else {
            // \Log::info('useDefaults -> event `' . $this->id . '` -> false');

            $this->addMediaConversion('thumb_mobile')
                ->optimize()
                ->fit(Manipulations::FIT_FILL, 372, 250)
                ->background('000000');

            $this->addMediaConversion('thumb_tablet')
                ->optimize()
                ->fit(Manipulations::FIT_FILL, 726, 250)
                ->background('000000');

            $this->addMediaConversion('thumb_desktop')
                ->optimize()
                ->fit(Manipulations::FIT_FILL, 608, 250)
                ->background('000000');
        }
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

    /**
    * Get Similar Event Attribute
    *
    * @param string $attribute
    *
    * @return null|string
    */
    public function getSimilarEventAttribute($attribute)
    {
        // init var
        $value = null;

        // do the query
        $query = Event::where('name', '=', $this->name)
            ->where('start_date', '=', $this->start_date)
            ->where('location_id', '=', $this->location_id)
            ->where('category_id', '=', $this->category_id);

        if (!empty($this->id)) {
            $query->where('id', '!=', $this->id);
        }

        $lookup = $query->first();

        if (!empty($lookup)) {
            $value = $lookup->$attribute;
        }

        return $value;
    }

    /**
    * Get Location Name
    *
    * @return string
    */
    public function getLocationName()
    {
        // location ID check
        if (empty($this->location_id)) {
            \Log::error('Empty location ID for event...');
            \Log::error(json_encode($this->toArray()));

            return null;
        }

        // see if location name set already
        if (!empty($this->locationName)) {
            return $this->locationName;
        }

        // lookup location name
        if (empty($this->location)) {
            $location = Location::find($this->location_id);
        } else {
            $location = $this->location;
        }

        $this->locationName = optional($location)->name;

        return $this->locationName;
    }

    /**
    * Get Category Name
    *
    * @return string
    */
    public function getCategoryName()
    {
        // category ID check
        if (empty($this->category_id)) {
            \Log::error('Empty category ID for event...');
            \Log::error(json_encode($this->toArray()));

            return null;
        }

        // see if category name set already
        if (!empty($this->categoryName)) {
            return $this->categoryName;
        }

        // lookup category name
        if (empty($this->category)) {
            $category = Category::find($this->category_id);
        } else {
            $category = $this->category;
        }

        $this->categoryName = optional($category)->name;

        return $this->categoryName;
    }

    /**
    * Generate Name
    *
    * @return string
    */
    public function generateName()
    {
        // init vars
        $name = strtolower(trim($this->name));
        $maxNameLength = 65;

        // replace characters we don't want
        // and other words
        $find = [
            '’'
        ];

        $name = str_replace($find, '', $name);

        // check for "at Location" string
        // and other similar checks
        if (!empty($this->location_id)) {
            $locationName = $this->getLocationName();

            $replacements = [
                $locationName . " presents ",
                $locationName . "'s presents ",
                " at " . $locationName,
                " at " . $locationName . "'s",
            ];

            $name = str_replace($replacements, '', $name);
        }

        // max length for name
        $newNameLength = strlen($name);
        if ($newNameLength >= $maxNameLength) {
            \Log::error('Event `' . $this->id . '` has generated name `' . $name . '` which is longer than the max. Length: ' . $newNameLength . ' :: Max Length: ' . $maxNameLength);
        }

        return Str::title($name);
    }

    /**
    * Generate Short Description
    *
    * @param array $bands
    *
    * @return string
    */
    public function generateShortDescription($bands = [])
    {
        // init vars
        $name = $this->name;
        $description = trim($this->short_description);
        $minLength = 100;
        $maxLength = 150;

        // no bands? try to get them fresh
        if (empty($bands)) {
            $bands = $this->bands()->pluck('name')->toArray();
        }

        // method to generate description text
        $category = $this->getCategoryName();
        $location = $this->getLocationName();
        $time = $this->start_time;
        $date = $this->start_date->format('l, F jS');

        $getDescription = function () use ($bands, $time, $date, $category, $location) {
            // empty category/location check
            if (empty($category) || empty($location)) {
                \Log::error('cannot get description, empty location or category...');

                return null;
            }

            if ($category === 'Music') {
                if (!empty($bands)) {
                    $description = 'See live music at ' . $location . ' on ' . $date . '. Starting at ' . $time . ', with bands including ' . implode(', ', $bands) . '.';
                } else {
                    $description = 'See live music at ' . $location . ' on ' . $date . '. Starting at ' . $time . ' with plenty of great music.';
                }
            } else {
                switch ($category) {
                    case 'Other':
                        $description = 'On ' . $date . ', starting at ' . $time . '. Go check out this event at ' . $location . '!';
                    break;

                    case 'Comedy':
                        $description = 'Catch some laughs at ' . $location . ' on ' . $date . ', starting at ' . $time . '. Drink minimum may apply.';
                    break;

                    default:
                        $description = 'On ' . $date . ', starting at ' . $time . '. Enjoy ' . $category . ' with this event at ' . $location;
                    break;
                }
            }

            return $description;
        };

        // look for existing event with same name
        // if there is one, use the description from that
        $lookup = $this->getSimilarEventAttribute('short_description');

        if (!empty($lookup)) {
            $description = $lookup;
        }

        // does event have description, but not a short one?
        if (empty($description) && !empty($this->description)) {
            $description = Str::limit($this->description, 150);
        }

        // if no description, let's generate one
        if (empty($description)) {
            $description = $getDescription();
        }

        // check max/min length
        $newDescriptionLength = strlen($description);

        if ($newDescriptionLength < $minLength) {
            $description = $getDescription();
        }

        if ($newDescriptionLength > $maxLength) {
            $description = $getDescription();
        }

        // debugging for check length
        if ($newDescriptionLength < $minLength) {
            \Log::error('Event `' . $this->id . '` has generated short description `' . $description . '` which is less than the minimum. Length: ' . $newDescriptionLength . ' :: Min Length: ' . $minLength);
        }

        if ($newDescriptionLength > $maxLength) {
            \Log::error('Event `' . $this->id . '` has generated short description `' . $description . '` which is longer than the max. Length: ' . $newDescriptionLength . ' :: Max Length: ' . $maxLength);
        }

        return $description;
    }

    /**
    * Generate Description
    *
    * @param array $bands
    *
    * @return string
    */
    public function generateDescription($bands = [])
    {
        // init vars
        $name = $this->name;
        $description = trim($this->description);
        $minLength = 100;
        $maxLength = 250;

        // no bands? try to get them fresh
        if (empty($bands)) {
            $bands = $this->bands()->pluck('name')->toArray();
        }

        // look for existing event with same name
        // if there is one, use the description from that
        $lookup = $this->getSimilarEventAttribute('description');

        if (!empty($lookup)) {
            $description = $lookup;
        }

        // check max/min length
        $newDescriptionLength = strlen($description);

        // debugging for check length
        // if ($newDescriptionLength < $minLength) {
        //     \Log::error('Event `' . $this->id . '` has generated description `' . $description . '` which is less than the minimum. Length: ' . $newDescriptionLength . ' :: Min Length: ' . $minLength);
        // }

        // if ($newDescriptionLength > $maxLength) {
        //     \Log::error('Event `' . $this->id . '` has generated description `' . $description . '` which is longer than the max. Length: ' . $newDescriptionLength . ' :: Max Length: ' . $maxLength);
        // }

        return $description;
    }

    /**
    * Get Is Tweetable Attribute
    *
    * @return bool
    */
    public function getIsTweetableAttribute()
    {
        $status = false;
        if ($event->hasMeta('is_tweetable')) {
            $meta = $event->getMeta('is_tweetable');

            $is_tweetable = ($meta->value === 'true' || $meta->value === true);

            if ($is_tweetable) {
                $status = true;
            }
        }

        return $status;
    }

    /**
    * Get Bands List Attribute
    *
    * @return array
    */
    public function getBandsListAttribute()
    {
        $bands = [];

        if ($this->bands->count()) {
            foreach($this->bands as $band) {
                $bands[] = $band->name;
            }
        }

        return $bands;
    }
}
