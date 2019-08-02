<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use SpotifyWebAPI\SpotifyWebAPI;

use App\Category;
use App\Event;
use App\Location;
use App\MusicBand;
use App\Tag;

use Cache;
use DB;
use Storage;

class DevCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dev command for misc testing.';

    /**
     * @var boolean
     */
    public $enableSyncToSearch = true;

    /**
     * @var boolean
     */
    public $enableSync = true;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $this->flushMongo();
        // $this->flushJsonFiles();
        // $this->syncSpotifyMusicBands();

        // $this->eventsWithoutPhoto();
        // $this->locationsWithoutPhoto();
        // $this->syncMusicBands();
        // $this->regenerateEventSlugs();

        if ($this->enableSync) {
            $this->syncTagsToMongo();
            $this->syncMusicBandsToMongo();
            $this->syncCategoriesToMongo();
            $this->syncLocationsToMongo();
            $this->syncEventsToMongo();
            $this->syncDataToS3();
        }
    }

    /**
     * Flush Mongo
     *
     * @return void
     */
    public function flushMongo()
    {
        DB::connection('mongodb')->collection('tags')->delete();
        DB::connection('mongodb')->collection('music_bands')->delete();
        DB::connection('mongodb')->collection('categories')->delete();
        DB::connection('mongodb')->collection('locations')->delete();
        DB::connection('mongodb')->collection('events')->delete();
    }

    /**
    * Regenerate Event Slugs
    *
    * @return void
    */
    public function regenerateEventSlugs()
    {
        $this->info('regenerateEventSlugs');

        $now = Carbon::now();
        $events = Event::all();

        foreach($events as $event) {
            $event->updated_at = $now;

            $event->save();

            $this->info($event->id . ' :: ' . $event->name);
        }
    }

    /**
    * Events Without Photo
    *
    * @return void
    */
    public function eventsWithoutPhoto()
    {
        $this->info('eventsWithoutPhoto');

        $events = Event::isActive()->get();

        foreach($events as $event) {
            if (empty($event->photo_url)) {
                $this->info($event->id . ' :: ' . $event->name);
            }
        }
    }

    /**
    * Locations Without Photo
    *
    * @return void
    */
    public function locationsWithoutPhoto()
    {
        $this->info('locationsWithoutPhoto');

        $locations = Location::isActive()->get();

        foreach($locations as $location) {
            if (empty($location->photo_url)) {
                $this->info($location->id . ' :: ' . $location->name);
            }
        }
    }

    /**
    * Sync Events To Mongo
    *
    * @return void
    */
    private function syncEventsToMongo()
    {
        $mysqlEvents = Event::shouldShow()->get();

        foreach($mysqlEvents as $event) {
            $find = DB::connection('mongodb')
                ->collection('events')
                ->where('id', $event->id)
                ->first();

            if (empty($find)) {
                $value = $event->getMongoArray();

                DB::connection('mongodb')
                    ->collection('events')
                    ->insert($value);

                $this->info('Inserted event #' . $event->id . ' into MongoDB.');
            } else {
                $value = $event->getMongoArray();

                DB::connection('mongodb')
                    ->collection('events')
                    ->where('id', $event->id)
                    ->update($value);

                $this->info('Updated event #' . $event->id . ' with MongoDB.');
            }
        }

        // sync to search
        if ($this->enableSyncToSearch) {
            Event::isActive()->get()->searchable();

            $this->info('Events synced to Mongo and Scout.');
        } else {
            $this->info('Events synced to Mongo');
        }
    }

    /**
    * Sync Locations To Mongo
    *
    * @return void
    */
    private function syncLocationsToMongo()
    {
        $locations = Location::isActive()->get();

        foreach($locations as $location) {
            $find = DB::connection('mongodb')
                ->collection('locations')
                ->where('id', $location->id)
                ->first();

            if (empty($find)) {
                $payload = $location->getMongoArray();

                DB::connection('mongodb')
                    ->collection('locations')
                    ->insert($payload);

                $this->info('Inserted location #' . $location->id . ' into MongoDB.');
            } else {
                $payload = $location->getMongoArray();

                DB::connection('mongodb')
                    ->collection('locations')
                    ->where('id', $location->id)
                    ->update($payload);

                $this->info('Updated location #' . $location->id . ' with MongoDB.');
            }
        }

        // sync to search
        if ($this->enableSyncToSearch) {
            Location::isActive()->get()->searchable();

            $this->info('Locations synced to Mongo and Scout.');
        } else {
            $this->info('Locations synced to Mongo.');
        }
    }

    /**
    * Sync Categories To Mongo
    *
    * @return void
    */
    private function syncCategoriesToMongo()
    {
        $items = Category::isActive()->get();

        foreach($items as $item) {
            $find = DB::connection('mongodb')
                ->collection('categories')
                ->where('id', $item->id)
                ->first();

            if (empty($find)) {
                $value = $item->getMongoArray();

                DB::connection('mongodb')
                    ->collection('categories')
                    ->insert($value);

                $this->info('Inserted category #' . $item->id . ' into MongoDB.');
            } else {
                $value = $item->getMongoArray();

                DB::connection('mongodb')
                    ->collection('categories')
                    ->where('id', $item->id)
                    ->update($value);

                $this->info('Updated category #' . $item->id . ' with MongoDB.');
            }
        }

        // sync to search
        if ($this->enableSyncToSearch) {
            Category::isActive()->get()->searchable();

            $this->info('Categories synced to Mongo and Scout.');
        } else {
            $this->info('Categories synced to Mongo.');
        }
    }

    /**
    * Sync Music Bands To Mongo
    *
    * @return void
    */
    private function syncMusicBandsToMongo()
    {
        $items = MusicBand::all();

        foreach($items as $item) {
            $find = DB::connection('mongodb')
                ->collection('music_bands')
                ->where('id', $item->id)
                ->first();

            if (empty($find)) {
                $value = $item->getMongoArray();

                DB::connection('mongodb')
                    ->collection('music_bands')
                    ->insert($value);

                $this->info('Inserted music band #' . $item->id . ' into MongoDB.');
            } else {
                $value = $item->getMongoArray();

                DB::connection('mongodb')
                    ->collection('music_bands')
                    ->where('id', $item->id)
                    ->update($value);

                $this->info('Updated music band #' . $item->id . ' with MongoDB.');
            }
        }

        // sync to search
        if ($this->enableSyncToSearch) {
            MusicBand::query()->get()->searchable();

            $this->info('Music bands synced to Mongo and Scout.');
        } else {
            $this->info('Music bands synced to Mongo.');
        }
    }

    /**
    * Sync Tags To Mongo
    *
    * @return void
    */
    private function syncTagsToMongo()
    {
        $items = Tag::all();

        foreach($items as $item) {
            $find = DB::connection('mongodb')
                ->collection('tags')
                ->where('id', $item->id)
                ->first();

            if (empty($find)) {
                $value = $item->getMongoArray();

                DB::connection('mongodb')
                    ->collection('tags')
                    ->insert($value);

                $this->info('Inserted tag #' . $item->id . ' into MongoDB.');
            } else {
                $value = $item->getMongoArray();

                DB::connection('mongodb')
                    ->collection('tags')
                    ->where('id', $item->id)
                    ->update($value);

                $this->info('Updated tag #' . $item->id . ' with MongoDB.');
            }
        }

        $this->info('Tags synced to Mongo.');
    }

    /**
    * Fix Media Collections
    *
    * @return void
    */
    public function fixMediaCollections()
    {
        DB::table('media')
            ->where('collection_name', '=', 'images')
            ->where('model_type', '=', 'App\Event')
            ->update([ 'collection_name' => 'events' ]);
    }

    /**
    * Sync Music Bands
    *
    * @return void
    */
    public function syncMusicBands()
    {
        $this->info('syncMusicBands');

        $events = Event::isActive()->get();

        foreach($events as $event) {
            if (!$event->bands()->count() && $event->category_id === 1) {
                $this->info($event->name);
            }
        }
    }

    /**
    * Sync Data To S3
    *
    * @return void
    */
    public function syncDataToS3()
    {
        $this->info('syncDataToS3');

        $collections = [
            'events' => [
                'index',
                'bySlug',
                'byCategory',
                'byLocation',
                'byTag'
            ],
            'locations' => [
                'index',
                'bySlug'
            ],
            'categories' => [
                'index',
                'bySlug'
            ],
            'tags' => [
                'bySlug'
            ],
            'music_bands' => [
                'index'
            ]
        ];

        $data = [
            'events'      => DB::connection('mongodb')->collection('events')->get(),
            'locations'   => DB::connection('mongodb')->collection('locations')->get(),
            'categories'  => DB::connection('mongodb')->collection('categories')->get(),
            'music_bands' => DB::connection('mongodb')->collection('music_bands')->get(),
            'tags'        => DB::connection('mongodb')->collection('tags')->get()
        ];

        foreach($collections as $collection => $methods) {
            $this->info('Generating JSON for collection `' . $collection . '`');

            foreach($methods as $method) {
                $this->info('Starting for method `' . $method . '`...');

                $items = $data[$collection];

                switch ($method) {
                    case 'index':
                        $json = json_encode($items->toArray());

                        Storage::disk('s3')->put('json/' . $collection . '/index.json', $json);
                    break;

                    case 'bySlug':
                        $grouped = $items->groupBy('slug');

                        foreach($grouped as $slug => $rows) {
                            $json = json_encode($rows[0]);

                            Storage::disk('s3')->put('json/' . $collection . '/bySlug/' . $slug . '.json', $json);
                        }
                    break;

                    case 'byCategory':
                        $categories = $data['categories'];

                        foreach($categories as $category) {
                            $slug = $category['slug'];

                            $events = DB::connection('mongodb')
                                ->collection('events')
                                ->where('category_slug', $slug)
                                ->get();

                            $json = json_encode($events->toArray());

                            Storage::disk('s3')->put('json/' . $collection . '/byCategory/' . $slug . '.json', $json);
                        }
                    break;

                    case 'byLocation':
                        $locations = $data['locations'];

                        foreach($locations as $location) {
                            $slug = $location['slug'];

                            $events = DB::connection('mongodb')
                                ->collection('events')
                                ->where('location_slug', $slug)
                                ->get();

                            $json = json_encode($events->toArray());

                            Storage::disk('s3')->put('json/' . $collection . '/byLocation/' . $slug . '.json', $json);
                        }
                    break;

                    case 'byTag':
                        $tags = Tag::all();

                        foreach($tags as $tag) {
                            $slug = $tag->slug;

                            $eventIds = $tag->findIdsByModelId(new Event);

                            $events = DB::connection('mongodb')
                                ->collection('events')
                                ->whereIn('id', $eventIds)
                                ->get();

                            $json = json_encode($events->toArray());

                            Storage::disk('s3')->put('json/' . $collection . '/byTag/' . $slug . '.json', $json);
                        }
                    break;
                }

                $this->info('Finished for method `' . $method . '`!');
            }

            $this->info('Finished collection `' . $collection . '`');
        }
    }

    /**
     * Flush Json Files
     *
     * @return void
     */
    public function flushJsonFiles()
    {
        $folders = Storage::disk('s3')->directories('json');

        foreach($folders as $folder) {
            Storage::disk('s3')->deleteDirectory($folder);
        }
    }

    /**
    * Sync Spotify Music Bands
    *
    * @return void
    */
    public function syncSpotifyMusicBands()
    {
        $spotify = $this->initSpotify();

        $bands = MusicBand::whereNotNull('spotify_artist_id')
            ->whereNull('spotify_json')
            ->get();

        foreach($bands as $key => $band) {
            $info = $spotify->getArtist($band->spotify_artist_id);

            if (!empty($info) && !empty($info->id)) {
                $band->spotify_json = (array) $info;

                $band->save();

                $this->info('Band info saved for `' . $band->name . '`');
            } else {
                $this->error($info);
            }

            if ($key > 0 && ($key % 3 === 0)) {
                $this->info('Sleeping for 2 seconds...');

                sleep(2);
            }
        }
    }

    /**
    * Init Spotify
    *
    * @return object
    */
    public function initSpotify()
    {
        // get new access token
        if (!Cache::has('spotify_access_token')) {
            $this->info('getting new access token for Spotify');

            $session = new \SpotifyWebAPI\Session(
                env('SPOTIFY_CLIENT_ID'),
                env('SPOTIFY_CLIENT_SECRET')
            );

            $session->requestCredentialsToken();
            $accessToken = $session->getAccessToken();

            if (!empty($accessToken)) {
                Cache::put('spotify_access_token', $accessToken, 60);
            } else {
                throw new \Exception('Cannot get access token from Spotify');
            }
        } else {
            $accessToken = Cache::get('spotify_access_token');
        }

        // init spotify instance
        $spotify = new SpotifyWebAPI;

        $spotify->setAccessToken($accessToken);

        return $spotify;
    }
}
