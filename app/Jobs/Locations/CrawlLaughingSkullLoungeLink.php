<?php

namespace App\Jobs\Locations;

use Carbon\Carbon;
use Goutte\Client as WebScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Jobs\ParseEvent;

class CrawlLaughingSkullLoungeLink implements ShouldQueue
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
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
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
        $url = trim($data['website']);
        $errors = [];
        $isValid = true;

        // get crawler
        $scraper = new WebScraper;

        $crawler = $scraper->request('GET', $url);

        // event array
        $event = [
            'name' => '',
            'location_id' => $data['location_id'],
            'user_id' => 1,
            'category_id' => $data['category_id'],  
            'event_type_id' => 2,
            'start_date' => '',
            'price' => '',
            'start_time' => '',
            'end_time' => '',
            'website' => $url,
            'is_sold_out' => false,
            'tags' => [],
            'bands' => []
        ];

        // get the name of the event
        $event['name'] = trim($crawler->filter('.showTitle > span')->text());

        // get start/end time & set date
        $dateTime = trim($crawler->filter('.tm-date')->text());
        $dateTime = Carbon::parse($dateTime);

        $event['start_date'] = $dateTime->format('Y-m-d');
        $event['start_time'] = $dateTime->format('g:i A');

        $endTime = $dateTime->copy()->addHours(3);

        $event['end_time'] = $endTime->format('g:i A');

        // restrictions (i.e. tags)
        try {
            $restrictions = $crawler->filter('.restrictions > em')->each(function ($node) {
                return trim($node->text());
            });

            foreach($restrictions as $row) {
                if (strstr($row, '21 ')) {
                    $data['tags'][] = '21+';
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        // get price
        $prices = $crawler->filter('.ticketpricecontainer > em')->each(function ($node) {
            return trim($node->text());
        });

        if (!empty($prices)) {
            $cleanPrice = function ($price) {
                $find = [
                    "\r",
                    "\n",
                    '\r',
                    '\n',
                    '\t',
                    "\t"
                ];

                $price = trim(str_replace($find, '', $price));

                if (strstr($price, '-')) {
                    $ex = explode('-', $price);

                    if (count($ex) === 2) {
                        $price = trim($ex[0]) . ' - ' . trim($ex[1]);
                    }
                }

                return $price;
            };

            if (count($prices) === 1) {
                $event['price'] = $cleanPrice($prices[0]);
            }

            if (count($prices) === 2) {
                $event['price'] = $cleanPrice($prices[0]) . ' - ' . $cleanPrice($prices[1]);
            }
        }

        if (empty($event['price'])) {
            $isValid = false;
            $errors[] = 'price';
        }

        // populate image
        try {
            $images = $crawler->filter('.thumbnail > img')->each(function ($node) {
                return $node->attr('src');
            });

            if (!empty($images)) {
                $event['image_url'] = $images[0];
            }
        } catch (\Exception $e) {
            // do nothing
        }

        // if valid, create/update event
        // otherwise log an error
        if ($isValid) {
            dispatch(new ParseEvent($event));
        } else {
            \Log::error('Cannot create event, missing fields: ' . implode(', ', $errors));
            \Log::error($event);
        }
    }
}
