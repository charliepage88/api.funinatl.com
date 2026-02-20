<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use SpotifyWebAPI\SpotifyWebAPI;

use App\Category;
use App\Event;
use App\Location;
use App\MusicBand;
use App\Report;
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
  * Sync
  *
  * @return void
  */
  public function sync()
  {
    $this->info('sync');

    $this->syncData();
    $this->syncCache();
  }

  /**
  * Sync Cache
  *
  * @return void
  */
  public function syncCache()
  {
    $this->info('syncCache');

    // flush cache
    $this->flushCache();

    // generate routes
    $this->generateRoutesList();

    // create cache
    $this->createCache();
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
    Cache::tags('eventsByPeriodAndBand')->flush();
    Cache::tags('eventsBySlug')->flush();
    Cache::tags('categoriesIndex')->flush();
    Cache::tags('locationsIndex')->flush();
    Cache::tags('routesList')->flush();
    Cache::tags('routesListWeb')->flush();
    Cache::tags('dbcache')->flush();
    Cache::tags('eventsCache')->flush();
  }

  /**
  * Create Cache
  *
  * Warms all persistent (rememberForever) caches directly via Report methods,
  * bypassing HTTP entirely — no rate limiting, no network overhead.
  *
  * @return void
  */
  public function createCache()
  {
    $started = microtime(true);

    // Prime base data caches (all use rememberForever in Redis)
    $this->info('Warming base data caches...');

    $categories = Report::getCachedCategories();
    $locations  = Report::getCachedLocations();
    $tags       = Report::getCachedTags();
    $bands      = Report::getCachedBands();
    $events     = Report::getCachedEvents();

    $this->info(sprintf(
      'Loaded: %d events · %d categories · %d locations · %d tags · %d bands',
      $events->count(),
      $categories->count(),
      $locations->count(),
      $tags->count(),
      $bands->count()
    ));

    // Warm the routes list — covers all category/location/tag/band
    // combinations for this year (rememberForever cache)
    $this->info('Warming routes list...');
    $routes = Report::getRoutesList();
    $this->info('Routes list: ' . count($routes) . ' routes warmed.');

    $elapsed = round(microtime(true) - $started, 2);
    $this->info('Done in ' . $elapsed . 's.');
  }

  /**
  * Sync Data
  *
  * @return void
  */
  private function syncData()
  {
    $models = [
      [
        'items'      => Tag::orderBy('name', 'asc')->get(),
        'collection' => 'tags',
        'name'       => 'tag',
        'search'     => false
      ],
      [
        'items'      => MusicBand::orderBy('name', 'asc')->get(),
        'collection' => 'music_bands',
        'name'       => 'music band'
      ],
      [
        'items'      => Category::isActive()->orderBy('name', 'asc')->get(),
        'collection' => 'categories',
        'name'       => 'category'
      ],
      [
        'items'      => Location::isActive()->orderBy('name', 'asc')->get(),
        'collection' => 'locations',
        'name'       => 'location'
      ],
      [
        'items'      => Event::isActive()->orderBy('start_date', 'asc')->get(),
        'collection' => 'events',
        'name'       => 'event'
      ]
    ];

    foreach ($models as $model) {
      $items      = $model['items'];
      $collection = $model['collection'];
      $name       = $model['name'];
      $fullName   = Str::title(Str::plural($name));
      $search     = $model['search'] ?? true;

      if ($items->isEmpty()) {
        $this->info($fullName . ': no items found.');
        continue;
      }

      // batch fetch all Redis keys at once
      $keys        = $items->map(fn($item) => $collection . '.' . $item->id)->all();
      $redisValues = Redis::mget($keys);

      $changesCount   = 0;
      $collectionData = [];

      // pipeline all writes
      $pipeline = Redis::pipeline();

      foreach ($items as $index => $item) {
        $value    = $item->getFormattedArray();
        $existing = $redisValues[$index];

        if (empty($existing)) {
          $changesCount++;
          $pipeline->set($collection . '.' . $item->id, json_encode($value));
          $this->info('Inserted ' . $name . ' #' . $item->id . '.');
        } else {
          $existingDecoded = json_decode($existing, true);
          $existingKeys    = array_keys($existingDecoded);
          $valueKeys       = array_keys($value);

          $shouldUpdate = !empty(array_diff($valueKeys, $existingKeys));

          if (!$shouldUpdate) {
            $diff         = array_diff(
              array_map('json_encode', $existingDecoded),
              array_map('json_encode', $value)
            );
            $shouldUpdate = !empty(array_map('json_decode', $diff));
          }

          if ($shouldUpdate) {
            $changesCount++;
            $pipeline->set($collection . '.' . $item->id, json_encode($value));
            $this->info('Updated ' . $name . ' #' . $item->id . '.');
          }
        }

        $collectionData[] = $value;
      }

      $pipeline->set($collection, json_encode($collectionData));
      $pipeline->execute();

      // sync to search
      if ($search && $changesCount) {
        foreach ($items as $item) {
          $item->searchable();
        }
        $this->info($fullName . ' synced data and Scout. ' . $changesCount . ' changes.');
      } else {
        $this->info($fullName . ' synced data. ' . $changesCount . ' changes.');
      }
    }
  }

  /**
  * Regenerate Event Slugs
  *
  * @return void
  */
  public function regenerateEventSlugs()
  {
    $this->info('regenerateEventSlugs');

    $now    = Carbon::now();
    $events = Event::all();

    foreach ($events as $event) {
      $event->updated_at = $now;
      $event->save();

      $this->info($event->id . ' :: ' . $event->name);
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

    foreach ($locations as $location) {
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
      ->update(['collection_name' => 'events']);
  }

  /**
  * Sync Music Bands
  *
  * @return void
  */
  public function syncMusicBands()
  {
    $this->info('syncMusicBands -> start');

    $events     = Event::isActive()->get();
    $categories = Category::isActive()->get()->getList();

    foreach ($events as $event) {
      if (!$event->bands()->count() && $event->category_id === 1) {
        $message  = $event->name . ' @ ';
        $message .= $event->location->name . ' :: ' . $event->start_date->format('Y-m-d');

        $this->info($message);

        $bands = $this->ask('What are the band(s) for this event?');

        switch ($bands) {
          case null:
            $this->info('Skipping event...');
            break;

          case 'category-other':
            $category           = $categories['other'];
            $event->category_id = $category->id;
            $event->save();
            $this->info('Category saved to `' . $category->name . '`');
            break;

          case 'category-food-drinks':
            $category           = $categories['food-drinks'];
            $event->category_id = $category->id;
            $event->save();
            $this->info('Category saved to `' . $category->name . '`');
            break;

          case 'category-comedy':
            $category           = $categories['comedy'];
            $event->category_id = $category->id;
            $event->save();
            $this->info('Category saved to `' . $category->name . '`');
            break;

          case 'category-arts-theatre':
            $category           = $categories['arts-theatre'];
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

    foreach ($bands as $key => $band) {
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
    if (!Cache::has('spotify_access_token')) {
      $this->info('getting new access token for Spotify');

      $session = new \SpotifyWebAPI\Session(
        config('services.spotify.client_id'),
        config('services.spotify.secret')
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

    $spotify = new SpotifyWebAPI;
    $spotify->setAccessToken($accessToken);

    return $spotify;
  }

  /**
  * List available methods
  *
  * @return void
  */
  public function list()
  {
    $this->info('list');

    $findMethods = get_class_methods($this);
    $methods     = [];

    foreach ($findMethods as $methodName) {
      if ($methodName === '__construct') {
        break;
      } elseif ($methodName !== 'handle') {
        $methods[] = $methodName;
      }
    }

    // determine max param count across all methods
    $maxParams = 0;
    $methodData = [];

    foreach ($methods as $methodName) {
      $reflection  = new \ReflectionMethod($this, $methodName);
      $params      = $reflection->getParameters();
      $maxParams   = max($maxParams, count($params));
      $methodData[] = ['name' => $methodName, 'params' => $params];
    }

    // build headers once based on max param count
    $headers = ['Method'];
    for ($i = 1; $i <= $maxParams; $i++) {
      $headers[] = 'Param Name (' . $i . ')';
      $headers[] = 'Param Required (' . $i . ')';
    }

    $body = [];
    foreach ($methodData as $data) {
      $row    = [$data['name']];
      foreach ($data['params'] as $param) {
        $row[] = $param->getName();
        $row[] = !$param->isOptional() ? 'Yes' : 'No';
      }
      // pad row to full width
      while (count($row) < count($headers)) {
        $row[] = '';
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

    $events = Event::with(['bands', 'category'])
      ->shouldShow()
      ->get();

    foreach ($events as $key => $event) {
      $this->info('Processing event #' . $event->id);

      $photoUrl = $event->photo_url;
      $bands    = $event->bands()->pluck('name')->toArray();

      $this->info($photoUrl);

      // fix images that are too small
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
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }

      $shouldSave = false;

      // regenerate name & slug
      $newName = $event->generateName();

      if ($newName !== $event->name) {
        $this->info('New Name & Slug...');
        $this->info($event->name . ' -> ' . $newName);

        $event->name = $newName;
        $event->generateSlug();

        $this->info($event->slug);
        $shouldSave = true;
      }

      // short description
      $newShortDescription = $event->generateShortDescription($bands);

      if ($newShortDescription !== $event->short_description) {
        $this->info('New Short Description...');
        $this->info(($event->short_description ?? 'empty...') . ' -> ' . $newShortDescription);

        $event->short_description = $newShortDescription;
        $shouldSave = true;
      }

      // long description
      $newDescription = $event->generateDescription($bands);

      if ($newDescription !== $event->description) {
        $this->info('New Description...');
        $this->info(($event->description ?? 'empty...') . ' -> ' . $newDescription);

        $event->description = $newDescription;
        $shouldSave = true;
      }

      // set end time for music events missing it
      if (empty($event->end_time) && $event->category->slug === 'music') {
        $time            = Carbon::parse($event->start_date->format('Y-m-d') . ' ' . $event->start_time);
        $event->end_time = $time->copy()->addHours(3)->format('g:i A');
        $shouldSave      = true;
      }

      // unset end_date if same as start_date
      if (!empty($event->end_date) && $event->start_date->format('Y-m-d') === $event->end_date->format('Y-m-d')) {
        $event->end_date = null;
        $this->info('Unset end date.');
        $shouldSave = true;
      }

      if ($shouldSave) {
        $event->save();
        $this->info('Saved event `' . $event->id . '`');
      }

      // sleep every 15 events
      if ($key > 0 && ($key % 15 === 0)) {
        sleep(5);
      } else {
        sleep(2);
      }
    }

    $this->info('fixEventInfo -> end');
  }

  /**
  * Fix Event Dates And Times
  *
  * @return void
  */
  public function fixEventDatesAndTimes()
  {
    $this->info('fixEventDatesAndTimes');

    $events = Event::all();
    $count  = 0;

    foreach ($events as $event) {
      $startDate  = $event->start_date->format('Y-m-d');
      $endDate    = $event->end_date ? $event->end_date->format('Y-m-d') : null;
      $hasEndDate = !empty($endDate);

      $unsetEndDate = $hasEndDate && ($startDate === $endDate);
      if ($unsetEndDate) {
        $count++;
        $this->info('Event `' . $event->id . '` has same start & end date - ' . $startDate);
      }

      $startTime  = $event->start_time;
      $endTime    = $event->end_time;

      $unsetEndTime = !empty($endTime) && ($startTime === $endTime) && (!$hasEndDate || $unsetEndDate);
      if ($unsetEndTime) {
        $count++;
        $this->info('Event `' . $event->id . '` has same start & end time - ' . $startTime);
      }

      if ($unsetEndDate) {
        $event->end_date = null;
      }

      if ($unsetEndTime) {
        $event->end_time = null;
      }

      if ($unsetEndDate || $unsetEndTime) {
        $event->save();
      }
    }

    $this->info('Issues fixed: ' . $count);
  }

  /**
  * Flush Old Media
  *
  * @return void
  */
  public function flushOldMedia()
  {
    $folders       = Storage::directories();
    $mediaIds      = DB::table('media')->pluck('id')->toArray();
    $ignoreFolders = ['site'];

    $leftOverFolders = array_filter(
      $folders,
      fn($folder) => !in_array($folder, $mediaIds) && !in_array($folder, $ignoreFolders)
    );

    foreach ($leftOverFolders as $folder) {
      Storage::deleteDirectory($folder);
      $this->info($folder);
    }
  }

  /**
  * Find Recurring Events
  *
  * @return void
  */
  public function findRecurringEvents()
  {
    $checkFields = [
      'category_id',
      'short_description',
      'price',
      'list_tags'
    ];

    $events = Event::with('location')->shouldShow()->get();

    // pre-group events by location+name to avoid N+1 queries
    $grouped = $events->groupBy(fn($e) => $e->location_id . '|' . $e->name);

    foreach ($events as $event) {
      $groupKey = $event->location_id . '|' . $event->name;
      $matches  = $grouped[$groupKey]->where('id', '!=', $event->id);

      if ($matches->isEmpty()) {
        continue;
      }

      $location = $event->location;
      $origDate = $event->start_date->format('l, F jS');
      $event->category_id = (int) $event->category_id;

      $this->info('Found other events with name `' . $event->name . '` for location `' . $location->name . '`');
      $this->info('Original Event: ' . $event->start_date->format('Y-m-d') . ' @ ' . $event->start_time);

      foreach ($matches as $row) {
        $row->category_id = (int) $row->category_id;
        $otherDate        = $row->start_date->format('l, F jS');

        foreach ($checkFields as $field) {
          $eventValue = $field === 'short_description'
            ? str_replace($origDate, $otherDate, $event->$field)
            : $event->$field;

          if ($eventValue !== $row->$field) {
            $this->error('Different info for field `' . $field . '`');
            $this->error('Original: `' . $eventValue . '`');
            $this->error('Other: `' . $row->$field . '`');
          }
        }

        $this->info('Recurring Event: ' . $row->start_date->format('Y-m-d') . ' @ ' . $row->start_time);
      }

      $this->info('---');
    }
  }

  public function scanRedis(string $pattern)
  {
    $this->info('scanRedis -> ' . $pattern);

    $cursor = 0;
    do {
      list($cursor, $keys) = Redis::scan($cursor, 'match', $pattern);

      foreach ($keys as $key) {
        yield $key;
      }
    } while ($cursor);
  }

  public function fixEventTitles()
  {
    $events = Event::all();

    foreach ($events as $event) {
      $oldValue = $event->name;
      $newValue = trim(html_entity_decode($event->name));

      if ($newValue !== $oldValue) {
        $this->info($event->id . ' --- ' . $newValue . ' (' . $oldValue . ')');

        $event->name = $newValue;
        $event->save();
      }
    }
  }

  public function updateMusicBandImages()
  {
    $bands = MusicBand::whereNotNull('spotify_json')->with(['events'])->get();

    foreach ($bands as $band) {
      if (!isset($band->spotify_json['images'][0]['url'])) {
        $this->info('No Spotify image for band `' . $band->name . '`, skipping.');
        continue;
      }

      if ($band->getMedia('bands')->count()) {
        $band->getMedia('bands')->first()->delete();
      }

      $imageUrl = $band->spotify_json['images'][0]['url'];
      $contents = file_get_contents($imageUrl);
      $info     = pathinfo($imageUrl);
      $extension = !empty($info['extension']) ? $info['extension'] : 'jpeg';

      $filename = $band->id . '-' . $band->slug . '.' . $extension;
      $tmpPath  = storage_path('app') . '/' . $filename;

      Storage::disk('local')->put($filename, $contents);

      $band->addMedia($tmpPath)->toMediaCollection('bands');

      $this->info('Image updated for band `' . $band->name . '`');

      if ($band->events->count()) {
        foreach ($band->events as $event) {
          $event->getFirstBandWithImage();
          $event->searchable();

          $this->info('New band image saved to event `' . $event->name . '`');
        }
      }

      sleep(1);
    }
  }

  /**
  * Generate Routes List
  *
  * @return void
  */
  public function generateRoutesList()
  {
    $routes = Report::getRoutesList();

    $this->info('Routes generated for `' . $routes->count() . '` items.');
  }
}
