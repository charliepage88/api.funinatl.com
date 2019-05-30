<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;

use App\Event;

use Storage;

class ParseEvent implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
    * @var array
    */
    public $event;

    /**
    * @var SpotifyWebAPI
    */
    public $spotify;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $event, $spotify)
    {
        $this->event = $event;
        $this->spotify = $spotify;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // look for existing event with these details
        $find = Event::where('start_date', '=', $this->event['start_date'])
            ->where('location_id', '=', $this->event['location_id'])
            ->where('name', '=', $this->event['name'])
            ->first();

        if (empty($find)) {
            // create event
            $event = new Event;

            // setup data
            $data = $this->event;
            $tags = [];

            if (isset($data['tags'])) {
                $tags = $data['tags'];

                unset($data['tags']);
            }

            $event->fill($data);

            $event->save();

            if (!empty($tags)) {
                $event->syncTags($tags);
            }

            if (empty($event->id)) {
                \Log::error($this->event);

                throw new \Exception('Could not create event');
            } else {
                \Log::info('Created event #' . $event->id);
            }

            // attach image to event
            $results = $this->findArtist($event);

            if ($results->artists->total) {
                $this->populateImage($event, $results);
            } else {
                $this->populateImage($event, null);

                \Log::info('Could not find any spotify results for `' . $event->name . '`');
            }
        } else {
            // if field values have changed
            // trigger update
            $triggerUpdate = false;
            $fields = [
                'website',
                'price',
                'is_sold_out',
                'start_time'
            ];

            foreach($fields as $field) {
                if ($this->event[$field] !== $find->$field) {
                    $triggerUpdate = true;
                }
            }

            // if no photo URL, try and find one
            if (!$find->photo_url) {
                $results = $this->findArtist($find);

                if ($results->artists->total) {
                    $this->populateImage($find, $results);
                } else {
                    $this->populateImage($find, null);

                    \Log::info('Could not find any spotify results for `' . $find->name . '`');
                }
            }
        }
    }

    /**
    * Populate Image
    *
    * @param Event  $event
    * @param object $results
    *
    * @return Event
    */
    private function populateImage(Event $event, $results)
    {
        $imageUrl = null;
        if (!empty($results) && !empty($results->artists)) {
            // get largest image
            $artistId = null;
            $largestImage = 0;
            foreach($results->artists->items as $result) {
                if (!empty($result->images)) {
                    foreach($result->images as $image) {
                        $size = ($image->width + $image->height);

                        if ($size > $largestImage) {
                            $imageUrl = $image->url;
                            $artistId = $result->id;
                        }
                    }
                }
            }

            // save spotify artist ID so we don't
            // have to search next time
            if (!empty($artistId)) {
                $event->spotify_artist_id = $artistId;

                $event->save();
            }
        }

        if (!empty($imageUrl)) {
            try {
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

                $filename = $event->id . '-' . $event->slug;
                $filename = $filename . $extension;
                $tmpPath = storage_path('app') . '/' . $filename;

                // store locally for a moment
                Storage::disk('local')->put($filename, $contents);

                // then add the url
                $event->addMedia($tmpPath)->toMediaCollection('images', 'spaces');

                \Log::info('Uploaded image for event #' . $event->id);

                sleep(2);
            } catch (\Exception $e) {
                \Log::error('Could not attach image to event.');
                \Log::error($e->getMessage());
            }
        }

        if (empty($event->photo_url)) {
            // copy over default for this category
            $filename = $event->id . '-' . $event->slug . '.jpg';

            Storage::disk('public')->copy('category-music.jpg', $filename);

            $path = storage_path('app/public') . '/' . $filename;

            // then add the url
            $event->addMedia($path)->toMediaCollection('images', 'spaces');

            \Log::info('Uploaded image for event #' . $event->id);

            sleep(2);
        }

        return $event;
    }

    /**
    * Find Artist
    *
    * @param Event $event
    *
    * @return object
    */
    private function findArtist(Event $event)
    {
        $name = $event->name;

        if (strstr($name, ', ')) {
            $ex = explode(', ', $name);

            $name = $ex[0];
        }

        // attach image to event if empty
        $results = $this->spotify->search($name, 'artist');

        if (!empty($ex) && !$results->artists->total) {
            $results = $this->spotify->search($event->name, 'artist');
        }

        \Log::info('Spotify search for `' . $name . '`');
        \Log::info((array) $results->artists->items);

        return $results;
    }
}
