<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use SpotifyWebAPI\SpotifyWebAPI;

use App\Category;
use App\Event;
use App\Location;
use App\MusicBand;
use App\Tag;
use App\Jobs\Events\AssignCategoryPhoto;

use Cache;
use DB;
use SiteHelper;
use Storage;

class DevCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev {action?} {params?}';

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
        // can call methods directly
        $action = $this->argument('action');
        if (!empty($action)) {
            $methodName = Str::camel($action);

            if (method_exists($this, $methodName)) {
                $params = $this->argument('params');

                if (!empty($params)) {
                    $ex = explode(',', $params);

                    $this->$methodName(...$ex);
                } else {
                    $this->$methodName();
                }

                return true;
            } else {
                $this->error('Cannot find method name `' . $methodName . '`');

                return false;
            }
        }

        // if no method called directly
        // let's sync
        $this->sync();
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
        $this->info('syncMusicBands -> start');

        $events = Event::isActive()->get();
        $categories = Category::isActive()->get()->getList();

        foreach($events as $event) {
            if (!$event->bands()->count() && $event->category_id === 1) {
                $message = $event->name . ' @ ';
                $message .= $event->location->name . ' :: ' . $event->start_date->format('Y-m-d');

                $this->info($message);

                $bands = $this->ask('What are the band(s) for this event?');

                switch ($bands) {
                    case null:
                        $this->info('Skipping event...');
                    break;

                    case 'category-other':
                        $category = $categories['other'];

                        $event->category_id = $category->id;

                        $event->save();

                        $this->info('Category saved to `' . $category->name . '`');
                    break;

                    case 'category-food-drinks':
                        $category = $categories['food-drinks'];

                        $event->category_id = $category->id;

                        $event->save();

                        $this->info('Category saved to `' . $category->name . '`');
                    break;

                    case 'category-comedy':
                        $category = $categories['comedy'];

                        $event->category_id = $category->id;

                        $event->save();

                        $this->info('Category saved to `' . $category->name . '`');
                    break;

                    case 'category-arts-theatre':
                        $category = $categories['arts-theatre'];

                        $event->category_id = $category->id;

                        $event->save();

                        $this->info('Category saved to `' . $category->name . '`');
                    break;

                    default:
                        $ex = explode(',', $bands);

                        $event->syncBands($ex);

                        $this->info('Bands have been synced to event `' . $event->id . '`');
                    break;
                }
            }
        }

        $this->info('syncMusicBands -> end');
    }

    /**
    * Sync Data To S3
    *
    * @return void
    */
    public function syncDataToS3()
    {
        $this->info('syncDataToS3');

        // flush json files first
        $this->flushJsonFiles();

        $collections = [
            'events' => [
                'bySlug'
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
                }

                $this->info('Finished for method `' . $method . '`!');
            }

            $this->info('Finished collection `' . $collection . '`');
        }

        // flush cache
        $this->flushCache();

        // create cache
        $this->createCache();
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
    * Flush Cache
    *
    * @return void
    */
    public function flushCache()
    {
        Cache::tags('eventsIndexByPeriod')->flush();
        Cache::tags('eventsByPeriodAndCategory')->flush();
        Cache::tags('eventsByPeriodAndLocation')->flush();
        Cache::tags('eventsByPeriodAndTag')->flush();
    }

    /**
    * Create Cache
    *
    * @param string|null $apiUrl
    * @param string|null $start_at_url
    *
    * @return void
    */
    public function createCache($apiUrl = null, $start_at_url = null)
    {
        // get data

        // events
        $events = Event::isActive()
            ->orderBy('start_date', 'asc')
            ->get();

        // categories
        $categories = Category::isActive()->get();

        // location events
        $locations = Location::isActive()->get();

        // tags
        $tags = Tag::all();

        // init vars
        $client = new Guzzle;

        // get first event
        $firstEvent = $events->first();

        $startDate = $firstEvent->start_date;

        // get end date
        $endDate = $startDate->copy()->addWeeks(2);
        $eventsEndDate = $startDate->copy()->addWeeks(4);

        // function to hit URI to generate cache
        $hitUrl = function ($url) use ($client) {
            $response = $client->request('GET', $url);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $this->info('Hit URL: `' . $url . '`');
            } else {
                $this->error('Error Hitting URL: `' . $url . '`. Status Code: ' . $statusCode);
            }
        };

        // collect all possible date values
        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->copy();
        }

        $eventDates = [];
        for ($date = $startDate->copy(); $date->lte($eventsEndDate); $date->addDay()) {
            $eventDates[] = $date->copy();
        }

        if (empty($apiUrl)) {
            $apiUrl = config('app.url');
        }

        $urls = [];

        // category urls
        foreach ($categories as $category) {
            foreach($dates as $date) {
                $firstDate = $date->copy();
                $lastDate = $firstDate->copy()->addWeeks(2)->format('Y-m-d');

                $url = $apiUrl . '/api/events/category/' . $category->slug;
                $url .= '/' . $firstDate->format('Y-m-d') . '/' . $lastDate;

                $urls[] = $url;
            }
        }

        // tag urls
        foreach ($tags as $tag) {
            foreach($dates as $date) {
                $firstDate = $date->copy();
                $lastDate = $firstDate->copy()->addWeeks(2)->format('Y-m-d');

                $url = $apiUrl . '/api/events/tag/' . $tag->slug;
                $url .= '/' . $firstDate->format('Y-m-d') . '/' . $lastDate;

                $urls[] = $url;
            }
        }

        // location urls
        foreach ($locations as $location) {
            foreach($dates as $date) {
                $firstDate = $date->copy();
                $lastDate = $firstDate->copy()->addWeeks(2)->format('Y-m-d');

                $url = $apiUrl . '/api/events/location/' . $location->slug;
                $url .= '/' . $firstDate->format('Y-m-d') . '/' . $lastDate;

                $urls[] = $url;
            }
        }

        // event urls
        foreach($eventDates as $date) {
            $firstDate = $date->copy();
            $lastDate = $firstDate->copy()->addWeeks(4)->format('Y-m-d');

            $url = $apiUrl . '/api/events/index';
            $url .= '/' . $firstDate->format('Y-m-d') . '/' . $lastDate;

            $urls[] = $url;
        }

        // if error occurred, let's
        // not redo ALL of the url's
        if (!empty($start_at_url)) {
            $find = array_search($start_at_url, $urls);

            if ($find === false) {
                $this->error('Cannot find url `' . $start_at_url . '` to start caching at.');

                return false;
            } else {
                $newUrls = [];
                foreach($urls as $key => $url) {
                    if ($key >= $find) {
                        $newUrls[] = $url;
                    }
                }

                $urls = $newUrls;
            }
        }

        // hit URLs to generate cache
        foreach($urls as $key => $url) {
            $this->info('Start: ' . $url);

            $hitUrl($url);

            $this->info('Done: ' . $url);

            if ($key > 0 && ($key % 60 === 0)) {
                $this->info('Sleeping for 60 seconds...');

                sleep(60);
            }
        }

        $this->info('Done Creating Cache for: ' . $startDate->format('Y-m-d') . '-' . $endDate->format('Y-m-d'));
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

    /**
    * Sync To Mongo
    *
    * @return void
    */
    private function syncToMongo()
    {
        $models = [
            [
                'items' => Tag::all(),
                'collection' => 'tags',
                'name' => 'tag',
                'search' => false
            ],

            [
                'items' => MusicBand::all(),
                'collection' => 'music_bands',
                'name' => 'music band'
            ],

            [
                'items' => Category::isActive()->get(),
                'collection' => 'categories',
                'name' => 'category'
            ],

            [
                'items' => Location::isActive()->get(),
                'collection' => 'locations',
                'name' => 'location'
            ],

            [
                'items' => Event::isActive()->get(),
                'collection' => 'events',
                'name' => 'event'
            ]
        ];

        foreach($models as $model) {
            // init vars
            $items = $model['items'];
            $collection = $model['collection'];
            $name = $model['name'];
            $fullName = Str::title(Str::plural($name));

            if (isset($model['search']) && $model['search'] === false) {
                $search = false;
            } else {
                $search = true;
            }

            // loop through model items and create/update
            $changesCount = 0;
            foreach($items as $item) {
                $find = DB::connection('mongodb')
                    ->collection($collection)
                    ->where('id', $item->id)
                    ->first();

                if (empty($find)) {
                    $changesCount++;

                    $value = $item->getMongoArray();

                    DB::connection('mongodb')
                        ->collection($collection)
                        ->insert($value);

                    $this->info('Inserted ' . $name . ' #' . $item->id . ' into MongoDB.');
                } else {
                    $shouldUpdate = false;

                    // get value from local DB
                    $value = $item->getMongoArray(false);

                    // get mongo array to compare
                    $mongoValue = (array) $find;

                    unset($mongoValue['_id']);

                    // compare keys to see if unset is needed
                    $valueKeys = array_keys($value);
                    $mongoKeys = array_keys($mongoValue);

                    $keysDiff = array_diff($mongoKeys, $valueKeys);

                    if (!empty($keysDiff)) {
                        foreach(array_values($keysDiff) as $key) {
                            DB::connection('mongodb')
                                ->collection($collection)
                                ->where('id', $item->id)
                                ->unset($key);

                            $this->info('Unset field `' . $key . '` for collection ' . $collection);
                        }   
                    }

                    // compare keys to see if model needs
                    // to be updated
                    $isMongoMissingKeys = array_diff($valueKeys, $mongoKeys);

                    if (!empty($isMongoMissingKeys)) {
                        $shouldUpdate = true;
                    }

                    if (!$shouldUpdate) {
                        // compare values via json_encode
                        $diff = array_diff(
                            array_map('json_encode', $mongoValue),
                            array_map('json_encode', $value)
                        );

                        // json decode diff result
                        $shouldUpdate = array_map('json_decode', $diff);
                    }

                    if ($shouldUpdate) {
                        $changesCount++;

                        DB::connection('mongodb')
                            ->collection($collection)
                            ->where('id', $item->id)
                            ->update($value);

                        $this->info('Updated ' . $name . ' #' . $item->id . ' with MongoDB.');
                    } else {
                        $this->info('Skipping update for ' . $name . ' #' . $item->id);
                    }
                }
            }

            // sync to search
            if ($search && $changesCount && $this->enableSyncToSearch) {
                $items->searchable();

                $this->info($fullName . ' synced to Mongo and Scout. ' . $changesCount . ' total changes.');
            } else {
                $this->info($fullName . ' synced to Mongo. ' . $changesCount . ' total changes.');
            }
        }
    }

    /**
    * Sync
    *
    * @return void
    */
    public function sync()
    {
        $this->info('sync');

        if ($this->enableSync) {
            $this->syncToMongo();
            $this->syncDataToS3();
        }
    }

    /**
    * List
    *
    * @return void
    */
    public function list()
    {
        $this->info('list');

        $findMethods = get_class_methods($this);
        $methods = [];

        foreach($findMethods as $methodName) {
            if ($methodName === '__construct') {
                break;
            } else {
                if ($methodName !== 'handle') {
                    $methods[] = $methodName;
                }
            }
        }

        $headers = [
            'Method'
        ];
        $body = [];
        foreach($methods as $methodName) {
            $row = [
                $methodName
            ];

            $reflection = new \ReflectionMethod($this, $methodName);
            $params = $reflection->getParameters();
            $paramsList = [];
            foreach ($params as $param) {
                $paramsList[] = true;

                $row[] = $param->getName();
                $row[] = !$param->isOptional() ? 'Yes' : 'No';
            }

            if (!empty($paramsList)) {
                foreach($paramsList as $key => $value) {
                    $i = ($key + 1);

                    $headers[] = 'Param Name (' . $i . ')';
                    $headers[] = 'Param Required (' . $i . ')';
                }
            }

            $body[] = $row;
        }

        $this->table($headers, $body);
    }

    /**
    * Fix Event Info
    *
    * @return void
    */
    public function fixEventInfo()
    {
        $this->info('fixEventInfo -> start');

        // get events
        $events = Event::with([ 'bands', 'category' ])
            ->shouldShow()
            ->get();

        foreach($events as $key => $event) {
            // init debug info/vars
            $this->info('Processing event #' . $event->id);

            $photoUrl = $event->photo_url;
            $bands = $event->bands()->pluck('name')->toArray();

            $this->info($photoUrl);

            // fix images that are too small
            // try to fit instead of crop
            try {
                list($width, $height) = getimagesize($photoUrl);

                $this->info($width . ' x ' . $height);

                if ($width < 250 || $height < 150) {
                    $this->info('Replace photo with category default....');

                    AssignCategoryPhoto::dispatch($event);

                    $this->info('DONE Replacing photo with category default....');
                } else {
                    if ($width < 726 || $height < 250) {
                        $this->info('Should regenerate url `' . $photoUrl . '` and conversions.');
                    } else {
                        $this->info('Skipping....' . $width . ' x ' . $height);
                    }
                }
            } catch(\Exception $e) {
                $this->error($e->getMessage());
            }

            // fix event descriptions so they are
            // mostly consistent
            $shouldSave = false;

            // regenerate name & slug
            // if the generated value is different
            $newName = $event->generateName();

            if ($newName !== $event->name) {
                $this->info('New Name & Slug...');
                $this->info($event->name);
                $this->info($event->slug);

                $event->name = $newName;
                $event->generateSlug();

                $this->info($event->name);
                $this->info($event->slug);
                $this->info('---');

                $shouldSave = true;
            }

            // now figure out the short description
            $newShortDescription = $event->generateShortDescription($bands);

            if ($newShortDescription !== $event->short_description) {
                $this->info('New Short Description...');
                $this->info($event->short_description ?? 'empty...');

                $event->short_description = $newShortDescription;

                $this->info($event->short_description);
                $this->info('---');

                $shouldSave = true;
            }

            // and figure out the long description
            $newDescription = $event->generateDescription($bands);

            if ($newDescription !== $event->description) {
                $this->info('New Description...');
                $this->info($event->description ?? 'empty...');

                $event->description = $newDescription;

                $this->info($event->description);
                $this->info('---');

                $shouldSave = true;
            }

            // check if end time is set or not
            // only set for music events
            if (empty($event->end_time)) {
                $startDate = $event->start_date->format('Y-m-d');
                $time = Carbon::parse($startDate . ' ' . $event->start_time);

                if ($event->category->slug === 'music') {
                    $event->end_time = $time->copy()->addHours(3)->format('g:i A');
                }
            }

            // if event start date = end date
            // unset end date
            if ($event->start_date === $event->end_date) {
                $event->end_date = null;
            }

            // save event if data has changed
            if ($shouldSave) {
                $event->save();

                $this->info('Saved event `' . $event->id . '`');
            }

            // sleep
            if ($key > 0 && ($key % 15 === 0)) {
                $this->info('Sleeping for 5 seconds...');

                sleep(5);
            } else {
                $this->info('Sleeping for 2 seconds...');

                sleep(2);
            }
        }

        $this->info('fixEventInfo -> end');
    }
}
