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
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $event)
    {
        $this->event = $event;
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

            // set filtered values
            $event->name = $event->generateName();
            $event->short_description = $event->generateShortDescription();
            $event->description = $event->generateDescription();

            // if family friendly value not set, use location default
            if (!isset($data['is_family_friendly'])) {
                $event->is_family_friendly = $event->location->is_family_friendly;
            }

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
            $this->populateImage($event);
        } else {
            // if field values have changed
            // trigger update
            $fields = [
                'website',
                'price',
                'is_sold_out',
                'start_time',
                'is_family_friendly'
            ];

            $dataToSave = [];
            foreach($fields as $field) {
                if (isset($this->event[$field]) && ($this->event[$field] !== $find->$field)) {
                    $dataToSave[$field] = $this->event[$field];
                }
            }

            if (!empty($dataToSave)) {
                $find->fill($dataToSave);

                // set filtered values
                $find->name = $find->generateName();
                $find->short_description = $find->generateShortDescription();
                $find->description = $find->generateDescription();

                $find->save();
            }

            // sync tags
            if (!empty($this->event['tags'])) {
                $find->syncTags($this->event['tags']);
            }

            // if no photo URL, try and find one
            if (!$find->photo_url) {
                // attach image to event
                $this->populateImage($find);
            }
        }
    }

    /**
    * Populate Image
    *
    * @param Event  $event
    *
    * @return Event
    */
    private function populateImage(Event $event)
    {
        if (!empty($this->event['image_url'])) {
            try {
                $imageUrl = $this->event['image_url'];

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
                $event->addMedia($tmpPath)->toMediaCollection('events');

                \Log::info('Uploaded image for event #' . $event->id);

                sleep(2);
            } catch (\Exception $e) {
                \Log::error('Could not attach image to event.');
                \Log::error($e->getMessage());
            }
        } elseif (empty($event->photo_url)) {
            // get category image
            $contents = file_get_contents($event->category->photo_url);

            // store locally
            $filename = $event->id . '-' . $event->slug . '.jpg';
            $tmpPath = storage_path('app') . '/' . $filename;

            Storage::disk('local')->put($filename, $contents);

            // then attach file
            $event->addMedia($tmpPath)->toMediaCollection('events');

            \Log::info('Uploaded image for event #' . $event->id);

            sleep(2);
        }

        return $event;
    }
}
