<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Event;

use Twitter;

class TwitterPostCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:post';

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
    }
}
