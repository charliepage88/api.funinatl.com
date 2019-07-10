<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

use App\Collections\MusicBandCollection;

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
        $event['tags'] = $this->list_tags_string;
        $event['created_at'] = $this->created_at->toAtomString();
        $event['updated_at'] = $this->updated_at->toAtomString();
        $event['start_date'] = $this->start_date->format('Y-m-d');

        if (!empty($event['end_date'])) {
            $event['end_date'] = $this->end_date->format('Y-m-d');
        }

        $event['category'] = $this->category->name;
        $event['event_type'] = $this->eventType->name;
        $event['location'] = $this->location->name;

        if ($this->bands->count()) {
            $event['bands'] = implode(', ', $this->bands()->pluck('name')->toArray());
        }

        return $event;
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
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new MusicBandCollection($models);
    }
}
