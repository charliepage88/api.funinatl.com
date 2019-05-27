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
        /*
        // Sync all events to MongoDB
        $mysqlEvents = Event::all();

        foreach($mysqlEvents as $event) {
            $find = DB::connection('mongodb')->collection('events')->where('id', $event->id)->first();

            if (empty($find)) {
                $value = $event->getMongoArray();

                DB::connection('mongodb')->collection('events')->insert($value);

                $this->info('Inserted event #' . $event->id . ' into MongoDB.');
            }
        }

        $mongoEvents = DB::connection('mongodb')->collection('events')->get();

        $this->info('MySQL: ' . $mysqlEvents->count() . ' :: MongoDB: ' . $mongoEvents->count());
        */

        /*
        // Add tags to events & sync to mongo
        $mysqlEvents = Event::where('location_id', '=', 2)->get();

        foreach($mysqlEvents as $event) {
            // Add `blues` tag to mysql events
            $event->syncTags([ 'blues music' ]);

            $find = DB::connection('mongodb')->collection('events')->where('id', $event->id)->first();

            if (empty($find)) {
                $value = $event->getMongoArray();

                DB::connection('mongodb')->collection('events')->insert($value);

                $this->info('Inserted event #' . $event->id . ' into MongoDB.');
            } else {
                $value = $event->getMongoArray();

                DB::connection('mongodb')->collection('events')->where('id', $event->id)
                    ->update($value);

                $this->info('Updated event #' . $event->id . ' with MongoDB.');
            }
        }

        $mongoEvents = DB::connection('mongodb')->collection('events')->get();

        $this->info('Done!');
        */

        // Update all events data
        /*
        $mysqlEvents = Event::all();

        foreach($mysqlEvents as $event) {
            $find = DB::connection('mongodb')->collection('events')->where('id', $event->id)->first();

            if (empty($find)) {
                $value = $event->getMongoArray();

                DB::connection('mongodb')->collection('events')->insert($value);

                $this->info('Inserted event #' . $event->id . ' into MongoDB.');
            } else {
                $value = $event->getMongoArray();

                DB::connection('mongodb')->collection('events')->where('id', $event->id)
                    ->update($value);

                $this->info('Updated event #' . $event->id . ' with MongoDB.');
            }
        }

        $this->info('Done!');
        */

        // Update Locations in MongoDB
        /*
        $items = Location::all();

        foreach($items as $item) {
            $find = DB::connection('mongodb')->collection('locations')->where('id', $item->id)->first();

            if (empty($find)) {
                $value = $item->getMongoArray();

                DB::connection('mongodb')->collection('locations')->insert($value);

                $this->info('Inserted location #' . $item->id . ' into MongoDB.');
            }
        }

        $locations = DB::connection('mongodb')->collection('locations')->get();

        $this->info('MySQL: ' . $items->count() . ' :: MongoDB: ' . $locations->count());
        */

        // Script to ensure all events have photo
        /*
        $events = Event::all();

        foreach($events as $event) {
            if (empty($event->photo_url)) {
                // copy over default for this category
                $filename = $event->id . '-' . $event->slug . '.jpg';

                Storage::disk('public')->copy('category-music.jpg', $filename);

                $path = storage_path('app/public') . '/' . $filename;

                // then add the url
                $event->addMedia($path)->toMediaCollection('images', 'spaces');

                $this->info('Uploaded image for event #' . $event->id);
            }
        }
        */

        // Update Locations in MongoDB
        /*
        $items = Category::all();

        foreach($items as $item) {
            $find = DB::connection('mongodb')->collection('categories')->where('id', $item->id)->first();

            if (empty($find)) {
                $value = $item->getMongoArray();

                DB::connection('mongodb')->collection('categories')->insert($value);

                $this->info('Inserted location #' . $item->id . ' into MongoDB.');
            }
        }

        $categories = DB::connection('mongodb')->collection('categories')->get();

        $this->info('MySQL: ' . $items->count() . ' :: MongoDB: ' . $categories->count());
        */
    }
}
