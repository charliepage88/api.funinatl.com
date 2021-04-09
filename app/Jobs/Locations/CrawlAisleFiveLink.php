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
      $event['name'] = Str::title(trim($crawler->filter('.event-h2')->text()));

      // get start time
      try {
        $start_time = $crawler->filter('.event-bar-right .event-h3')->each(function ($node) {
          $text = trim($node->text());

          if (strstr($text, 'Show:')) {
            $ex = explode('Show:', $text);

            return trim($ex[1]);
          }
        });

        foreach ($start_time as $row) {
          if (!empty($row)) {
            $start_time = Carbon::parse($event['start_date'] . ' ' . $row);

            // set start time
            $event['start_time'] = $start_time->copy()->format('g:i A');

            // add 3 hours to start time
            $event['end_time'] = $start_time->copy()->addHours(3)->format('g:i A');
          }
        }
      } catch (\Exception $e) {
        \Log::error($e->getMessage());
        //
      }

      // figure out price
      try {
        $event['price'] = trim($crawler->filter('.price')->eq(1)->text());
        $event['price'] = str_replace('(price)', '', $event['price']);
      } catch (\Exception $e) {
        //
      }

      // check if sold out
      try {
        $is_sold_out = $crawler->filter('.ticket-price > .sold-out')->text();

        if (!empty($is_sold_out)) {
          $event['is_sold_out'] = true;
        }
      } catch (\Exception $e) {
        //
      }

      // get list of bands

      // first, let's get the main band
      $event['bands'][] = $event['name'];

      // now, let's get any supporting bands
      try {
        $eventSiteDate = Carbon::parse($event['start_date'])->format('l, M j, Y');
        $bands = $crawler->filter('.event-bar-left .event-h3')->each(function ($node) use ($eventSiteDate) {
          $text = trim($node->text());

          if (!strstr($text, 'Aisle 5') && $text === $eventSiteDate) {
            return $text;
          }
        });

        foreach($bands as $row) {
          if (!empty($row)) {
            $ex = explode(', ', $row);

            foreach ($ex as $item) {
              $event['bands'][] = Str::title(trim($item));
            }
          }
        }

        if (!empty($event['bands'])) {
          $event['short_description'] = 'With ' . implode(', ', $event['bands']);
        }
      } catch (\Exception $e) {
        //
      }

      dispatch(new ParseMusicEvent($event, $spotify));
    }
}
