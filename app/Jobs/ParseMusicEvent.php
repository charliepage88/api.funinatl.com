<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;
use SpotifyWebAPI\SpotifyWebAPI;

use App\Event;

use Cache;
use Storage;

class ParseMusicEvent implements ShouldQueue
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
        \Log::info('ParseMusicEvent -> start -> `' . $this->event['name'] . '`');

        // parse event data
        if (strstr($this->event['price'], ' Suggested ')) {
            $this->event['price'] = str_replace(' Suggested ', '', $this->event['price']);
        }

        // look for existing event with these details
        $find = Event::where('start_date', '=', $this->event['start_date'])
            ->where('location_id', '=', $this->event['location_id'])
            ->where('name', '=', $this->event['name'])
            ->first();

        if (empty($find)) {
            \Log::info('new event');

            // create event
            $event = new Event;

            // setup data
            $data = $this->event;
            $tags = [];
            $bands = [];

            // unset tags for now
            // will insert after
            if (isset($data['tags'])) {
                $tags = $data['tags'];

                unset($data['tags']);
            }

            // unset bands for now
            // will insert after
            if (isset($data['bands']) && count($data['bands'])) {
                $bands = $data['bands'];

                unset($data['bands']);
            }

            $event->fill($data);

            // set filtered values
            $event->name = $event->generateName();
            $event->short_description = $event->generateShortDescription($bands);
            $event->description = $event->generateDescription($bands);

            // if family friendly value not set, use location default
            if (!isset($data['is_family_friendly'])) {
                $event->is_family_friendly = $event->location->is_family_friendly;
            }

            $event->save();

            // sync tags
            if (!empty($tags)) {
                $event->syncTags($tags);
            }

            // sync bands
            if (!empty($bands)) {
                $event->syncBands($bands);
            }

            if (empty($event->id)) {
                \Log::error($this->event);

                throw new \Exception('Could not create event');
            } else {
                \Log::info('Created event #' . $event->id);
            }

            // populate image for event
            $this->findArtistAndPopulateImage($event);
        } else {
            \Log::info('existing event -> `' . $find->id . '`');

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

            // get bands
            if (!empty($this->event['bands'])) {
                $bands = $this->event['bands'];
            } else {
                $bands = [];
            }

            // save event data
            if (!empty($dataToSave)) {
                $find->fill($dataToSave);

                // set filtered values
                $find->name = $find->generateName();
                $find->short_description = $find->generateShortDescription($bands);
                $find->description = $find->generateDescription($bands);

                $find->save();
            }

            // sync tags
            if (!empty($this->event['tags'])) {
                $find->syncTags($this->event['tags']);
            }

            // sync bands
            if (!empty($bands)) {
                $find->syncBands($this->event['bands']);
            }

            // if no photo URL, try and find one
            // populate image for event
            $this->findArtistAndPopulateImage($find);
        }

        \Log::info('ParseMusicEvent -> done -> `' . $this->event['name'] . '`');
    }

    /**
    * Find Artist
    *
    * @param Event $event
    *
    * @return Event
    */
    private function findArtistAndPopulateImage(Event $event)
    {
        // first, let's get all the related bands to the event
        $bands = $event->bands()->get();

        if ($bands->count()) {
            foreach($bands as $band) {
                if (!$band->photo_url) {
                    // attach image to event if empty
                    try {
                        $results = $this->spotify->search($band->name, 'artist');
                    } catch (\Exception $e) {
                        $message = $e->getMessage();

                        if (strstr($message, 'The access token expired')) {
                            $this->reinitSpotify();

                            $results = $this->spotify->search($band->name, 'artist');
                        }
                    }

                    $imageUrl = null;

                    // look for image url and artist ID in results
                    if (!empty($results) && !empty($results->artists)) {
                        // get largest image
                        $artistJson = null;
                        foreach($results->artists->items as $result) {
                            if (!empty($result->images)) {
                              /*
                                $largestImage = 0;
                                foreach($result->images as $image) {
                                    if ($image->width > $image->height) {
                                        $size = $image->width;
                                    } else {
                                        $size = $image->height;
                                    }

                                    if ($size > $largestImage) {
                                        $imageUrl = $image->url;
                                        $artistJson = $result;
                                    }
                                }
                              */
                              $imageUrl = $result->images[0]->url;
                              $artistJson = $result;
                            }
                        }

                        // save spotify artist ID so we don't
                        // have to search next time
                        if (!empty($artistJson)) {
                            $band->spotify_artist_id = $artistJson->id;
                            $band->spotify_json = (array) $artistJson;

                            $band->save();
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

                            $filename = $band->id . '-' . $band->slug;
                            $filename = $filename . $extension;
                            $tmpPath = storage_path('app') . '/' . $filename;

                            // store locally for a moment
                            Storage::disk('local')->put($filename, $contents);

                            // then add the url
                            $band->addMedia($tmpPath)->toMediaCollection('bands');

                            \Log::info('Uploaded image for band `' . $band->name . '`');

                            sleep(2);
                        } catch (\Exception $e) {
                            \Log::error('Could not attach image to band.');
                            \Log::error($e->getMessage());
                        }
                    } else {
                        $this->assignCategoryDefaultImage($event->category->photo_url, $band, 'bands');
                    }
                }
            }
        }

        // if event has bands and no event image
        // get the first band with a photo and copy
        // over to event
        if ($bands->count() && !$event->photo_url) {
            $event->getFirstBandWithImage();
        }

        // if no photo attached to event
        // assign default category image
        if (!$event->photo_url) {
            $event = $this->assignCategoryDefaultImage($event->category->photo_url, $event, 'events');
        }

        return $event;
    }

    /**
    * Assign Category Default Image
    *
    * @param string $categoryPhotoUrl
    * @param object $model
    * @param string $mediaCollection
    *
    * @return object
    */
    private function assignCategoryDefaultImage($categoryPhotoUrl, $model, $mediaCollection)
    {
        // get category image
        $contents = file_get_contents($categoryPhotoUrl);

        // store locally
        $filename = $model->id . '-' . $model->slug . '.jpg';
        $tmpPath = storage_path('app') . '/' . $filename;

        Storage::disk('local')->put($filename, $contents);

        // then attach file
        $model->addMedia($tmpPath)->toMediaCollection($mediaCollection);

        \Log::info('Uploaded image for #' . $model->id);

        sleep(2);

        return $model;
    }

    /**
    * Reinit Spotify
    *
    * @return SpotifyWebAPI
    */
    private function reinitSpotify()
    {
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

        // init spotify instance
        $spotify = new SpotifyWebAPI;

        $spotify->setAccessToken($accessToken);

        $this->spotify = $spotify;

        return $this->spotify;
    }
}
