<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Event;

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
            ->count();

        if (!$find) {
            // create event
            $event = new Event;

            $event->fill($this->event);

            $event->save();

            if (empty($event->id)) {
                \Log::error($this->event);

                throw new \Exception('Could not create event');
            }

            // attach image to event
            $results = $this->spotify->search($event->name, 'artist');

            if ($results->artists->total) {
                $event = $this->populateImage($event, $results);
            } else {
                \Log::error('Could not find any spotify results for `' . $event->name . '`');
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
        // get largest image
        $imageUrl = null;
        $largestImage = 0;
        foreach($results->artists->items as $result) {
            if (!empty($result->images)) {
                foreach($result->images as $image) {
                    $size = ($image->width + $image->height);

                    if ($size > $largestImage) {
                        $imageUrl = $image->url;
                    }
                }
            }
        }

        if (!empty($imageUrl)) {
            $event->addMedia($imageUrl)->toMediaCollection('images', 'spaces');
        }

        return $event;
    }
}
