<?php

namespace App\Jobs\Locations;

use Carbon\Carbon;
use GuzzleHttp\Client as Guzzle;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Jobs\ParseMusicEvent;

class CrawlMasqueradeLink implements ShouldQueue
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
        $this->data    = $data;
        $this->spotify = $spotify;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data    = $this->data;
        $spotify = $this->spotify;

        $startDate = Carbon::parse($data['start_date']);

        $event = [
            'name'          => $data['name'] ?? '',
            'location_id'   => $data['location_id'],
            'user_id'       => 1,
            'category_id'   => $data['category_id'],
            'event_type_id' => 2,
            'start_date'    => $startDate->format('Y-m-d'),
            'price'         => $data['price'] ?? '',
            'start_time'    => $data['start_time'] ?? '',
            'end_time'      => '',
            'website'       => $data['website'],
            'is_sold_out'   => $data['is_sold_out'] ?? false,
            'tags'          => [],
            'bands'         => [],
        ];

        // parse start & end time
        if (!empty($event['start_time'])) {
            try {
                $startTimeObj        = Carbon::parse($event['start_date'] . ' ' . $event['start_time']);
                $event['start_time'] = $startTimeObj->format('g:i A');
                $event['end_time']   = $startTimeObj->copy()->addHours(3)->format('g:i A');
            } catch (\Exception $e) {
                \Log::error('CrawlMasqueradeLink time parse: ' . $e->getMessage());
            }
        }

        // fetch individual event page for price
        // detail page .time-show format: "Doors 9:00 pm / $15 - $20 ADV / 18+"
        if (!empty($data['detail_url'])) {
            try {
                $client   = new Guzzle([
                    'timeout' => 15,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    ],
                ]);
                $response = $client->get($data['detail_url']);
                $html     = (string) $response->getBody();
                $crawler  = new Crawler($html);

                $timeShow = $crawler->filter('.time-show');
                if ($timeShow->count() > 0) {
                    $timeText = trim($timeShow->text());
                    // split "Doors X:XX pm / $XX ADV / 18+" by " / "
                    $segments = array_map('trim', explode('/', $timeText));
                    foreach ($segments as $segment) {
                        if (stripos($segment, 'free') !== false || stripos($segment, 'no cover') !== false) {
                            $event['price'] = 'Free';
                            break;
                        }
                        if (strpos($segment, '$') !== false) {
                            $event['price'] = $segment;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('CrawlMasqueradeLink fetch: ' . $e->getMessage());
            }
        }

        // build bands list: headliner first, then support acts
        $event['bands'][] = $event['name'];

        if (!empty($data['bands'])) {
            foreach ($data['bands'] as $band) {
                $band = trim($band);
                if (!empty($band) && $band !== $event['name']) {
                    $event['bands'][] = $band;
                }
            }
        }

        if (count($event['bands']) > 1) {
            $supporting = $event['bands'];
            array_shift($supporting);
            $event['short_description'] = 'With ' . implode(', ', $supporting);
        }

        dispatch(new ParseMusicEvent($event, $spotify));
    }
}
