<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Goutte\Client as WebScraper;
use GuzzleHttp\Client as Guzzle;
use ICal\ICal;
use SpotifyWebAPI\SpotifyWebAPI;
use Symfony\Component\DomCrawler\Crawler;

use App\Category;
use App\EventType;
use App\Provider;
use App\Jobs\ParseEvent;
use App\Jobs\ParseMusicEvent;
use App\Jobs\Locations\CrawlVenkmansLink;
use App\Jobs\Locations\CrawlAisleFiveLink;
use App\Jobs\Locations\CrawlTerminalWestLink;

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
            // ->where('id', '=', 9)
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
                $this->info('Starting scraper for `' . $provider->name . '`');

                $this->$methodName($provider, $scraper, $spotify);
            } else {
                $this->error('Cannot find method name `' . $methodName . '`');
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
    * Validate
    *
    * @param array $events
    *
    * @return boolean
    */
    public function validate(array $events)
    {
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
        foreach($events as $key => $event) {
            $isValid = true;

            foreach($required_fields as $field) {
                if (empty($event[$field])) {
                    $errors[] = [
                        'error' => 'Field is required: ' . $field,
                        'event' => $event
                    ];
                }
            }
        }

        if (!empty($errors)) {
            foreach($errors as $error) {
                $this->error($error['error']);
                $this->error(json_encode($error['event']));
                $this->info('---');
            }

            return false;
        } else {
            return true;
        }
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
        // get crawler
        $crawler = $scraper->request('GET', $provider->scrape_url);

        // Get the latest post in this category and display the titles
        $events = $crawler->filter('.schedule-show-container')->each(function ($node) use ($provider) {
            $event = [
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
                'is_family_friendly' => $provider->location->is_family_friendly,
                'is_explicit' => false,
                'bands' => [],
                'tags' => []
            ];

            // get date & start/end time
            $date = trim($node->filter('.schedule-show-left > .schedule-show-date')->text());
            $date = explode('|', $date);

            $time = explode('doors at ', trim($date[1]));

            // get date object
            $dateObj = Carbon::parse(trim($date[0]) . ' ' . $time[1]);

            $start_time = $dateObj->format('g:i A');
            $end_time = $dateObj->copy()->addHours(3)->format('g:i A');

            $event['start_time'] = $start_time;
            $event['end_time'] = $end_time;
            $event['start_date'] = Carbon::parse(trim($date[0]))->format('Y-m-d');

            // get pricing
            $prices = $node->filter('.schedule-show-right > .schedule-ui-elements > .schedule-prices-contain > .schedule-price')->each(function ($childNode) {
                return $childNode->text();
            });

            if (!empty($prices)) {
                if ($prices[0] === $prices[1]) {
                    $event['price'] = $prices[0];
                } else {
                    $event['price'] = $prices[0] . ' - ' . $prices[1];
                }
            }

            // donations check & sold out check
            try {
                $soldOutText = trim($node->filter('.schedule-show-right > .schedule-ui-elements > .schedule-sold-out')->text());
                $soldOutText = strtolower($soldOutText);

                switch ($soldOutText) {
                    case 'donations':
                        $event['price'] = 'Donations';
                    break;

                    case 'sold out':
                        $event['is_sold_out'] = true;
                    break;

                    case 'free event':
                        $event['price'] = 'Free';
                    break;
                }
            } catch (\Exception $e) {

            }

            // get event basic info
            $info = $node->filter('.schedule-show-left > .schedule-show-B-headliner > a');

            $event['website'] = $provider->location->website . rtrim($info->attr('href'), '/');

            // check for multiple artists
            $html = $info->html();

            if (strstr($html, '<br>')) {
                $name = str_replace('<br>', ', ', $html);

                $event['name'] = Str::title(trim($name));

                // collect band names
                $ex = explode('<br>', $html);

                if (!empty($ex)) {
                    foreach($ex as $row) {
                        $name = Str::title(trim($row));

                        if (!strstr($name, 'Two Night Pass')) {
                            $event['bands'][] = $name;
                        }
                    }
                }
            } else {
                $event['name'] = Str::title(trim($info->text()));

                $event['bands'][] = $event['name'];
            }

            // look for other info attached to event
            try {
                $info = trim($node->filter('.schedule-show-event-title')->text());

                if (!empty($info)) {
                    $event['name'] .= ', ' . Str::title($info);
                }
            } catch (\Exception $e) {

            }

            // get band names
            try {
                $bands = trim($node->filter('.schedule-show-support')->text());

                if (!empty($bands)) {
                    $ex = explode(' | ', $bands);

                    if (!empty($ex)) {
                        foreach($ex as $row) {
                            $name = trim($row);

                            $name = str_replace(' (solo)', '', $name);

                            $event['bands'][] = $name;
                        }
                    }
                }
            } catch (\Exception $e) {

            }

            // unique array for bands
            if (count($event['bands'])) {
                $event['bands'] = array_unique($event['bands']);

                foreach($event['bands'] as $key => $row) {
                    $band = strtolower($row);

                    if (strstr($band, 'fuck')) {
                        $event['is_explicit'] = true;
                    }

                    if (strstr($band, 'the official')) {
                        // unset($event['bands'][$key]);
                    }
                }
            }

            // is event non-smoking?
            try {
                $node->filter('.schedule-show-nonsmoking')->text();

                $event['tags'][] = 'No Smoking';
            } catch (\Exception $e) {

            }

            return $event;
        });

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        // validate
        $validator = $this->validate($events);

        if (!$validator) {
            return false;
        }

        // fire off data into queue
        foreach($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);

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
        // list upcoming months
        // to get shows
        $possibleValues = [];

        $startDate = Carbon::now();
        $endDate = $startDate->copy()->addMonths(4);

        for ($date = $startDate; $date->lte($endDate); $date->addMonth()) {
            $possibleValues[] = $date->format('F Y');
        }

        // get crawler
        $crawler = $scraper->request('GET', $provider->scrape_url);

        // Get the latest post in this category and display the titles
        $path = '#main-content > .article > .article-content > .RichTextElement';
        $path .= ' > div > p';

        $events = [];
        $currentValidTime = false;
        $crawler->filter($path)->each(function ($node, $index) use ($provider, &$events, $possibleValues, &$currentValidTime) {
            // base events info - generic. skip it.
            if ($index === 0) {
                return false;
            }

            // listing of early show bands
            // look for valid date string
            $isEarlyShows = false;
            $monthAndYear = null;
            try {
                $monthAndYear = trim($node->filter('span')->eq(0)->text());

                $isEarlyShows = (!empty($monthAndYear) && in_array($monthAndYear, $possibleValues));
            } catch (\Exception $e) {

            }

            if ($isEarlyShows) {
                $currentValidTime = $monthAndYear;

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
                        'name' => $bandName . ' Early Show',
                        'day_of_week' => $dayOfWeek,
                        'day_name' => $dayName,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'price' => $price,
                        'bands' => [
                            $bandName
                        ]
                    ];
                }

                if (!empty($shows)) {
                    $today = Carbon::now()->startOfDay();

                    foreach($shows as $show) {
                        $start_date = $today->copy()->next($show['day_of_week']);

                        if ($today->diffInDays($start_date) === 7) {
                            $start_date = $today->copy();
                        }

                        $end_date = new Carbon('last ' . $show['day_name'] . ' of ' . $monthAndYear);

                        for($date = $start_date; $date->lte($end_date); $date->next($show['day_of_week'])) {
                            $event = [
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
                                'is_sold_out' => false,
                                'bands' => $show['bands']
                            ];

                            $events[] = $event;
                        }
                    }
                }
            }

            // get normal listings
            if (!$isEarlyShows && $currentValidTime) {
                try {
                    $dayOfWeek = trim($node->filter('span')->eq(0)->text());

                    if (!empty($dayOfWeek) && in_array($dayOfWeek, [ 'Friday', 'Saturday' ])) {
                        // get day & band/event name
                        $eventName = trim($node->filter('span')->eq(1)->filter('strong')->text());
                        $eventName = htmlentities($eventName);
                        $eventName = str_replace('', ' ', $eventName);

                        $day = trim($node->filter('span')->eq(1)->text());

                        $day = htmlentities($day);
                        $day = str_replace('&nbsp;', '', $day);

                        $day = str_replace(' -' . $eventName, '', $day);
                        $day = str_replace(' - ' . $eventName, '', $day);
                        $day = str_replace('- ' . $eventName, '', $day);

                        if (empty($day) && $dayOfWeek === 'Saturday') {
                            $lastEventDate = $events[count($events) - 1]['start_date'];
                            $lastEventDate = Carbon::parse($lastEventDate);

                            $day = $lastEventDate->addDay()->format('jS');
                        }

                        // parse date
                        $ex = explode(' ', $currentValidTime);
                        $dateString = $ex[0] . ' ' . $day . ', ' . $ex[1];

                        $startDate = Carbon::parse($dateString);

                        // get start & end time
                        $startTime = Carbon::parse($dateString . ' 10:00 PM');
                        $endTime = $startTime->copy()->addHours(4);

                        $event = [
                            'name' => $eventName,
                            'location_id' => $provider->location_id,
                            'user_id' => 1,
                            'category_id' => $provider->location->category_id,  
                            'event_type_id' => 2,
                            'start_date' => $startDate->format('Y-m-d'),
                            'price' => '$10',
                            'start_time' => $startTime->format('g:i A'),
                            'end_time' => $endTime->format('g:i A'),
                            'website' => $provider->location->website,
                            'is_sold_out' => false,
                            'bands' => []
                        ];

                        $event['bands'][] = $eventName;

                        $events[] = $event;
                    }
                } catch (\Exception $e) {
                    if (strstr($e->getMessage(), 'node list is empty')) {
                        $currentValidTime = null;
                    } else {
                        $this->error('uh oh on: `' . $day . '` / `' . $eventName . '`');
                        $this->info('`' . substr($eventName, 0, 1) . '`');
                        // $this->error($e->getMessage());
                    }
                }
            }
        });

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        // validate
        $validator = $this->validate($events);

        if (!$validator) {
            return false;
        }

        // fire off data into queue
        foreach($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);

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
        // get crawler
        $crawler = $scraper->request('GET', $provider->scrape_url);

        // Get the latest post in this category and display the titles
        $events = $crawler->filter('article.event')->each(function ($node) use ($provider) {
            $event = [
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
                'bands' => []
            ];

            // get band name & other bands
            $event['name'] = trim($node->filter('.middle-info > .headliner a')->text());

            // look for supporting bands
            try {
                $supporting_bands = trim($node->filter('.middle-info > .headliner_support')->text());

                $event['short_description'] = $supporting_bands;
            } catch (\Exception $e) {
                // do nothing
            }

            try {
                $event['description'] = $node->filter('.middle-info > p')->text();
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
                    $event['price'] = $row['price'];
                }

                if (!empty($row['time'])) {
                    $ex = explode('  ', $row['time']);

                    $dateObj = Carbon::parse($event['start_date'] . ' ' . $ex[1]);

                    $event['start_time'] = $dateObj->format('g:i A');
                    $event['end_time'] = $dateObj->copy()->addHours(3)->format('g:i A');
                }
            }

            // get date & website
            $event['start_date'] = trim($node->filter('.right-buttons > .date')->text());
            $event['start_date'] = Carbon::parse($event['start_date'])->format('Y-m-d');

            $event['website'] = rtrim($node->filter('.right-buttons > a.more')->attr('href'), '/');

            return $event;
        });

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        // validate
        $validator = $this->validate($events);

        if (!$validator) {
            return false;
        }

        // get categories
        $items = Category::all();

        $categories = [];
        foreach($items as $item) {
            $categories[$item->slug] = $item;
        }

        // fire off data into queue
        $delays = [];
        $max = 300;
        foreach($events as $event) {
            do {
                $rand = rand(15, $max);

                if (!in_array($rand, $delays)) {
                    $delays[] = $rand;

                    break;
                }
            } while (0);

            CrawlTerminalWestLink::dispatch($event, $spotify, $categories)
                ->delay(now()->addSeconds($rand));

            $this->info('Dispatching crawler for url: ' . $event['website'] . '. Delay: ' . $rand);
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
        // get crawler
        $crawler = $scraper->request('GET', $provider->scrape_url);

        // get categories
        $items = Category::all();

        $categories = [];
        foreach($items as $item) {
            $categories[$item->slug] = $item;
        }

        // get event types
        $items = EventType::all();

        $eventTypes = [];
        foreach($items as $item) {
            $eventTypes[$item->slug] = $item;
        }

        // Get the latest post in this category and display the titles
        $events = $crawler->filter('.event-container-single')->each(function ($node) use ($provider, $categories, $eventTypes) {
            $event = [
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
                'tags' => [],
                'bands' => []
            ];

            $addHeadliners = true;

            // get date for event
            $date = trim($node->filter('.event-date-single > .right')->text());

            $event['start_date'] = Carbon::parse($date)->format('Y-m-d');

            // get website
            $event['website'] = rtrim($node->filter('.event-info > .left-column > .event-buttons > a')
                ->reduce(function ($childNode) {
                    return strtolower(trim($childNode->text())) === 'info';
                })->attr('href'), '/');

            // get meta info
            // for price, time & tags
            $meta = trim($node->filter('.event-info > .left-column > .event-meta')->text());
            $meta = explode('|', $meta);

            $findToReplace = [
                'adv',
                'dos',
                'door',
                'Dos',
                'DOS',
                'ADV',
                'Adv'
            ];
            
            foreach($meta as $index => $value) {
                $value = trim($value);
                $valueLower = strtolower($value);

                if ($value === '21+' || $value === '18+') {
                    $event['tags'][] = $value;
                } elseif (strstr($valueLower, ' pm')) {
                    $dateObj = Carbon::parse($event['start_date'] . ' ' . $value);

                    $event['start_time'] = $dateObj->format('g:i A');
                    $event['end_time'] = $dateObj->copy()->addHours(3)->format('g:i A');
                } elseif (strstr($value, 'Weekend Pass')) {
                    $event['event_type_id'] = $eventTypes['festival']->id;

                    $prices = str_replace('Weekend Pass ', '', $value);

                    $ex = explode('/', $prices);

                    $price = trim(str_replace($findToReplace, '', $ex[1]));

                    if (!empty($event['price'])) {
                        $event['price'] = $event['price'] . ' - ' . $price;
                    } else {
                        $event['price'] = $price;
                    }

                    $addHeadliners = false;
                } elseif (strstr($valueLower, 'free')) {
                    $event['price'] = 'Free';
                } elseif (strstr($valueLower, 'donation')) {
                    $event['price'] = Str::title(trim($value));
                } elseif (!strstr($value, '/') && !strstr($value, ', ')) {
                    $event['price'] = Str::title(trim(str_replace($findToReplace, '', $value)));
                } else {
                    if (!strstr($value, 'Single Day')) {
                        if (strstr($value, '/')) {
                            $ex = explode('/', $value);
                        } elseif (strstr($value, ', ')) {
                            $ex = explode(', ', $value);
                        }

                        $ex[0] = trim($ex[0]);

                        if (count($ex) === 2) {
                            $ex[1] = trim($ex[1]);

                            $price_from = str_replace($findToReplace, '', $ex[0]);
                            $price_to = str_replace($findToReplace, '', $ex[1]);

                            $event['price'] = trim($price_from) . ' - ' . trim($price_to);
                        } else {
                            $event['price'] = trim(str_replace($findToReplace, '', $ex[0]));
                        }
                    } else {
                        $prices = str_replace('Single Day ', '', $value);

                        $ex = explode('/', $prices);

                        $ex[0] = trim($ex[0]);

                        $event['price'] = trim(str_replace($findToReplace, '', $ex[0]));
                    }
                }
            }

            // check for no-smoking
            try {
                $value = trim($node->filter('.event-info > .left-column > .event-meta > .no-smoking')->attr('src'));

                $event['tags'][] = 'No Smoking';
            } catch (\Exception $e) {
                // do nothing
            }

            // get band name(s)
            $headliners = $node->filter('.event-info > .left-column > .event-headliners')
                ->each(function ($childNode) {
                    return trim($childNode->text());
                });

            if (!empty($headliners)) {
                $event['name'] = $headliners[0];
            }

            if (!empty($event['name'])) {
                // parse event name
                if (strstr(strtolower($event['name']), 'festival')) {
                    $event['event_type_id'] = $eventTypes['festival']->id;
                    $event['name'] = Str::title($event['name']);

                    $addHeadliners = false;
                }

                if (strstr($event['name'], 'Weird Movie')) {
                    $event['category_id'] = $categories['arts-theatre']->id;

                    $addHeadliners = false;
                }

                if (strstr($event['name'], ' (')) {
                    $ex = explode(' (', $event['name']);

                    $event['bands'][] = trim($ex[0]);

                    $addHeadliners = false;
                }
            }

            if ($addHeadliners && !empty($headliners)) {
                foreach($headliners as $band) {
                    $event['bands'][] = $band;
                }

                if (count($headliners) > 1) {
                    $bandsAfterFirst = $headliners;

                    unset($bandsAfterFirst[0]);

                    $event['short_description'] = 'With ' . implode(', ', $bandsAfterFirst);
                }
            }

            // include lesser known bands in description
            try {
                $backupBands = trim($node->filter('.event-info > .left-column > .event-bands')->text());
                $backupBands = explode(' | ', $backupBands);

                if (!empty($backupBands)) {
                    if (!empty($event['short_description'])) {
                        $event['short_description'] .= ', ' . implode(', ', $backupBands);
                    } else {
                        $event['short_description'] = 'With ' . implode(', ', $backupBands);
                    }

                    if (empty($event['name'])) {
                        $event['name'] = $backupBands[0];
                    }

                    foreach($backupBands as $band) {
                        $event['bands'][] = $band;
                    }
                }
            } catch (\Exception $e) {
                // do nothing
            }

            // unset bands if not music show/festival
            if ($event['category_id'] !== $categories['music']->id) {
                $event['bands'] = [];
            }

            // manual price assignment, temporary
            if (empty($event['price'])) {
                if ($event['name'] === 'A Drug Called Tradition') {
                    $event['price'] = '$10';
                }

                if ($event['name'] === 'Hannah Thomas') {
                    $event['price'] = '$5';
                }
            }

            // cleanup price if needed
            if (strstr($event['price'], '-') && !strstr($event['price'], ' - ')) {
                $event['price'] = str_replace('-', ' - ', $event['price']);
            }

            // if festival, see if empty bands array
            if ($event['event_type_id'] === $eventTypes['festival']->id && empty($event['bands'])) {
                $bands = null;

                switch ($event['name']) {
                    case 'Irrelevant Music Festival 2019: Opening Reception':
                        $bands = 'Patois Counselors, Riboflavin, Shouldies, Sarah Swillum, USGS, Night Cleaner';
                    break;

                    case 'Irrelevant Music Festival 2019: Night 2':
                        $bands = 'YUKONS, Lord Narf, Upchuck, Kibi James, Harmacy, Jamee Cornelia';
                    break;

                    case 'Irrelevant Music Festival 2019: Night 3':
                        $bands = 'Fantasy Guys, Breathers, Ed Schrader\'s Music Beat, The Queendom, Dot.s, True Blossom';
                    break;

                    case 'Irrelevant Music Festival 2019: Closing Reception':
                        $bands = 'Lyonnais, DiCaprio, Warm Red, Blammo, Pure Joy';
                    break;
                }

                if (!empty($bands)) {
                    $event['bands'] = explode(', ', $bands);
                }
            }

            return $event;
        });

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        // validate
        $validator = $this->validate($events);

        if (!$validator) {
            return false;
        }

        // fire off data into queue
        foreach($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);

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
                            $url = rtrim($linkNode->filter('.tribe-events-month-event-title > a')->attr('href'), '/');
                            $title = strtolower($linkNode->filter('.tribe-events-month-event-title > a')->text());

                            if (!strstr($title, 'closed for')) {
                                $links[] = [
                                    'website' => $url,
                                    'start_date' => $startDate,
                                    'location_id' => $provider->location_id,
                                    'category_id' => $provider->location->category_id
                                ];
                            }

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
        foreach($urls as $event) {
            do {
                $rand = rand(15, $max);

                if (!in_array($rand, $delays)) {
                    $delays[] = $rand;

                    break;
                }
            } while (0);

            CrawlVenkmansLink::dispatch($event, $spotify, $categories, $eventTypes)
                ->delay(now()->addSeconds($rand));

            $this->info('Dispatching crawler for url: ' . $event['website'] . '. Delay: ' . $rand);
        }

        // save last scraped time
        $provider->last_scraped = Carbon::now();

        $provider->save();
    }

    /**
    * Provider Aisle 5
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerAisle5(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        // won't work with our scraper
        // use symfony dom crawler manually
        $html = file_get_contents($provider->scrape_url);
        $crawler = new Crawler($html);

        // let's just collect links, that's it
        $today = Carbon::now();

        $urls = $crawler->filter('.has-event')
            ->reduce(function ($node) use ($today) {
                $status = true;

                try {
                    $event = $node->filter('.one-event')->text();

                    $startDate = Carbon::parse($node->filter('.dtstart > span')->attr('title'));

                    if (!$startDate->greaterThanOrEqualTo($today)) {
                        $status = false;
                    }
                } catch (\Exception $e) {
                    $status = false;
                }

                return $status;
            })
            ->each(function ($node) use ($today, $provider) {
                $startDate = Carbon::parse(trim($node->filter('.dtstart > span')->attr('title')));

                $url = 'https://www.aisle5atl.com';
                $url .= rtrim($node->filter('.one-event > a')->eq(0)->attr('href'), '/');

                $event = [
                    'website' => $url,
                    'start_date' => $startDate->format('Y-m-d'),
                    'location_id' => $provider->location_id,
                    'category_id' => $provider->location->category_id
                ];

                return $event;
            });

        $this->info(count($urls) . ' links found that need to be crawled for provider `' . $provider->name . '`');

        // fire off data into queue
        $delays = [];
        $max = 300;
        foreach($urls as $event) {
            do {
                $rand = rand(15, $max);

                if (!in_array($rand, $delays)) {
                    $delays[] = $rand;

                    break;
                }
            } while (0);

            CrawlAisleFiveLink::dispatch($event, $spotify)
                ->delay(now()->addSeconds($rand));

            $this->info('Dispatching crawler for url: ' . $event['website'] . '. Delay: ' . $rand);
        }

        // save last scraped time
        $provider->last_scraped = Carbon::now();

        $provider->save();
    }

    /**
    * Provider Eddies Attic
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerEddiesAttic(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        // JSON feed, yay!
        // set start & end date
        $startDate = Carbon::now();
        $endDate = $startDate->copy()->addMonth(6)->format('Y-m-d');
        $startDate = $startDate->format('Y-m-d');

        // get json contents of events
        $fullUrl = $provider->scrape_url . '?fromDate=' . $startDate . '&thruDate=' . $endDate;

        $this->info($fullUrl);

        $json = file_get_contents($fullUrl);
        $json = json_decode($json, true);

        // get event types
        $items = EventType::all();

        $eventTypes = [];
        foreach($items as $item) {
            $eventTypes[$item->slug] = $item;
        }

        // parse through data
        $events = [];
        foreach($json as $data) {
            $event = [
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
                'tags' => [],
                'bands' => []
            ];

            // set basic info
            $event['name'] = $data['title'];
            $event['website'] = str_replace('?utm_medium=api', '', $data['url']);

            // get supporting bands
            if (!empty($data['supportsName'])) {
                $event['short_description'] = 'With ' . $data['supportsName'];

                // collect bands
                $ex = explode(', ', $data['supportsName']);

                foreach($ex as $row) {
                    $event['bands'][] = trim($row);
                }
            }

            // parse name
            if (strstr($event['name'], ', ')) {
                $ex = explode(', ', $event['name']);

                foreach($ex as $row) {
                    $event['bands'][] = trim($row);
                }
            } else {
                $event['bands'][] = $event['name'];
            }

            if (strstr($event['name'], 'Open Mic')) {
                $event['event_type_id'] = $eventTypes['on-going']->id;

                $event['bands'] = [];
            }

            // set date/time
            $date = Carbon::parse($data['start']);

            $event['start_date'] = $date->copy()->format('Y-m-d');
            $event['start_time'] = $date->copy()->format('g:i A');
            $event['end_time'] = $date->copy()->addHours(3)->format('g:i A');

            // get price
            $crawler = new Crawler($data['popoverContent']);

            $priceLookup = $crawler->filter('p')
                ->each(function ($node) {
                    $html = trim($node->html());

                    $ex = explode('<br>', $html);

                    if (!empty($ex[1]) && strstr($ex[1], '$')) {
                        return str_replace('</i>', '', $ex[1]);
                    } else {
                        return false;
                    }
                });

            foreach($priceLookup as $row) {
                if (!empty($row)) {
                    $event['price'] = str_replace('.00', '', trim($row));

                    break;
                }
            }

            $events[] = $event;
        }

        // validate
        $validator = $this->validate($events);

        if (!$validator) {
            return false;
        }

        $this->info(count($events) . ' links found that need to be crawled for provider `' . $provider->name . '`');

        // fire off data into queue
        foreach($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);

            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        // save last scraped time
        $provider->last_scraped = Carbon::now();

        $provider->save();
    }

    /**
    * Provider Piedmont Park
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerPiedmontPark(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        // reading from an ICS file
        $feed = new ICal;

        $feed->initUrl($provider->scrape_url);

        $feedEvents = $feed->events();

        $events = [];
        foreach($feedEvents as $row) {
            // init event data, including date
            $startDate = Carbon::parse($row->dtstart);

            $event = [
                'start_date' => $startDate->copy()->format('Y-m-d'),
                'start_time' => $startDate->copy()->format('g:i A'),
                'description' => trim($row->description),
                'website' => rtrim($row->url, '/'),
                'image_url' => !empty($row->attach) ? $row->attach : '',
                'location_id' => $provider->location_id,
                'category_id' => $provider->location->category_id,
                'user_id' => 1,
                'event_type_id' => 2,
                'price' => 'N/A'
            ];

            // set name
            $findToReplace = [
                'City permit: ',
                'City Permit: '
            ];

            $event['name'] = str_replace($findToReplace, '', trim($row->summary));

            // tags
            if (!empty($row->categories)) {
                $event['tags'] = explode(',', trim($row->categories));
            }

            // get end date/time
            if (!empty($row->dtend)) {
                $endDate = Carbon::parse($row->dtend);

                $event['end_date'] = $endDate->copy()->format('Y-m-d');
                $event['end_time'] = $endDate->copy()->format('g:i A');
            }

            // look for "Free", if it's present set the price
            // to free. otherwise, leave empty
            if (strstr(strtolower($event['name']), 'free')) {
                $event['price'] = 'Free';
            }

            if (!empty($event->row) && strstr(strtolower($event->row), 'free')) {
                $event['price'] = 'Free';
            }

            $events[] = $event;
        }

        // validate
        $validator = $this->validate($events);

        if (!$validator) {
            return false;
        }

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        // fire off data into queue
        foreach($events as $event) {
            ParseEvent::dispatch($event);

            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        // save last scraped time
        $provider->last_scraped = Carbon::now();

        $provider->save();
    }

    /**
    * Provider Variety Playhouse
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerVarietyPlayhouse(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        // get crawler
        $crawler = $scraper->request('GET', $provider->scrape_url);

        // get categories
        $items = Category::all();

        $categories = [];
        foreach($items as $item) {
            $categories[$item->slug] = $item;
        }

        // Get the latest post in this category and display the titles
        $events = $crawler->filter('article.event')->each(function ($node) use ($provider, $categories) {
            $event = [
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
                'tags' => [],
                'bands' => [],
                'meta' => []
            ];

            // get name, url & description info
            $event['name'] = trim($node->filter('.headliner > a')->text());
            $event['name'] = str_replace(' (Early Show)', '', $event['name']);
            $event['name'] = str_replace('’', "'", $event['name']);
            $event['name'] = str_replace('–', '-', $event['name']);

            $event['website'] = rtrim($node->filter('.headliner > a')->attr('href'), '/');

            // parse name to try and get bands
            $name = $event['name'];

            // look for "Presents" text
            if (strstr($name, 'Presents ')) {
                $ex = explode('Presents ', $name);

                if (strstr($name, 'Friendsgiving')) {
                    $event['bands'][] = trim($ex[0]);
                } else {
                    $event['bands'][] = trim($ex[1]);
                }
            }

            // look for comma, usually multiple bands
            if (strstr($name, ', ')) {
                $ex = explode(', ', $name);

                foreach($ex as $row) {
                    $event['bands'][] = trim($row);
                }
            }

            // look for a plus sign, like "and"
            if (strstr($name, ' + ')) {
                $ex = explode(' + ', $name);

                foreach($ex as $row) {
                    $event['bands'][] = trim($row);
                }
            }

            // look for "Tour", so we can get the band name
            if (strstr($name, 'Tour') && strstr($name, '- ')) {
                if (strstr($name, ' present ')) {
                    $ex = explode(' present ', $name);

                    $event['bands'][] = trim($ex[0]);
                } else {
                    $ex = explode('- ', $name);

                    $event['bands'][] = trim($ex[0]);
                }
            }

            if (strstr($name, 'Tour') && strstr($name, ' - ')) {
                $ex = explode(' - ', $name);

                $event['bands'][] = trim($ex[0]);
            }

            if (strstr($name, 'Tour') && strstr($name, ': ')) {
                if (strstr($name, ' Presents')) {
                    $ex = explode(' Presents', $name);
                } else {
                    $ex = explode(': ', $name);
                }

                $event['bands'][] = trim($ex[0]);
            }

            if (strstr($name, '(Feat.')) {
                $ex = explode(' (Feat.', $name);

                $event['bands'][] = trim($ex[0]);
            }

            if (strstr($name, 'Podcast')) {
                $event['category_id'] = $categories['other']->id;
            }

            if (strstr($name, 'Doug Loves')) {
                $event['category_id'] = $categories['comedy']->id;
            }

            if (strstr($name, ' Christmas- ')) {
                $ex = explode(' Christmas', $name);

                $band_name = trim($ex[0]);

                if (substr($band_name, 0, 2) === 'A ') {
                    $band_name = substr($band_name, 2);
                }

                $event['bands'][] = $band_name;
            }

            // if no band names found, add the event name
            if (!count($event['bands']) && $event['category_id'] === $provider->location->category_id) {
                $event['bands'][] = $event['name'];
            }

            // ensure unique array of bands
            if (count($event['bands'])) {
                $event['bands'] = array_unique($event['bands']);
            }

            // get supporting bands
            try {
                $supporting_bands = trim($node->filter('.headliner_support')->text());

                $event['short_description'] = $supporting_bands;

                $supporting_bands = str_replace('With ', '', $supporting_bands);

                $ex = explode(', ', $supporting_bands);

                foreach($ex as $row) {
                    $event['bands'][] = trim($row);
                }
            } catch (\Exception $e) {

            }

            // get start time & price
            // calculate end time manually
            $event['price'] = trim($node->filter('.bottom-list > ul > li.price')->text());
            $event['price'] = str_replace(' / ', ' - ', $event['price']);

            // start time
            $ex = explode('doors ', trim($node->filter('.bottom-list > ul > li')->eq(1)->text()));

            $doors_open = trim($ex[0]);

            // get start date
            $event['start_date'] = trim($node->filter('.right-buttons > .date')->text());
            $event['start_date'] = str_replace($doors_open, '', $event['start_date']);
            $event['start_date'] = Carbon::parse($event['start_date'])->format('Y-m-d');

            $start_time = explode(' show', $ex[1]);
            $start_time = str_replace('PM', ' PM', $start_time[0]);

            $dateObj = Carbon::parse($event['start_date'] . ' ' . $start_time);

            // end time
            $event['start_time'] = $dateObj->format('g:i A');
            $event['end_time'] = $dateObj->copy()->addHours(3)->format('g:i A');

            // check if event is sold out
            $tickets_text = strtolower(trim($node->filter('.right-buttons > .button')->text()));

            if (!empty($tickets_text) && $tickets_text === 'sold out') {
                $event['is_sold_out'] = true;
            }

            return $event;
        });

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        // validate
        $validator = $this->validate($events);

        if (!$validator) {
            return false;
        }

        // fire off data into queue
        foreach($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);

            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        // save last scraped time
        $provider->last_scraped = Carbon::now();

        $provider->save();
    }
}
