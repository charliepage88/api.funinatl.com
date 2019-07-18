<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use App\Category;
use App\Event;
use App\Location;
use App\MusicBand;
use App\Tag;

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
        // $this->truncateMongo();
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
        }
    }

    /**
     * Truncate Mongo
     *
     * @return void
     */
    public function truncateMongo()
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
}
