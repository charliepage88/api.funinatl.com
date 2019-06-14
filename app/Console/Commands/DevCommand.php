<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Category;
use App\Event;
use App\Location;

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->syncEventsToMongo();
        // $this->syncLocationsToMongo();
        // $this->syncCategoriesToMongo();
    }

    /**
    * Sync Events To Mongo
    *
    * @return void
    */
    private function syncEventsToMongo()
    {
        $mysqlEvents = Event::all();

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
        Event::all()->searchable();

        $this->info('Events synced to Mongo and Scout.');
    }

    /**
    * Sync Locations To Mongo
    *
    * @return void
    */
    private function syncLocationsToMongo()
    {
        $locations = Location::all();

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
        Location::all()->searchable();

        $this->info('Locations synced to Mongo and Scout.');
    }

    /**
    * Sync Categories To Mongo
    *
    * @return void
    */
    private function syncCategoriesToMongo()
    {
        $items = Category::all();

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
        Category::all()->searchable();

        $this->info('Categories synced to Mongo and Scout.');
    }
}
