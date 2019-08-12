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

class CrawlVenkmansLink implements ShouldQueue
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
    * @var array
    */
    public $eventTypes;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data, $spotify, array $categories, array $eventTypes)
    {
        $this->data = $data;
        $this->spotify = $spotify;
        $this->categories = $categories;
        $this->eventTypes = $eventTypes;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // init data
        $data = $this->data;
        $eventTypes = $this->eventTypes;
        $categories = $this->categories;
        $spotify = $this->spotify;

        // parse date
        $startDate = Carbon::parse($data['start_date']);

        // get crawler
        $scraper = new WebScraper;

        $crawler = $scraper->request('GET', $data['website']);

        // event array
        $event = [
            'name' => '',
            'location_id' => $data['location_id'],
            'user_id' => 1,
            'category_id' => $data['category_id'],  
            'event_type_id' => 2,
            'start_date' => $startDate->format('Y-m-d'),
            'price' => '',
            'start_time' => '',
            'end_time' => '',
            'website' => trim($data['website']),
            'is_sold_out' => false,
            'tags' => [],
            'bands' => []
        ];

        // get the name of the event
        $event['name'] = trim($crawler->filter('.tribe-events-single-event-title')->text());

        // parse event name to figure out category
        $lowerName = strtolower($event['name']);
        $is_special = false;

        if (strstr($lowerName, 'free early show') || strstr($lowerName, 'free show') || strstr($lowerName, 'free:')) {
            $event['price'] = 'Free';
        }

        if (strstr($lowerName, 'kids eat free')) {
            $event['price'] = 'Free';
            $event['is_family_friendly'] = true;
            $event['category_id'] = $categories['food-drinks']->id;

            $is_special = true;
        }

        if (strstr($lowerName, 'bottomless mimosa')) {
            $event['category_id'] = $categories['food-drinks']->id;

            $is_special = true;
        }

        if (strstr($lowerName, 'taco wednesday')) {
            $event['price'] = 'Special';
            $event['is_family_friendly'] = true;
            $event['category_id'] = $categories['food-drinks']->id;

            $is_special = true;
        }

        if (strstr($lowerName, 'princess brunch')) {
            $event['price'] = 'Free';
            $event['is_family_friendly'] = true;
            $event['category_id'] = $categories['food-drinks']->id;

            $is_special = true;
        }

        if (strstr($lowerName, 'brunch dine out')) {
            $event['is_family_friendly'] = true;
            $event['category_id'] = $categories['food-drinks']->id;

            $is_special = true;
        }

        if (strstr($lowerName, 'trivia')) {
            $event['price'] = 'Free';
            $event['category_id'] = $categories['food-drinks']->id;

            $is_special = true;
        }

        if ($is_special) {
            $event['event_type_id'] = $eventTypes['special']->id;
        }

        // get start and if listed, end time

        // start time
        try {
            $date = trim($crawler->filter('.tribe-event-date-start')->text());

            $ex = explode(' @ ', $date);

            if (!empty($ex[1])) {
                $event['start_time'] = Carbon::parse($event['start_date'] . ' ' . trim($ex[1]));
                $event['start_time'] = $event['start_time']->format('g:i A');
            }
        } catch (\Exception $e) {

        }

        // end time
        try {
            $end_time = trim($crawler->filter('.tribe-event-time')->text());

            $event['end_time'] = Carbon::parse($event['start_date'] . ' ' . $end_time);
            $event['end_time'] = $event['end_time']->format('g:i A');
        } catch (\Exception $e) {
            
        }

        // set default start time if empty
        if (empty($event['start_time'])) {
            $event['start_time'] = Carbon::parse($event['start_date'] . ' 17:30:00');
            $event['start_time'] = $event['start_time']->format('g:i A');
        }

        // set default end time if empty
        if (empty($event['end_time'])) {
            $start_time = Carbon::parse($event['start_date'] . ' ' . $event['start_time']);

            $event['end_time'] = $start_time->addHours(3)->format('g:i A');
        }

        // figure out price
        try {
            $event['price'] = trim($crawler->filter('.tribe-events-cost')->text());
        } catch (\Exception $e) {

        }

        // set a default price if empty
        if (empty($event['price'])) {
            $event['price'] = 'Special';
        }

        // if event is music related, collect the bands
        if ($event['category_id'] === $categories['music']->id) {
            if (strstr($event['name'], 'Yacht Rock')) {
                $event['bands'][] = 'Yacht Rock Revue';
            }

            if (strstr($event['name'], 'w/')) {
                $ex = explode('w/ ', $event['name']);

                $event['bands'][] = trim($ex[1]);
            }

            if (strstr($event['name'], 'Tribute with')) {
                $ex = explode('Tribute with ', $event['name']);

                $event['bands'][] = trim($ex[1]);
            }
        }

        dispatch(new ParseMusicEvent($event, $spotify));
    }
}
