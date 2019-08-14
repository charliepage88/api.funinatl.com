<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use ScoutElastic\Searchable;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

use App\Collections\MusicBandCollection;

class MusicBand extends Model implements HasMedia
{
    use HasMediaTrait,
        HasSlug,
        Searchable;

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
    * @var string
    */
    protected $indexConfigurator = MusicBandIndexConfigurator::class;

    /**
    * @var array
    */
    protected $searchRules = [
        SearchMusicBandsRule::class
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
                'analyzer' => 'band_analyzer'
            ],

            'slug' => [
                'type' => 'text'
            ],

            'photo' => [
                'type' => 'text'
            ],

            'spotify_url' => [
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
        $photos = $this->getMedia('bands');

        if ($photos->count()) {
            $photo = env('DO_SPACES_URL') . '/' . $photos->first()->getPath();
        } else {
            $photo = null;
        }

        return $photo;
    }

    /**
    * Get Spotify Url Attribute
    *
    * @return string|null
    */
    public function getSpotifyUrlAttribute()
    {
        if (empty($this->spotify_json)) {
            return null;
        }

        return \igorw\get_in($this->spotify_json, [ 'external_urls', 'spotify' ]);
    }

    /**
    * To Searchable Array
    *
    * @return array
    */
    public function toSearchableArray()
    {
        $fields = [
            'id',
            'name',
            'slug'
        ];

        $band = [];
        foreach($fields as $field) {
            $band[$field] = $this->$field;
        }

        $band['photo'] = $this->photo_url;
        $band['spotify_url'] = $this->spotify_url;
        $band['created_at'] = $this->created_at->toAtomString();
        $band['updated_at'] = $this->updated_at->toAtomString();

        return $band;
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
        $fields = [
            'id',
            'name',
            'slug'
        ];

        $band = [];
        foreach($fields as $field) {
            $band[$field] = $this->$field;
        }

        $band['photo'] = $this->photo_url;
        $band['spotify_url'] = $this->spotify_url;
        $band['created_at'] = $this->created_at->toAtomString();
        $band['updated_at'] = $this->updated_at->toAtomString();

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
