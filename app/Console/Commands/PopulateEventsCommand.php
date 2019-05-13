<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Goutte\Client as WebScraper;
use SpotifyWebAPI\SpotifyWebAPI;

use App\Provider;
use App\Jobs\ParseEvent;

use Cache;

class PopulateEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape websites to populate events.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $spotify = $this->initSpotify();

        $scraper = new WebScraper;

        $providers = Provider::isActive()->get();

        foreach($providers as $provider) {
            $methodName = Str::camel('provider' . $provider->name);

            if (method_exists($this, $methodName)) {
                $this->$methodName($provider, $scraper, $spotify);
            }
        }
    }

    /**
    * Init Spotify
    *
    * @return object
    */
    public function initSpotify()
    {
        // get new access token
        if (!Cache::has('spotify_access_token')) {
            $this->info('getting new access token for Spotify');

            $session = new \SpotifyWebAPI\Session(
                env('SPOTIFY_CLIENT_ID'),
                env('SPOTIFY_CLIENT_SECRET')
            );

            $session->requestCredentialsToken();
            $accessToken = $session->getAccessToken();

            if (!empty($accessToken)) {
                Cache::put('spotify_access_token', $accessToken, 60);
            } else {
                throw new \Exception('Cannot get access token from Spotify');
            }
        } else {
            $accessToken = Cache::get('spotify_access_token');
        }

        // init spotify instance
        $spotify = new SpotifyWebAPI;

        $spotify->setAccessToken($accessToken);

        return $spotify;
    }

    /**
    * Provider The Earl
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerTheEarl(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        $crawler = $scraper->request('GET', $provider->scrape_url);

        // Get the latest post in this category and display the titles
        $results = $crawler->filter('.schedule-show-container')->each(function ($node) use ($provider) {
            $data = [
                'name' => '',
                'location_id' => $provider->location_id,
                'user_id' => 1,
                'category_id' => $provider->location->category_id,  
                'event_type_id' => 2,
                'start_date' => '',
                'price' => '',
                'start_time' => '',
                'end_time' => '',
                'website' => ''
            ];

            // get date & start/end time
            $date = trim($node->filter('.schedule-show-left > .schedule-show-date')->text());
            $date = explode('|', $date);

            $time = explode('doors at ', trim($date[1]));

            // get date object
            $dateObj = Carbon::parse(trim($date[0]) . ' ' . $time[1]);

            $start_time = $dateObj->format('g:i A');
            $end_time = $dateObj->copy()->addHours(3)->format('g:i A');

            $data['start_time'] = $start_time;
            $data['end_time'] = $end_time;
            $data['start_date'] = Carbon::parse(trim($date[0]))->format('Y-m-d');

            // get pricing
            $prices = $node->filter('.schedule-show-right > .schedule-ui-elements > .schedule-prices-contain > .schedule-price')->each(function ($childNode) {
                return $childNode->text();
            });

            if (!empty($prices)) {
                if ($prices[0] === $prices[1]) {
                    $data['price'] = $prices[0];
                } else {
                    $data['price'] = $prices[0] . ' - ' . $prices[1];
                }
            }

            // get event basic info
            $info = $node->filter('.schedule-show-left > .schedule-show-B-headliner > a');

            $data['website'] = $provider->location->website . '/' . $info->attr('href');
            $data['name'] = Str::title(trim($info->text()));

            return $data;
        });

        $this->info(count($results) . ' events found for provider `' . $provider->name . '`');

        foreach($results as $event) {
            ParseEvent::dispatch($event, $spotify);

            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }
    }
}
