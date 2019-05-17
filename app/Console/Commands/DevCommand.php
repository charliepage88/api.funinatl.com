<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Event;

use DB;

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
    }
}
