<?php

namespace App\Jobs\Locations;

use Carbon\Carbon;
use Goutte\Client as WebScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Jobs\ParseMusicEvent;

class CrawlTerminalWestLink implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
    * @var array
    */
    public $data;

    /**
    * @var SpotifyWebAPI
    */
    public $spotify;

    /**
    * @var array
    */
    public $categories;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data, $spotify, array $categories)
    {
        $this->data = $data;
        $this->spotify = $spotify;
        $this->categories = $categories;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // init data
        $event = $this->data;
        $categories = $this->categories;
        $spotify = $this->spotify;

        // get crawler
        $scraper = new WebScraper;

        $crawler = $scraper->request('GET', $event['website']);

        // get band names
        $bands = $crawler->filter('.event-section-item')->each(function ($node) use (&$event, $categories) {
            $band = trim($node->filter('.section-content > .title')->text());

            try {
                $info = strtolower(trim($node->filter('.section-content')->text()));

                // is family friendly?
                if (strstr($info, 'family')) {
                    $event['is_family_friendly'] = true;
                }

                // look for comedy event
                if (strstr($info, 'comedy')) {
                    $event['category_id'] = $categories['comedy']->id;
                }
            } catch (\Exception $e) {

            }

            return $band;
        });

        if (!empty($bands)) {
            $event['bands'] = $bands;
        }

        dispatch(new ParseMusicEvent($event, $spotify));
    }
}
