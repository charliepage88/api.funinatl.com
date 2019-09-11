<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Event;

use Twitter;

/*
// post tweet
Twitter::postTweet([
    'status' => 'Laravel is beautiful',
    'format' => 'json'
]);

// fields
// - possibly_sensitive (only if graphic content)
// - lat
// - lng (for location?)

// post tweet with uploaded image
$uploaded_media = Twitter::uploadMedia([ 'media' => Storage::get('abc.jpg') ]);

Twitter::postTweet([
    'status' => 'Laravel is beautiful',
    'media_ids' => $uploaded_media->media_id_string
]);
*/
class TwitterPostCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:post {action?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post events to twitter.';

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
            } else {
                $this->error('Cannot find method name `' . $methodName . '`');
            }
        } else {
            $this->error('No method name supplied. ex: `php artisan twitter:post daily`');
        }
    }

    /**
    * Daily
    *
    * @return void
    */
    public function daily()
    {
        $date = Carbon::now()->format('Y-m-d');

        \Log::info('TwitterPostCommand -> daily (' . $date . ')');

        $events = Event::shouldShow()->byDate($date);

        $tweetContent = null;
        $photoUrl = null;
        $location = null;
        foreach($events as $event) {
            if (empty($tweetContent) && $event->hasMeta('tweet_content')) {
                $tweetContent = $event->getMeta('tweet_content')->value;
                $location = $event->location;
            }

            if (empty($photoUrl) && $event->is_tweetable) {
                $photoUrl = $event->photo_url;
            }
        }

        if (!empty($tweetContent) && !empty($photoUrl) && !empty($location)) {
            $file = file_get_contents($photoUrl);

            $uploaded_media = Twitter::uploadMedia([
                'media' => $file
            ]);

            $response = Twitter::postTweet([
                'status' => $tweetContent,
                'media_ids' => $uploaded_media->media_id_string,
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'possibly_sensitive' => false
            ]);

            \Log::info('Tweet posted for: ' . $date);
            \Log::info($response);
        } else {
            \Log::error('No events to tweet for: ' . $date);
        }
    }
}
