<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Goutte\Client as WebScraper;
use SpotifyWebAPI\SpotifyWebAPI;

use App\Category;
use App\EventType;
use App\Provider;
use App\Jobs\ParseEvent;
use App\Jobs\Locations\Venkmans\CrawlLink as CrawlVenkmansLink;

use Cache;
use DB;

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
        // init spotify
        $spotify = $this->initSpotify();

        // get active providers
        // that haven't run today
        $today = Carbon::today()->startOfDay()->format('Y-m-d H:i:s');

        $providers = Provider::isActive()
            ->where('last_scraped', '<=', $today)
            ->orWhereNull('last_scraped')
            ->get();

        $scraper = new WebScraper;
        foreach($providers as $provider) {
            $find = [
                '"',
                "'"
            ];

            $providerName = str_replace($find, '', $provider->name);
            $methodName = Str::camel('provider' . $providerName);

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
                'website' => '',
                'is_sold_out' => false
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

            // donations check & sold out check
            if (empty($data['price'])) {
                try {
                    $soldOutCheck = $node->filter('.schedule-show-right > .schedule-ui-elements > .schedule-sold-out');

                    if ($text = $soldOutCheck->text()) {
                        if ($text === 'DONATIONS') {
                            $data['price'] = 'Donations';
                        } elseif ($text === 'SOLD OUT') {
                            $data['is_sold_out'] = true;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                }
            }

            // get event basic info
            $info = $node->filter('.schedule-show-left > .schedule-show-B-headliner > a');

            $data['website'] = $provider->location->website . $info->attr('href');

            // check for multiple artists
            $html = $info->html();

            if (strstr($html, '<br>')) {
                $name = str_replace('<br>', ', ', $html);

                $data['name'] = Str::title(trim($name));
            } else {
                $data['name'] = Str::title(trim($info->text()));
            }

            return $data;
        });

        $this->info(count($results) . ' events found for provider `' . $provider->name . '`');

        // fire off data into queue
        foreach($results as $event) {
            ParseEvent::dispatch($event, $spotify);

            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        // save last scraped time
        $provider->last_scraped = Carbon::now();

        $provider->save();
    }

    /**
    * Provider Northside Tavern
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerNorthsideTavern(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        $crawler = $scraper->request('GET', $provider->scrape_url);

        // Get the latest post in this category and display the titles
        $path = '#main-content > .article > .article-content > .RichTextElement';
        $path .= ' > div > p';

        $results = [];
        $crawler->filter($path)->each(function ($node, $index) use ($provider, &$results) {
            // base events info - generic. skip it.
            if ($index === 0) {
                return false;
            }

            // listing of early show bands
            if ($index === 1) {
                $monthAndYear = $node->filter('span')->eq(0)->text();

                $earlyShows = $node->filter('span')->eq(1)->html();
                $earlyShows = explode('<br>', $earlyShows);

                $shows = [];
                foreach($earlyShows as $show) {
                    $ex = explode('Early Show', $show);

                    if (empty($ex[0]) || empty($ex[1])) {
                        continue;
                    }

                    $bandName = str_replace(' ', '', trim($ex[0]));
                    $showDate = str_replace(' ', '', trim($ex[1]));

                    // parse date info
                    $showDate = explode(' ', $showDate);

                    if (empty($showDate[0]) || empty($showDate[1])) {
                        continue;
                    }

                    // figure out day of week
                    $dayOfWeek = null;
                    $price = '$10';
                    switch ($showDate[0]) {
                        case 'Friday':
                            $dayOfWeek = Carbon::FRIDAY;
                            $dayName = 'Friday';
                        break;

                        case 'Saturday':
                            $dayOfWeek = Carbon::SATURDAY;
                            $dayName = 'Saturday';
                        break;

                        case 'Sunday': case 'Sundays':
                            $dayOfWeek = Carbon::SUNDAY;
                            $dayName = 'Sunday';
                            $price = 'Free';
                        break;
                    }

                    // figure out start & end time
                    $startTime = null;
                    $endTime = null;

                    $ex = explode('-', $showDate[1]);

                    if (empty($ex[0]) || empty($ex[1])) {
                        continue;
                    }

                    if (strlen($ex[0]) === 1) {
                        $startTime = $ex[0] . ':00 PM';
                    } elseif (strlen($ex[0]) === 4) {
                        $startTime = $ex[0] . ' PM';
                    }

                    if (strlen($ex[1]) === 1) {
                        $endTime = $ex[1] . ':00 PM';
                    } elseif (strlen($ex[1]) === 4) {
                        $endTime = $ex[1] . ' PM';
                    }

                    $shows[] = [
                        'name' => $bandName,
                        'day_of_week' => $dayOfWeek,
                        'day_name' => $dayName,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'price' => $price
                    ];
                }

                if (!empty($shows)) {
                    $today = Carbon::now()->startOfDay();

                    foreach($shows as $show) {
                        $start_date = $today->copy()->next($show['day_of_week']);

                        if ($today->diffInDays($start_date) === 7) {
                            $start_date = $today->copy();
                        }

                        $end_date = new Carbon('last ' . $show['day_name'] . ' of this month');

                        for($date = $start_date; $date->lte($end_date); $date->next($show['day_of_week'])) {
                            $data = [
                                'name' => $show['name'],
                                'location_id' => $provider->location_id,
                                'user_id' => 1,
                                'category_id' => $provider->location->category_id,  
                                'event_type_id' => 2,
                                'start_date' => $date->format('Y-m-d'),
                                'price' => $show['price'],
                                'start_time' => $show['start_time'],
                                'end_time' => $show['end_time'],
                                'website' => $provider->location->website,
                                'is_sold_out' => false
                            ];

                            $results[] = $data;
                        }
                    }
                }
            }

            // now we can get the main shows
            if ($index > 1) {
                /*
                $content = trim($node->filter('span')->text());

                if ($content !== '--') {

                }
                */

                // formatting changes too much, skip main shows for now
            }
        });

        $this->info(count($results) . ' events found for provider `' . $provider->name . '`');

        // fire off data into queue
        foreach($results as $event) {
            ParseEvent::dispatch($event, $spotify);

            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        // save last scraped time
        $provider->last_scraped = Carbon::now();

        $provider->save();
    }

    /**
    * Provider Terminal West
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerTerminalWest(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        $crawler = $scraper->request('GET', $provider->scrape_url);

        // Get the latest post in this category and display the titles
        $results = $crawler->filter('article.event')->each(function ($node) use ($provider) {
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
                'website' => '',
                'is_sold_out' => false
            ];

            // get band name & other bands
            $data['name'] = trim($node->filter('.middle-info > .headliner a')->text());
            
            try {
                $data['short_description'] = trim($node->filter('.middle-info > .headliner_support')->text());
            } catch (\Exception $e) {
                // do nothing
            }

            try {
                $data['description'] = $node->filter('.middle-info > p')->text();
            } catch (\Exception $e) {
                // do nothing
            }

            // get price & start time
            $details = $node->filter('.middle-info > .bottom-list > ul > li')
                ->reduce(function ($childNode, $index) {
                    return $index <= 1;
                })
                ->each(function ($childNode, $index) {
                    if ($index === 0) {
                        return [ 'price' => $childNode->text() ];
                    }

                    if ($index === 1) {
                        $text = $childNode->text();

                        $find = [
                            'show',
                            'doors'
                        ];

                        $text = str_replace($find, '', $text);

                        return [ 'time' => trim($text) ];
                    }

                    return null;
                });

            foreach($details as $row) {
                if (!empty($row['price'])) {
                    $data['price'] = $row['price'];
                }

                if (!empty($row['time'])) {
                    $ex = explode('  ', $row['time']);

                    $dateObj = Carbon::parse($data['start_date'] . ' ' . $ex[1]);

                    $data['start_time'] = $dateObj->format('g:i A');
                    $data['end_time'] = $dateObj->copy()->addHours(3)->format('g:i A');
                }
            }

            // get date & website
            $data['start_date'] = trim($node->filter('.right-buttons > .date')->text());
            $data['start_date'] = Carbon::parse($data['start_date'])->format('Y-m-d');

            $data['website'] = $node->filter('.right-buttons > a.more')->attr('href');

            return $data;
        });

        $this->info(count($results) . ' events found for provider `' . $provider->name . '`');

        // fire off data into queue
        foreach($results as $event) {
            ParseEvent::dispatch($event, $spotify);

            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        // save last scraped time
        $provider->last_scraped = Carbon::now();

        $provider->save();
    }

    /**
    * Provider 529
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function provider529(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        $crawler = $scraper->request('GET', $provider->scrape_url);

        // Get the latest post in this category and display the titles
        $results = $crawler->filter('.event-container-single')->each(function ($node) use ($provider) {
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
                'website' => '',
                'is_sold_out' => false,
                'tags' => []
            ];

            // get date for event
            $date = trim($node->filter('.event-date-single > .right')->text());

            $data['start_date'] = Carbon::parse($date)->format('Y-m-d');

            // get website
            $data['website'] = $node->filter('.event-info > .left-column > .event-buttons > a')
                ->reduce(function ($childNode) {
                    return strtolower(trim($childNode->text())) === 'info';
                })->attr('href');

            // get meta info
            // for price, time & tags
            $meta = trim($node->filter('.event-info > .left-column > .event-meta')->text());
            $meta = explode('|', $meta);
            
            foreach($meta as $index => $value) {
                $value = trim($value);

                if ($value === '21+' || $value === '18+') {
                    $data['tags'][] = $value;
                } elseif (strstr(strtolower($value), ' pm')) {
                    $dateObj = Carbon::parse($data['start_date'] . ' ' . $value);

                    $data['start_time'] = $dateObj->format('g:i A');
                    $data['end_time'] = $dateObj->copy()->addHours(3)->format('g:i A');
                } else {
                    $ex = explode('/', $value);

                    if (count($ex) === 2) {
                        $find = [
                            'ADV',
                            'DOS'
                        ];

                        $price_from = str_replace($find, '', trim($ex[0]));
                        $price_to = str_replace($find, '', trim($ex[1]));

                        $data['price'] = $price_from . ' - ' . $price_to;
                    } else {
                        $data['price'] = trim($ex[0]);
                    }
                }
            }

            // check for no-smoking
            try {
                $value = trim($node->filter('.event-info > .left-column > .event-meta > .no-smoking')->attr('src'));

                $data['tags'][] = 'No Smoking';
            } catch (\Exception $e) {
                // do nothing
            }

            // get band name(s)
            $bands = $node->filter('.event-info > .left-column > .event-headliners')
                ->each(function ($childNode) {
                    return trim($childNode->text());
                });

            if (!empty($bands)) {
                $data['name'] = $bands[0];

                if (count($bands) > 1) {
                    $bandsAfterFirst = $bands;

                    unset($bandsAfterFirst[0]);

                    $data['description'] = implode(' | ', $bandsAfterFirst);
                }
            }

            // include lesser known bands in description
            try {
                $backupBands = trim($node->filter('.event-info > .left-column > .event-bands')->text());

                $value = str_replace(' | ', ', ', $backupBands);

                $data['short_description'] = 'Also with music from: ' . $value;

                if (empty($data['name'])) {
                    $ex = explode(' | ', $backupBands);

                    $data['name'] = $ex[0];
                }
            } catch (\Exception $e) {
                // do nothing
            }

            return $data;
        });

        $this->info(count($results) . ' events found for provider `' . $provider->name . '`');

        // validation
        $required_fields = [
            'name',
            'start_date',
            'start_time',
            'end_time',
            'website',
            'price'
        ];

        $errors = [];
        foreach($results as $key => $event) {
            $isValid = true;

            foreach($required_fields as $field) {
                if (empty($event[$field])) {
                    if (!isset($errors[$key])) {
                        $errors[$key] = [];
                    }

                    $errors[$key][$field] = $event;
                }
            }
        }

        if (!empty($errors)) {
            \Log::error($errors);

            return false;
        }

        // fire off data into queue
        foreach($results as $event) {
            ParseEvent::dispatch($event, $spotify);

            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        // save last scraped time
        $provider->last_scraped = Carbon::now();

        $provider->save();
    }

    /**
    * Provider Venkmans
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerVenkmans(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        // get events for 3 months
        $months = [];

        $months['current'] = Carbon::now();
        $months['next'] = $months['current']->copy()->addMonth();
        $months['third'] = $months['next']->copy()->addMonth();

        $urls = [];
        foreach($months as $month) {
            $url = $provider->scrape_url . '/' . $month->format('Y-m');

            $crawler = $scraper->request('GET', $url);

            try {
                $noResultsFind = $crawler->filter('.tribe-events-notices')->text();

                if (!empty($noResultsFind)) {
                    $status = false;
                }
            } catch (\Exception $e) {
                $status = true;
            }

            if ($status) {
                // let's just collect links, that's it
                $today = Carbon::now();
                $links = [];

                $crawler->filter('.tribe-events-thismonth')->each(function ($parentNode) use ($today, &$links, $provider) {
                    $startDate = Carbon::parse($parentNode->attr('data-day'));

                    if ($startDate->greaterThanOrEqualTo($today)) {
                        $parentNode->filter('.tribe_events')->each(function ($linkNode) use ($startDate, &$links, $provider) {
                            $url = $linkNode->filter('.tribe-events-month-event-title > a')->attr('href');

                            $links[] = [
                                'website' => $url,
                                'start_date' => $startDate,
                                'location_id' => $provider->location_id,
                                'category_id' => $provider->location->category_id
                            ];

                            return true;
                        });
                    }

                    return true;
                });

                foreach($links as $link) {
                    $urls[] = $link;
                }
            }
        }

        $this->info(count($urls) . ' links found that need to be crawled for provider `' . $provider->name . '`');

        // fire off data into queue
        $items = Category::all();

        $categories = [];
        foreach($items as $item) {
            $categories[$item->slug] = $item;
        }

        $items = EventType::all();

        $eventTypes = [];
        foreach($items as $item) {
            $eventTypes[$item->slug] = $item;
        }

        $delays = [];
        $max = 300;
        foreach($urls as $data) {
            do {
                $rand = rand(15, $max);

                if (!in_array($rand, $delays)) {
                    $delays[] = $rand;

                    break;
                }
            } while (0);

            CrawlVenkmansLink::dispatch($data, $spotify, $categories, $eventTypes)
                ->delay(now()->addSeconds($rand));

            $this->info('Dispatching crawler for url: ' . $data['website'] . '. Delay: ' . $rand);
        }

        // save last scraped time
        $provider->last_scraped = Carbon::now();

        $provider->save();
    }
}
