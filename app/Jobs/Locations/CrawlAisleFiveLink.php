<?php

namespace App\Jobs\Locations;

use Carbon\Carbon;
use Goutte\Client as WebScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;

use App\Jobs\ParseMusicEvent;

class CrawlAisleFiveLink implements ShouldQueue
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
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data, $spotify)
    {
        $this->data = $data;
        $this->spotify = $spotify;
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
            'website' => $data['website'],
            'is_sold_out' => false,
            'tags' => [],
            'bands' => []
        ];

        // get the name of the event
        $event['name'] = Str::title(trim($crawler->filter('.headliners.summary')->text()));

        // get start time
        try {
            $start_time = trim($crawler->filter('.start.dtstart')->text());
            $start_time = str_replace($startDate->format('F d') . ' @ ', '', $start_time);

            $start_time = Carbon::parse($event['start_date'] . ' ' . $start_time);

            // set start time
            $event['start_time'] = $start_time->copy()->format('g:i A');

            // add 3 hours to start time
            $event['end_time'] = $start_time->copy()->addHours(3)->format('g:i A');
        } catch (\Exception $e) {

        }

        // figure out price
        try {
            $event['price'] = trim($crawler->filter('.price-range')->text());
        } catch (\Exception $e) {

        }

        // check if sold out
        try {
            $is_sold_out = $crawler->filter('.ticket-price > .sold-out')->text();

            if (!empty($is_sold_out)) {
                $event['is_sold_out'] = true;
            }
        } catch (\Exception $e) {

        }

        // get list of bands

        // first, let's get the main band
        try {
            $headliner = $crawler->filter('.artist-box-headliner > .artist-headline > .artist-name')->text();

            if (!empty($headliner)) {
                $headliner = trim($headliner);

                $event['bands'][] = $headliner;
            }
        } catch (\Exception $e) {

        }

        // now, let's get any supporting bands
        try {
            $checkForSupportingBands = $crawler->filter('.artist-box-support')->text();

            if (!empty($checkForSupportingBands)) {
                $bands = $crawler->filter('.artist-box-support')->each(function ($node) {
                    return trim($node->filter('.artist-headline > .artist-name')->text());
                });

                if (!empty($bands)) {
                    foreach($bands as $row) {
                        if (!empty($row)) {
                            $event['bands'][] = $row;
                        }
                    }

                    $event['short_description'] = 'With ' . implode(', ', $bands);
                }
            }
        } catch (\Exception $e) {

        }

        dispatch(new ParseMusicEvent($event, $spotify));
    }
}
