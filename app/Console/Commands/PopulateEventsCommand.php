<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\BrowserKit\HttpBrowser as WebScraper;
use GuzzleHttp\Client as Guzzle;
use ICal\ICal;
use SpotifyWebAPI\SpotifyWebAPI;
use Symfony\Component\DomCrawler\Crawler;

use App\Category;
use App\EventType;
use App\Provider;
use App\Jobs\ParseEvent;
use App\Jobs\ParseMusicEvent;
use App\Jobs\Locations\CrawlAisleFiveLink;
use App\Jobs\Locations\CrawlLaughingSkullLoungeLink;
use App\Jobs\Locations\CrawlTerminalWestLink;
use App\Jobs\Locations\CrawlMasqueradeLink;
use App\Jobs\Locations\CrawlVenkmansLink;

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
    * @var Carbon
    */
    public $today;

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
        $this->today = Carbon::today()->startOfDay();

        $providers = Provider::isActive()
            >where('last_scraped', '<=', $this->today->format('Y-m-d H:i:s'))
            // ->whereIn('id', [
            //     1, // Bad Earl
            //     2, // Northside Tavern
            //     3, // Terminal West
            //     4, // 529
            //     6, // Aisle 5
            //     7, // Eddies Attic
            //     8, // Piedmont Park
            //     9, // Variety Playhouse
            //     10, // Laughing Skull Lounge
            //     11, // Red Light Cafe
            //     12, // The Masquerade
            //     13, // Fox Theatre
            //     14, // The Tabernacle
            //     15, // Buckhead Theatre
            // ])
            ->orWhereNull('last_scraped')
            ->get();

        /*
        // see if checksums need to be regenerated
        $keyDate = 'provider_checksums_date';
        $skipGenerateChecksum = false;
        if (!Cache::has($keyDate)) {
            $this->regenerateChecksums($providers);

            $skipGenerateChecksum = true;
        } else {
            $cacheDate = Cache::get($keyDate);

            if (empty($cacheDate)) {
                $this->regenerateChecksums($providers);

                $skipGenerateChecksum = true;
            } else {
                $today = $this->today->copy();
                $date = Carbon::parse($cacheDate);

                if ($today->diffInDays($date) >= 3) {
                    $this->regenerateChecksums($providers);

                    $skipGenerateChecksum = true;
                }
            }
        }
        */

        // loop through providers
        $scraper = new WebScraper;
        foreach($providers as $provider) {
            $name = $provider->name;

            $find = [
                '"',
                "'"
            ];

            $providerName = str_replace($find, '', $name);
            $methodName = Str::camel('provider' . $providerName);

            if (method_exists($this, $methodName)) {
                /*
                // checksum

                $key = 'provider_' . $provider->slug;
                $keyDate = 'provider_' . $provider->slug . '_date';

                if (!$skipGenerateChecksum) {
                    $this->info('Checksum for provider `' . $name . '`');

                    if (Cache::has($key) && Cache::has($keyDate)) {
                        $cacheDate = Cache::get($keyDate);

                        $today = $this->today->copy();
                        $date = Carbon::parse($cacheDate);

                        if ($today->diffInDays($date) < 3) {
                            $this->info('Checksum validated, skipping scraper for `' . $name . '`');

                            continue;
                        }
                    }
                }*/

                // call method
                $this->info('Starting scraper for `' . $name . '`');

                $events = $this->$methodName($provider, $scraper, $spotify);

                // $this->createChecksum($provider, $events);
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
                config('services.spotify.client_id'),
                config('services.spotify.secret')
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
        // Site uses WordPress + Content Layers plugin.
        // scrape_url: https://badearl.com/show-calendar/
        // Paginated via ?sf_paged=N — crawl all pages until no more events found.
        $allNodes = [];
        $page     = 1;
        do {
            $url     = rtrim($provider->scrape_url, '/') . '/' . ($page > 1 ? '?sf_paged=' . $page : '');
            $crawler = $scraper->request('GET', $url);
            $nodes   = $crawler->filter('.cl-template--post');
            if ($nodes->count() === 0) break;
            $nodes->each(function ($node) use (&$allNodes) {
                $allNodes[] = $node;
            });
            $page++;
        } while ($page <= 10); // safety cap

        $events = array_filter(array_map(function ($node) use ($provider) {
            // website: prefer ticket link (freshtix), fall back to detail link
            $website    = '';
            $moreInfoUrl = '';
            try {
                $moreInfoUrl = trim($node->filter('.more-info-btn a')->attr('href'));
            } catch (\Exception $e) {}
            try {
                foreach ($node->filter('.show-btn a')->each(fn($a) => trim($a->attr('href'))) as $link) {
                    if (!empty($link) && $link !== $moreInfoUrl) {
                        $website = $link;
                        break;
                    }
                }
            } catch (\Exception $e) {}
            if (empty($website)) $website = $moreInfoUrl;
            if (empty($website)) return null;

            // date (required)
            $startDate = '';
            try {
                $dateText  = trim($node->filter('p.show-listing-date')->text());
                $startDate = Carbon::parse($dateText)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
            if (empty($startDate)) return null;
            if (Carbon::parse($startDate)->lt($this->today)) return null;

            // show time: prefer the <p class="show-listing-time"> that contains "show"
            $startTime = '';
            $endTime   = '';
            try {
                $times = $node->filter('p.show-listing-time')->each(fn($t) => trim($t->text()));
                $showTimeStr = '';
                foreach ($times as $t) {
                    if (stripos($t, 'show') !== false) {
                        $showTimeStr = trim(preg_replace('/\s*show\s*/i', '', $t));
                        break;
                    }
                }
                if (empty($showTimeStr) && !empty($times)) {
                    $showTimeStr = trim(preg_replace('/\s*doors?\s*/i', '', $times[0]));
                }
                if (!empty($showTimeStr)) {
                    $dateObj   = Carbon::parse($startDate . ' ' . $showTimeStr);
                    $startTime = $dateObj->format('g:i A');
                    $endTime   = $dateObj->copy()->addHours(3)->format('g:i A');
                }
            } catch (\Exception $e) {}
            if (empty($startTime)) {
                $startTime = '8:00 PM';
                $endTime   = '11:00 PM';
            }

            // sold out: .listing-sold-out-contain has text when sold out
            $isSoldOut = false;
            try {
                $soldOutEl = $node->filter('.listing-sold-out-contain');
                if ($soldOutEl->count() > 0 && !empty(trim($soldOutEl->text()))) {
                    $isSoldOut = true;
                }
            } catch (\Exception $e) {}

            // price
            $price  = '';
            $isFree = false;
            try {
                $freeEl = $node->filter('.listing-free-show-contain');
                if ($freeEl->count() > 0 && !empty(trim($freeEl->text()))) {
                    $isFree = true;
                    $price  = 'Free';
                }
            } catch (\Exception $e) {}

            if (!$isFree) {
                try {
                    $priceList = array_values(array_unique(array_filter(
                        $node->filter('p.show-listing-price')->each(
                            fn($p) => trim(preg_replace('/\s*(ADV|DOS)\s*/i', '', trim($p->text())))
                        )
                    )));
                    if (count($priceList) === 1) {
                        $price = $priceList[0];
                    } elseif (count($priceList) > 1) {
                        $price = $priceList[0] . ' - ' . end($priceList);
                    }
                } catch (\Exception $e) {}
            }
            if (empty($price)) {
                $price = $isSoldOut ? 'N/A' : '';
            }

            // headliners (required — skip event if none)
            $headliners = [];
            try {
                $headliners = array_values(array_filter(
                    $node->filter('.show-listing-headliner')->each(fn($h) => Str::title(trim($h->text())))
                ));
            } catch (\Exception $e) {}
            if (empty($headliners)) return null;

            $name  = implode(', ', $headliners);
            $bands = $headliners;

            // support acts
            try {
                foreach (array_filter(
                    $node->filter('.show-listing-support')->each(fn($s) => Str::title(trim($s->text())))
                ) as $support) {
                    if (!in_array($support, $bands)) {
                        $bands[] = $support;
                    }
                }
            } catch (\Exception $e) {}

            $isExplicit = false;
            foreach ($bands as $band) {
                if (stripos($band, 'fuck') !== false) {
                    $isExplicit = true;
                }
            }

            return [
                'name'               => $name,
                'location_id'        => $provider->location_id,
                'user_id'            => 1,
                'category_id'        => $provider->location->category_id,
                'event_type_id'      => 2,
                'start_date'         => $startDate,
                'start_time'         => $startTime,
                'end_time'           => $endTime,
                'website'            => $website,
                'price'              => $price,
                'is_sold_out'        => $isSoldOut,
                'is_family_friendly' => $provider->location->is_family_friendly,
                'is_explicit'        => $isExplicit,
                'bands'              => $bands,
                'tags'               => [],
            ];
        }, $allNodes));

        $events = array_values($events);

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        $validator = $this->validate($events);
        if (!$validator) return false;

        foreach ($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);
            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        if (count($events)) {
            $provider->last_scraped = Carbon::now();
            $provider->save();
        }

        return $events;
    }

    /**
    * Provider Northside Tavern
    *
    * Site: https://www.northsidetavern.com/
    * Custom static HTML. One div.anim block holds the weekend schedule.
    * Structure:
    *   <p class="p1"><span class="f20">February 2026</span></p>  ← month header
    *   <p class="p1 f21">Friday Feb 6th - Dustin McCook</p>      ← event line
    *   <p class="p1 f21">Saturday Feb 21st - Two Bands!</p>      ← multi-band event
    *   <p class="p1 f21">Matthew Curry 8:30pm-11:15 &amp; Cazanova's 11:30-2am</p> ← continuation
    *
    * Weekend shows (Fri/Sat): $10 at the door @ 9:30 PM
    * Weeknight shows (Sun-Thu): No Cover — resident artists rotating nightly
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerNorthsideTavern(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        $today = $this->today->copy();

        // build "Month Year" → year lookup for the next 4 months
        $monthYearMap = [];
        for ($d = $today->copy()->startOfMonth(); $d->lte($today->copy()->addMonths(3)); $d->addMonth()) {
            $monthYearMap[$d->format('F Y')] = (int) $d->format('Y');
        }

        $events = collect([]);

        // ── weekend shows (scraped from div.anim) ────────────────────────────
        try {
            $crawler = $scraper->request('GET', $provider->scrape_url);
            $animDiv = $crawler->filter('div.anim')->first();

            if ($animDiv->count()) {
                // Flatten all <p> content into individual text lines,
                // splitting on inline <br> tags so multi-event paragraphs are separated.
                $allLines = [];
                $animDiv->filter('p')->each(function ($pNode) use (&$allLines) {
                    $html  = $pNode->html();
                    $split = preg_replace('/<br\s*\/?>/i', '|||', $html);

                    foreach (explode('|||', $split) as $part) {
                        $line = trim(strip_tags(html_entity_decode($part, ENT_QUOTES | ENT_HTML5)));
                        $allLines[] = $line; // keep empty lines — used to reset continuation context
                    }
                });

                // Matches "Friday Feb 6th - Dustin McCook"
                //          "Saturday March 14th - Blair Crimmins & the Hookers"
                $eventLinePattern = '/^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)\s+(\w+\s+\d+(?:st|nd|rd|th)?)\s+-\s+(.+)$/i';

                $currentYear   = (int) $today->format('Y');
                $lastEventKey  = null; // collection key of the most-recently-added weekend event

                foreach ($allLines as $line) {
                    // ── month header ─────────────────────────────────────────
                    if (isset($monthYearMap[$line])) {
                        $currentYear  = $monthYearMap[$line];
                        $lastEventKey = null;
                        continue;
                    }

                    // blank line resets multi-band continuation context
                    if ($line === '') {
                        $lastEventKey = null;
                        continue;
                    }

                    // ── event line ───────────────────────────────────────────
                    if (preg_match($eventLinePattern, $line, $m)) {
                        $dateStr   = trim($m[2]) . ' ' . $currentYear; // e.g. "Feb 6th 2026"
                        $eventName = Str::title(trim($m[3]));

                        try {
                            $startDate = Carbon::parse($dateStr);
                        } catch (\Exception $e) {
                            $lastEventKey = null;
                            continue;
                        }

                        if ($startDate->lt($today)) {
                            $lastEventKey = null;
                            continue;
                        }

                        // Weekend show time: doors open before 9:30 PM show
                        $showStart = $startDate->copy()->setTime(21, 30, 0);
                        $showEnd   = $showStart->copy()->addHours(4);

                        $eventArr = [
                            'name'          => $eventName,
                            'location_id'   => $provider->location_id,
                            'category_id'   => $provider->location->category_id,
                            'event_type_id' => 2,
                            'user_id'       => 1,
                            'start_date'    => $showStart->format('Y-m-d'),
                            'start_time'    => $showStart->format('g:i A'),
                            'end_time'      => $showEnd->format('g:i A'),
                            'website'       => $provider->location->website,
                            'is_sold_out'   => false,
                            'price'         => '$10.00',
                            'bands'         => [$eventName],
                            'timestamp'     => (int) $showStart->format('U'),
                        ];

                        $lastEventKey = $events->count();
                        $events->push($eventArr);
                        continue;
                    }

                    // ── continuation line ────────────────────────────────────
                    // Lines that don't match the event pattern may be band/time
                    // details for the previous "Two Bands!" style event, e.g.:
                    //   "Matthew Curry 8:30pm-11:15 & Cazanova's 11:30-2am"
                    if ($lastEventKey !== null && $events->has($lastEventKey)) {
                        $prev = $events->get($lastEventKey);

                        // Only treat as continuation when the event looks multi-band
                        // or the continuation line itself contains "&"
                        $prevIsTwoBands = stripos($prev['name'], 'band') !== false
                            || stripos($prev['name'], 'act')  !== false;
                        $lineHasAmpersand = str_contains($line, '&');

                        if ($prevIsTwoBands || $lineHasAmpersand) {
                            // Split on "&" or " and "; strip inline time tokens
                            $parts = preg_split('/\s*&\s*|\s+and\s+/iu', $line);
                            $parsedBands = [];

                            foreach ($parts as $part) {
                                // Remove time ranges like "8:30pm-11:15" or "11:30-2am"
                                $bandName = preg_replace('/\s+\d+:\d+\s*(?:am|pm)?[-–]\d+:?\d*\s*(?:am|pm)?/iu', '', $part);
                                $bandName = trim($bandName, " \t\n\r-–");

                                if (!empty($bandName)) {
                                    $parsedBands[] = Str::title($bandName);
                                }
                            }

                            if (!empty($parsedBands)) {
                                $prev['bands']             = $parsedBands;
                                $prev['name']              = implode(' & ', $parsedBands);
                                $prev['short_description'] = $line;
                                $events->put($lastEventKey, $prev);
                            }
                        }

                        $lastEventKey = null; // continuation only applies once per event
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('NST: failed to scrape weekend schedule — ' . $e->getMessage());
        }

        // ── weeknight shows (recurring, 4-week lookahead) ────────────────────
        // Resident artists listed on site (northsidetavern.com footer):
        //   "Uncle Sugar · Lola's Jam · Nathalie Rose · Ronnie Bullock · Garrett Collins"
        // No Cover, Mon-Thu 10 PM–2 AM, Sun 9 PM–midnight
        $weeknightBands = [
            Carbon::SUNDAY    => ['name' => 'Uncle Sugar',     'start' => [21, 0], 'hours' => 3],
            Carbon::MONDAY    => ['name' => "Lola's Jam",      'start' => [22, 0], 'hours' => 4],
            Carbon::TUESDAY   => ['name' => 'Nathalie Rose',   'start' => [22, 0], 'hours' => 4],
            Carbon::WEDNESDAY => ['name' => 'Ronnie Bullock',  'start' => [22, 0], 'hours' => 4],
            Carbon::THURSDAY  => ['name' => 'Garrett Collins', 'start' => [22, 0], 'hours' => 4],
        ];

        foreach ($weeknightBands as $dow => $info) {
            $firstOccurrence = $today->copy()->next($dow);

            for (
                $d = $firstOccurrence->copy();
                $d->lte($today->copy()->addWeeks(4));
                $d->addWeek()
            ) {
                $showStart = $d->copy()->setTime($info['start'][0], $info['start'][1], 0);
                $showEnd   = $showStart->copy()->addHours($info['hours']);

                $events->push([
                    'name'          => $info['name'],
                    'location_id'   => $provider->location_id,
                    'category_id'   => $provider->location->category_id,
                    'event_type_id' => 2,
                    'user_id'       => 1,
                    'start_date'    => $showStart->format('Y-m-d'),
                    'start_time'    => $showStart->format('g:i A'),
                    'end_time'      => $showEnd->format('g:i A'),
                    'website'       => $provider->location->website,
                    'is_sold_out'   => false,
                    'price'         => 'Free',
                    'bands'         => [$info['name']],
                    'timestamp'     => (int) $showStart->format('U'),
                ]);
            }
        }

        $events = $events->sortBy('timestamp')->values()->all();

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        $validator = $this->validate($events);

        if (!$validator) {
            return false;
        }

        foreach ($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);

            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        $provider->last_scraped = Carbon::now();
        $provider->save();

        return $events;
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
        // AEG JSON endpoint — all event data is pre-loaded, no additional scraping needed.
        // scrape_url: https://aegwebprod.blob.core.windows.net/json/events/211/events.json
        $json = file_get_contents($provider->scrape_url);
        $data = json_decode($json, true);

        if (empty($data['events'])) {
            $this->info('No events found for provider `' . $provider->name . '`');
            return [];
        }

        $today  = $this->today->copy();
        $events = [];

        foreach ($data['events'] as $item) {
            try {
                $eventDateTime = Carbon::parse($item['eventDateTime']);
                if ($eventDateTime->lt($today)) continue;

                // Use the plain-text fields — the 'headliners'/'supporting' fields
                // contain raw HTML with <a> links; 'headlinersText'/'supportingText'
                // are the clean versions. strip_tags() is added as a safety net.
                $headlinersText = strip_tags(trim($item['title']['headlinersText'] ?? $item['title']['headliners'] ?? ''));
                if (empty($headlinersText)) continue;

                $name = $headlinersText;

                // Split bands by ", " or " | " (both separators appear in the feed)
                $bandSeparator = preg_match('/\s*\|\s*/', $headlinersText) ? ' | ' : ', ';
                $bands = array_values(array_filter(array_map('trim', preg_split('/\s*[,|]\s*/', $headlinersText))));
                if (empty($bands)) $bands = [$name];

                $supportingText   = strip_tags(trim($item['title']['supportingText'] ?? $item['title']['supporting'] ?? ''));
                $shortDescription = '';
                if (!empty($supportingText)) {
                    $shortDescription = 'With ' . $supportingText;
                    foreach (array_values(array_filter(array_map('trim', preg_split('/\s*[,|]\s*/', $supportingText)))) as $band) {
                        if (!empty($band) && !in_array($band, $bands)) {
                            $bands[] = $band;
                        }
                    }
                }

                // ticketPriceLow/High are strings like "$0" or "$25.00"
                $price     = '';
                $priceLow  = (float) str_replace('$', '', $item['ticketPriceLow']  ?? '0');
                $priceHigh = (float) str_replace('$', '', $item['ticketPriceHigh'] ?? '0');
                if ($priceLow > 0 && $priceHigh > 0 && $priceLow !== $priceHigh) {
                    $price = '$' . number_format($priceLow, 2) . ' - $' . number_format($priceHigh, 2);
                } elseif ($priceLow > 0) {
                    $price = '$' . number_format($priceLow, 2);
                }
                if (empty($price)) $price = 'N/A';

                $isSoldOut  = false;
                $statusText = strtolower(trim($item['ticketing']['status'] ?? ''));
                if ($statusText === 'sold out') $isSoldOut = true;

                $website = trim($item['ticketing']['url'] ?? '');
                if (empty($website)) $website = $provider->location->website;

                $event = [
                    'name'               => $name,
                    'location_id'        => $provider->location_id,
                    'user_id'            => 1,
                    'category_id'        => $provider->location->category_id,
                    'event_type_id'      => 2,
                    'start_date'         => $eventDateTime->format('Y-m-d'),
                    'start_time'         => $eventDateTime->format('g:i A'),
                    'end_time'           => $eventDateTime->copy()->addHours(3)->format('g:i A'),
                    'website'            => $website,
                    'price'              => $price,
                    'is_sold_out'        => $isSoldOut,
                    'is_family_friendly' => $provider->location->is_family_friendly,
                    'bands'              => $bands,
                    'tags'               => [],
                ];

                if (!empty($shortDescription)) {
                    $event['short_description'] = $shortDescription;
                }

                $events[] = $event;
            } catch (\Exception $e) {
                //
            }
        }

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        $validator = $this->validate($events);
        if (!$validator) return false;

        foreach ($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);
            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        if (count($events)) {
            $provider->last_scraped = Carbon::now();
            $provider->save();
        }

        return $events;
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
        // Site redesigned to a calendar table layout.
        // Each div.calendar-desktop has an h3 month heading and a table of days.
        $crawler = $scraper->request('GET', $provider->scrape_url);

        $events = [];

        $crawler->filter('div.calendar-desktop')->each(function ($calBlock) use (&$events, $provider) {
            // Parse month/year from heading (e.g. "February 2026")
            try {
                $monthYear = trim($calBlock->filter('h3')->text());
                $monthBase = Carbon::parse('1 ' . $monthYear);
            } catch (\Exception $e) {
                return;
            }

            $calBlock->filter('td')->each(function ($td) use (&$events, $provider, $monthBase) {
                $dayNum = 0;
                try {
                    $dayNum = (int) trim($td->filter('span.day-of-month')->text());
                } catch (\Exception $e) {
                    return;
                }
                if ($dayNum <= 0) return;

                $startDate = $monthBase->copy()->day($dayNum)->format('Y-m-d');
                if (Carbon::parse($startDate)->lt($this->today)) return;

                $td->filter('div.event-item')->each(function ($item) use (&$events, $provider, $startDate) {
                    // Headliner (required)
                    $name = '';
                    try {
                        $name = trim($item->filter('div.headliner')->text());
                    } catch (\Exception $e) {}
                    if (empty($name)) return;

                    // Support acts
                    $bands = [$name];
                    try {
                        $supportSpans = array_filter(
                            $item->filter('div.bands span')->each(fn($s) => trim($s->text())),
                            fn($t) => $t !== ',' && $t !== ', ' && !empty($t)
                        );
                        foreach ($supportSpans as $sp) {
                            if (!in_array($sp, $bands)) $bands[] = $sp;
                        }
                    } catch (\Exception $e) {}

                    // Ticket URL (fl-button), fallback to first link in item
                    $website = '';
                    try {
                        $website = trim($item->filter('a.fl-button')->attr('href'));
                    } catch (\Exception $e) {}
                    if (empty($website)) {
                        try {
                            $website = trim($item->filter('a')->attr('href'));
                        } catch (\Exception $e) {}
                    }
                    if (empty($website)) return;

                    $events[] = [
                        'name'               => $name,
                        'location_id'        => $provider->location_id,
                        'user_id'            => 1,
                        'category_id'        => $provider->location->category_id,
                        'event_type_id'      => 2,
                        'start_date'         => $startDate,
                        'start_time'         => '8:00 PM',
                        'end_time'           => '11:00 PM',
                        'website'            => $website,
                        'price'              => 'See Website',
                        'is_sold_out'        => false,
                        'is_family_friendly' => $provider->location->is_family_friendly,
                        'bands'              => array_values($bands),
                        'tags'               => [],
                    ];
                });
            });
        });

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        $validator = $this->validate($events);
        if (!$validator) return false;

        foreach ($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);
            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        if (count($events)) {
            $provider->last_scraped = Carbon::now();
            $provider->save();
        }

        return $events;
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

        return $urls;
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
        // site uses SeeTickets widget - use file_get_contents with browser headers
        // since the symfony scraper doesn't handle this site well
        // $context = stream_context_create([
        //     'http' => [
        //         'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n"
        //     ]
        // ]);

        $html = file_get_contents($provider->scrape_url);
        $crawler = new Crawler($html);

        $today = Carbon::now();
        $currentYear = $today->year;

        // collect full event data from the SeeTickets listing widget
        // individual event pages (wl.eventim.us) block scraping, so we grab everything here
        $events = $crawler->filter('.seetickets-list-event-container')
            ->reduce(function ($node) {
                try {
                    $title = trim($node->filter('.event-title a')->text());
                    return !empty($title);
                } catch (\Exception $e) {
                    return false;
                }
            })
            ->each(function ($node) use ($today, $provider, $currentYear) {
                try {
                    // get name & ticket URL
                    // title is in <p class="event-title"><a href="...">Name</a></p>
                    $titleLink = $node->filter('.event-title a');
                    $name = Str::title(trim($titleLink->text()));
                    $url  = $titleLink->attr('href');

                    // parse date - format is "Thu Feb 19"
                    $dateText  = trim($node->filter('.event-date')->text());
                    $startDate = Carbon::parse($dateText . ' ' . $currentYear);

                    // handle year rollover: if parsed date is more than 4 months in the past, it's next year
                    if ($startDate->isBefore($today) && $startDate->diffInMonths($today) > 4) {
                        $startDate->addYear();
                    }

                    // skip events already in the past
                    if ($startDate->lt($today->copy()->startOfDay())) {
                        return null;
                    }

                    // parse show time - prefer .see-showtime (show time), fall back to .see-doortime (door time)
                    // format is "8:00PM" in each span; both live inside .doortime-showtime
                    $startTime = '';
                    try {
                        $showEl = $node->filter('.see-showtime');
                        if ($showEl->count() > 0) {
                            $startTime = trim($showEl->text());
                        } else {
                            $doorEl = $node->filter('.see-doortime');
                            if ($doorEl->count() > 0) {
                                $startTime = trim($doorEl->text());
                            }
                        }
                    } catch (\Exception $e) {
                        //
                    }

                    // parse price
                    $price = '';
                    try {
                        $price = trim($node->filter('.price')->text());
                    } catch (\Exception $e) {
                        //
                    }

                    // check sold out - .button-notavailable is present when tickets are unavailable;
                    // also catch any remaining "sold out" text on the buy button
                    $isSoldOut = false;
                    try {
                        if ($node->filter('.button-notavailable')->count() > 0) {
                            $isSoldOut = true;
                        } elseif ($node->filter('.button-gettickets')->count() > 0) {
                            $buyBtnText = strtolower(trim($node->filter('.button-gettickets')->text()));
                            if ($buyBtnText === 'not available' || $buyBtnText === 'sold out') {
                                $isSoldOut = true;
                            }
                        }
                    } catch (\Exception $e) {
                        //
                    }

                    // headliners: .headliners contains the main act name (mirrors the title);
                    // .supporting-talent contains supporting acts
                    $bands = [];
                    try {
                        $headlinersText = trim($node->filter('.headliners')->text());
                        if (!empty($headlinersText) && strtolower($headlinersText) !== strtolower($name)) {
                            foreach (explode(', ', $headlinersText) as $band) {
                                $band = Str::title(trim($band));
                                if (!empty($band)) {
                                    $bands[] = $band;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        //
                    }

                    try {
                        $supportingText = trim($node->filter('.supporting-talent')->text());
                        if (!empty($supportingText)) {
                            foreach (explode(', ', $supportingText) as $band) {
                                $band = Str::title(trim($band));
                                if (!empty($band) && !in_array($band, $bands)) {
                                    $bands[] = $band;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        //
                    }

                    return [
                        'name'        => $name,
                        'website'     => $url,
                        'start_date'  => $startDate->format('Y-m-d'),
                        'start_time'  => $startTime,
                        'price'       => $price,
                        'is_sold_out' => $isSoldOut,
                        'bands'       => $bands,
                        'location_id' => $provider->location_id,
                        'category_id' => $provider->location->category_id
                    ];
                } catch (\Exception $e) {
                    return null;
                }
            });

        // filter out nulls from skipped/failed events
        $events = array_values(array_filter($events));

        $this->info(count($events) . ' links found that need to be crawled for provider `' . $provider->name . '`');

        // fire off data into queue
        $delays = [];
        $max = 60;
        foreach ($events as $event) {
            do {
                $rand = rand(10, $max);

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

        return $events;
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
        // Eddie's Attic homepage renders events server-side as div.listing.plotCard elements.
        // The /calendar page uses a Dice.fm JS widget that can't be scraped statically.
        // scrape_url should point to the homepage: https://eddiesattic.com/
        $crawler = $scraper->request('GET', $provider->scrape_url);

        $events = $crawler->filter('div.listing.plotCard')->each(function ($node) use ($provider) {
            // Name (required)
            $name = '';
            try {
                $name = trim($node->filter('div.listing__title h3')->text());
            } catch (\Exception $e) {}
            if (empty($name)) return null;

            // Date/time: "Thu, Feb 19 7pm" or "Thu, Feb 19 7:30pm"
            $startDate = '';
            $startTime = '7:00 PM';
            $endTime   = '10:00 PM';
            try {
                $dtText = trim($node->filter('div.listingDateTime span')->text());
                if (preg_match('/([A-Za-z]+,\s+[A-Za-z]+\s+\d+)\s+(\d+(?::\d+)?\s*(?:am|pm))/i', $dtText, $m)) {
                    $dateObj = Carbon::parse($m[1] . ' ' . Carbon::now()->year . ' ' . $m[2]);
                    if ($dateObj->lt($this->today)) {
                        $dateObj->addYear();
                    }
                    $startDate = $dateObj->format('Y-m-d');
                    $startTime = $dateObj->format('g:i A');
                    $endTime   = $dateObj->copy()->addHours(3)->format('g:i A');
                }
            } catch (\Exception $e) {}
            if (empty($startDate)) return null;

            // Ticket URL (Dice.fm), fall back to detail page link
            $website = '';
            try {
                $website = trim($node->filter('a.JS--buyTicketsButton')->attr('href'));
            } catch (\Exception $e) {}
            if (empty($website)) {
                try {
                    $website = trim($node->filter('a.listing__titleLink')->attr('href'));
                } catch (\Exception $e) {}
            }
            if (empty($website)) return null;

            // Bands: split on comma for multi-act names, otherwise use as-is
            $bands = [];
            if (strpos($name, ', ') !== false) {
                foreach (explode(', ', $name) as $b) {
                    $bands[] = Str::title(trim($b));
                }
            } else {
                $bands[] = $name;
            }

            return [
                'name'               => $name,
                'location_id'        => $provider->location_id,
                'user_id'            => 1,
                'category_id'        => $provider->location->category_id,
                'event_type_id'      => 2,
                'start_date'         => $startDate,
                'start_time'         => $startTime,
                'end_time'           => $endTime,
                'website'            => $website,
                'price'              => 'See Website',
                'is_sold_out'        => false,
                'is_family_friendly' => $provider->location->is_family_friendly,
                'bands'              => $bands,
                'tags'               => [],
            ];
        });

        $events = array_values(array_filter($events));

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        $validator = $this->validate($events);
        if (!$validator) return false;

        foreach ($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);
            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        if (count($events)) {
            $provider->last_scraped = Carbon::now();
            $provider->save();
        }

        return $events;
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
      foreach($feedEvents as $i => $row) {
        // init event data, including date
        $startDate = Carbon::parse($row->dtstart);

        $event = [
          'start_date' => $startDate->copy()->format('Y-m-d'),
          'start_time' => $startDate->copy()->format('g:i A'),
          'description' => '',
          'website' => rtrim($row->url, '/'),
          'location_id' => $provider->location_id,
          'category_id' => $provider->location->category_id,
          'user_id' => 1,
          'event_type_id' => 2,
          'price' => 'N/A'
        ];

        $info = trim($row->description);

        // $event['description'] = trim(preg_replace('/\s+/', ' ', $row->description));
        // $event['description'] = trim(preg_replace('/\s+/', ' ', substr($info, 0, 5))) . substr($info, 5, -5);
        // $event['description'] = trim(preg_replace('/\s+/', ' ', substr($info, -5, -10)));
        $event['description'] = trim($row->description);

        // set name
        $findToReplace = [
          'City permit: ',
          'City Permit: '
        ];

        $event['name'] = str_replace($findToReplace, '', trim($row->summary));

        // tags (CATEGORIES field may not exist in current ICS feed)
        try {
            if (!empty($row->categories)) {
                $event['tags'] = explode(',', trim($row->categories));
            }
        } catch (\Exception $e) {}

        // get end date/time
        if (!empty($row->dtend)) {
          $endDate = Carbon::parse($row->dtend);

          $event['end_date'] = $endDate->copy()->format('Y-m-d');
          $event['end_time'] = $endDate->copy()->format('g:i A');

          if ($event['start_date'] === $event['end_date']) {
            $event['end_date'] = null;
          }

          if ($event['start_time'] === $event['end_time']) {
            $event['end_time'] = null;
          }
        }

        // look for "Free", if it's present set the price
        // to free. otherwise, leave empty
        if (strstr(strtolower($event['name']), 'free')) {
          $event['price'] = 'Free';
        }

        if (!empty($event['description']) && strstr(strtolower($event['description']), 'free')) {
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

      return $events;
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
        // scrape_url is the AEG JSON events feed for Variety Playhouse (venue ID 214).
        // e.g. https://aegwebprod.blob.core.windows.net/json/events/214/events.json
        $json = file_get_contents($provider->scrape_url);
        $data = json_decode($json, true);

        if (empty($data['events'])) {
            $this->error('No events found in JSON for ' . $provider->name);
            return false;
        }

        $events = [];
        foreach ($data['events'] as $ev) {
            // Date/time from ISO field
            $startDate = '';
            $startTime = '8:00 PM';
            $endTime   = '11:00 PM';
            try {
                $dtISO = $ev['eventDateTimeISO'] ?? $ev['eventDateTime'] ?? null;
                if ($dtISO) {
                    $dateObj   = Carbon::parse($dtISO);
                    $startDate = $dateObj->format('Y-m-d');
                    $startTime = $dateObj->format('g:i A');
                    $endTime   = $dateObj->copy()->addHours(3)->format('g:i A');
                }
            } catch (\Exception $e) {}
            if (empty($startDate)) continue;

            // Skip past events
            if (Carbon::parse($startDate)->lt($this->today)) continue;

            // Name: prefer headliner association name, fall back to headlinersText
            $name = '';
            try {
                if (!empty($ev['associations']['headliners'][0]['name'])) {
                    $name = trim($ev['associations']['headliners'][0]['name']);
                }
            } catch (\Exception $e) {}
            if (empty($name)) {
                $name = trim($ev['title']['headlinersText'] ?? $ev['title']['eventTitleText'] ?? '');
            }
            if (empty($name)) continue;

            // Bands array
            $bands = [$name];

            // Support acts from title.supportingText
            $supportText = trim($ev['title']['supportingText'] ?? '');
            if (!empty($supportText)) {
                foreach (explode(', ', $supportText) as $sp) {
                    $sp = trim($sp);
                    if (!empty($sp) && !in_array($sp, $bands)) $bands[] = $sp;
                }
            }
            // Support acts from associations.supportingActs
            try {
                foreach ($ev['associations']['supportingActs'] as $sa) {
                    $saName = trim($sa['name'] ?? '');
                    if (!empty($saName) && !in_array($saName, $bands)) $bands[] = $saName;
                }
            } catch (\Exception $e) {}

            // Ticket URL
            $website = trim($ev['ticketing']['url'] ?? '');

            // Sold out
            $isSoldOut    = false;
            $ticketStatus = strtolower(trim($ev['ticketing']['status'] ?? ''));
            if (strpos($ticketStatus, 'sold') !== false) {
                $isSoldOut = true;
            }

            // Price: JSON price fields are unreliable ($0), use 'See Website'
            $price = $isSoldOut ? 'N/A' : 'See Website';

            // Age restriction tag
            $tags = [];
            $age  = trim($ev['age'] ?? '');
            if (!empty($age) && strtolower($age) !== 'all ages') {
                $tags[] = $age;
            }

            $events[] = [
                'name'               => $name,
                'location_id'        => $provider->location_id,
                'user_id'            => 1,
                'category_id'        => $provider->location->category_id,
                'event_type_id'      => 2,
                'start_date'         => $startDate,
                'start_time'         => $startTime,
                'end_time'           => $endTime,
                'website'            => $website,
                'price'              => $price,
                'is_sold_out'        => $isSoldOut,
                'is_family_friendly' => $provider->location->is_family_friendly,
                'bands'              => array_values(array_unique($bands)),
                'tags'               => $tags,
            ];
        }

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        $validator = $this->validate($events);
        if (!$validator) return false;

        foreach ($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);
            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        if (count($events)) {
            $provider->last_scraped = Carbon::now();
            $provider->save();
        }

        return $events;
    }

    /**
    * Create Checksum
    *
    * @param Provider $provider
    * @param array    $data
    *
    * @return void
    */
    private function createChecksum(Provider $provider, array $data)
    {
        // get hash
        $hash = md5(array_map('json_decode', $data));

        // store cache
        $key = 'provider_' . $provider->slug;
        $keyDate = 'provider_' . $provider->slug . '_date';

        Cache::put($key, $hash);
        Cache::put($keyDate, Carbon::now()->format('Y-m-d H:i:s'));
    }

    /**
    * Regenerate Checksums
    *
    * @param Collection $providers
    *
    * @return void
    */
    private function regenerateChecksums($providers)
    {
        $this->info('regenerateChecksums -> start');

        // regenerate checksums
        foreach($providers as $provider) {
            $events = [];
            foreach($provider->location->events as $event) {
                $events[] = $event->toSearchableArray();
            }

            $this->createChecksum($provider, $events);
        }

        // set cache
        $keyDate = 'provider_checksums_date';
        $date = Carbon::now()->format('Y-m-d H:i:s');

        Cache::put($keyDate, $date);

        $this->info('regenerateChecksums -> end');
    }

    /**
    * Provider Laughing Skull Lounge
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerLaughingSkullLounge(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        // Laughing Skull Lounge uses The Events Calendar (Tribe) WordPress plugin.
        // scrape_url should be https://laughingskulllounge.com/events/
        // (The old Freshtix page is now JS-rendered and returns no events statically.)
        $crawler = $scraper->request('GET', $provider->scrape_url);

        $events = $crawler->filter('.tribe-events-calendar-list__event-row')->each(function ($row) use ($provider) {
            // ISO date from <time datetime="2026-02-19">
            $startDate = '';
            try {
                $startDate = trim($row->filter('time.tribe-events-calendar-list__event-date-tag-datetime')->attr('datetime'));
            } catch (\Exception $e) {}
            if (empty($startDate)) return null;
            if (Carbon::parse($startDate)->lt($this->today)) return null;

            // Event title anchor
            $titleAnchor = null;
            try {
                $titleAnchor = $row->filter('h3.tribe-events-calendar-list__event-title a');
            } catch (\Exception $e) {
                return null;
            }
            if (!$titleAnchor || $titleAnchor->count() === 0) return null;

            // Full text includes span.cart-title-date — strip it to get clean name
            $fullText = trim($titleAnchor->text());
            $spanText = '';
            try {
                $spanText = trim($titleAnchor->filter('span.cart-title-date')->text());
            } catch (\Exception $e) {}
            $name = trim(str_replace($spanText, '', $fullText));
            // Also strip the tribe-event-time span if present
            try {
                $timeSpanText = trim($titleAnchor->filter('span.tribe-event-time')->text());
                if (!empty($timeSpanText)) {
                    $name = trim(str_replace($timeSpanText, '', $name));
                }
            } catch (\Exception $e) {}
            if (empty($name)) return null;

            // Time from span.cart-title-date: "Thursday, February 19, 8:00 pm"
            $startTime = '8:00 PM';
            $endTime   = '10:00 PM';
            if (!empty($spanText) && preg_match('/(\d+:\d+\s*(?:am|pm))/i', $spanText, $m)) {
                try {
                    $dateObj   = Carbon::parse($startDate . ' ' . $m[1]);
                    $startTime = $dateObj->format('g:i A');
                    $endTime   = $dateObj->copy()->addHours(2)->format('g:i A');
                } catch (\Exception $e) {}
            }

            // Website: ticket/event link (strip #fragment)
            $website = '';
            try {
                $website = trim($row->filter('a.tribe-events-c-small-cta__link')->attr('href'));
                $website = preg_replace('/#.*$/', '', $website);
            } catch (\Exception $e) {}
            if (empty($website)) {
                try {
                    $website = trim($titleAnchor->attr('href'));
                } catch (\Exception $e) {}
            }
            if (empty($website)) return null;

            // Price
            $price = 'See Website';
            try {
                $priceText = trim($row->filter('span.tribe-events-c-small-cta__price')->text());
                $priceText = html_entity_decode($priceText, ENT_HTML5, 'UTF-8');
                $priceText = str_replace(['–', '—'], '-', $priceText);
                $priceText = preg_replace('/\.00/', '', $priceText);
                if (!empty($priceText)) $price = $priceText;
            } catch (\Exception $e) {}

            return [
                'name'               => $name,
                'location_id'        => $provider->location_id,
                'user_id'            => 1,
                'category_id'        => $provider->location->category_id,
                'event_type_id'      => 2,
                'start_date'         => $startDate,
                'start_time'         => $startTime,
                'end_time'           => $endTime,
                'website'            => $website,
                'price'              => $price,
                'is_sold_out'        => false,
                'is_family_friendly' => $provider->location->is_family_friendly,
                'bands'              => [],
                'tags'               => [],
            ];
        });

        $events = array_values(array_filter($events));

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        $validator = $this->validate($events);
        if (!$validator) return false;

        foreach ($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);
            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        if (count($events)) {
            $provider->last_scraped = Carbon::now();
            $provider->save();
        }

        return $events;
    }

    /**
    * Provider Red Light Cafe
    *
    * Squarespace-powered site. Events are fetched from the JSON calendar API
    * (same collectionId used historically). Price, ticket URL, sold-out flag,
    * and short description are parsed from the excerpt HTML automatically —
    * no interactive prompts.
    *
    * Excerpt HTML structure (typical):
    *   <p><strong>SAT • FEB 28 • 8 PM<br></strong>
    *      $15 Adv – $20 Doors<br>Doors @ 7:30 PM</p>
    *   <p><a href="https://eventbrite.com/..."><strong>BUY TICKETS</strong></a></p>
    *   <p><em>Advance ticket sales end one hour prior...</em></p>
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerRedLightCafe(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        $client          = new Guzzle;
        $locationWebsite = $provider->location->website;
        $today           = Carbon::now('America/New_York');

        // fetch events for the next 4 months (current + 3 ahead)
        $months = [];
        for ($d = $today->copy()->startOfMonth(); $d->lte($today->copy()->addMonths(3)); $d->addMonth()) {
            $months[] = $d->copy();
        }

        // load categories keyed by slug
        $categories = Category::all()->keyBy('slug');

        $musicCategoryId = $categories['music']->id ?? $provider->location->category_id;

        $events  = [];
        $seenIds = [];

        foreach ($months as $month) {
            $url = $provider->scrape_url
                . '?month=' . $month->format('m-Y')
                . '&collectionId=516db1d0e4b0633e7b269026';

            try {
                $response = $client->get($url, ['timeout' => 15]);
                $data     = json_decode((string) $response->getBody(), true);
            } catch (\Exception $e) {
                $this->error('RLC: failed to fetch ' . $month->format('F Y') . ' — ' . $e->getMessage());
                continue;
            }

            if (empty($data) || !is_array($data)) {
                continue;
            }

            foreach ($data as $item) {
                try {
                    $itemId = $item['id'] ?? null;

                    // deduplicate across month boundaries
                    if ($itemId && in_array($itemId, $seenIds, true)) {
                        continue;
                    }

                    // ── dates ────────────────────────────────────────────────
                    $startDate = Carbon::createFromTimestampMs($item['startDate'])
                        ->setTimezone('America/New_York');

                    if ($startDate->lt($today->copy()->startOfDay())) {
                        continue;
                    }

                    $endDate = null;
                    if (!empty($item['endDate'])) {
                        $endDate = Carbon::createFromTimestampMs($item['endDate'])
                            ->setTimezone('America/New_York');
                    }


                    // ── name ─────────────────────────────────────────────────
                    $name = html_entity_decode(trim($item['title'] ?? ''), ENT_QUOTES | ENT_HTML5);
                    $name = str_replace(['&amp;', '  '], ['&', ' '], $name);

                    if (empty($name)) {
                        continue;
                    }

                    // ── parse excerpt HTML ───────────────────────────────────
                    $excerptHtml     = $item['excerpt'] ?? '';
                    $excerptCrawler  = new Crawler('<div>' . $excerptHtml . '</div>');
                    $excerptPlain    = trim(strip_tags($excerptHtml));

                    // price — in first <p>, after the bold date/time header
                    // structure: <strong>...<br></strong>PRICE<br>Doors @ ...
                    $price     = '';
                    $isSoldOut = false;

                    if (stripos($excerptPlain, 'sold out') !== false) {
                        $isSoldOut = true;
                    }

                    try {
                        $firstP     = $excerptCrawler->filter('p')->first();
                        $firstPHtml = $firstP->count() ? $firstP->html() : '';

                        if (!empty($firstPHtml)) {
                            // split on closing </strong> to isolate the price line
                            $afterStrong = preg_split('/<\/strong>/i', $firstPHtml, 2);

                            if (!empty($afterStrong[1])) {
                                // first segment before any <br> is the price
                                $priceLine = trim(strip_tags(explode('<br>', $afterStrong[1])[0]));

                                // strip label prefixes like "GA: " or "VIP 1st Row: "
                                $priceLine = preg_replace('/^[^$]*:\s*/u', '', $priceLine);
                                $priceLine = trim($priceLine);

                                if (!empty($priceLine)) {
                                    $price = $priceLine;
                                }
                            }

                            // fallback: look for NO COVER in the whole paragraph
                            if (empty($price) && preg_match('/no\s+cover/i', strip_tags($firstPHtml))) {
                                $price = 'Free';
                            }
                        }
                    } catch (\Exception $e) {
                        //
                    }

                    // second fallback: any $ amount in the plain excerpt text
                    if (empty($price) && preg_match('/\$\d+(?:\.\d{2})?/u', $excerptPlain, $pm)) {
                        // grab from the $ to the next newline or end
                        if (preg_match('/(\$[\d.]+[^\n<]*)/u', $excerptPlain, $pm2)) {
                            $price = trim($pm2[1]);
                        }
                    }

                    if (empty($price)) {
                        $price = 'N/A';
                    }

                    // normalize en-dashes in price (e.g. "–" → "–")
                    $price = preg_replace('/\s*[–—]\s*/u', ' – ', $price);

                    // ── ticket URL ───────────────────────────────────────────
                    // prefer eventbrite link in the excerpt, fall back to venue page
                    $website = $locationWebsite . ($item['fullUrl'] ?? '');

                    try {
                        $excerptCrawler->filter('a')->each(function ($node) use (&$website, &$isSoldOut) {
                            $href     = trim($node->attr('href') ?? '');
                            $linkText = strtolower(trim($node->text()));

                            if (empty($href)) {
                                return;
                            }

                            if (str_contains($linkText, 'sold out')) {
                                $isSoldOut = true;
                                return;
                            }

                            // any outbound ticket link qualifies
                            if (
                                str_contains($linkText, 'ticket')  ||
                                str_contains($href, 'eventbrite') ||
                                str_contains($href, 'freshtix')   ||
                                str_contains($href, 'ticketweb')
                            ) {
                                $website = $href;
                            }
                        });
                    } catch (\Exception $e) {
                        //
                    }

                    // ── short description ────────────────────────────────────
                    // use non-price, non-ticket paragraphs as description
                    $shortDescription = '';

                    try {
                        $excerptCrawler->filter('p')->each(function ($node) use (&$shortDescription) {
                            $text = trim($node->text());

                            if (
                                empty($text)                             ||
                                str_contains($text, '$')                 ||
                                stripos($text, 'ticket') !== false       ||
                                stripos($text, 'Advance ticket') !== false ||
                                stripos($text, 'No refund') !== false
                            ) {
                                return;
                            }

                            if (empty($shortDescription)) {
                                $shortDescription = $text;
                            }
                        });
                    } catch (\Exception $e) {
                        //
                    }

                    // ── category detection ───────────────────────────────────
                    $nameLower  = strtolower($name);
                    $categoryId = $provider->location->category_id;

                    $comedyTerms = ['comedy', 'stand-up', 'stand up', 'improv'];
                    $artsTerms   = ['cabaret', 'songwriters', 'showcase', 'drag'];
                    $otherTerms  = ['nerdtastic', 'open mic', 'poker tournament', 'trivia', 'movie night', 'film'];

                    foreach ($comedyTerms as $term) {
                        if (str_contains($nameLower, $term)) {
                            $categoryId = $categories['comedy']->id ?? $categoryId;
                            break;
                        }
                    }

                    if ($categoryId === $provider->location->category_id) {
                        foreach ($artsTerms as $term) {
                            if (str_contains($nameLower, $term)) {
                                $categoryId = $categories['arts-theatre']->id ?? $categoryId;
                                break;
                            }
                        }
                    }

                    if ($categoryId === $provider->location->category_id) {
                        foreach ($otherTerms as $term) {
                            if (str_contains($nameLower, $term)) {
                                $categoryId = $categories['other']->id ?? $categoryId;
                                break;
                            }
                        }
                    }

                    // ── band extraction (music events only) ──────────────────
                    // Split on common multi-act delimiters in the event title.
                    // If no delimiter is found the title itself is the band name.
                    $bands = [];

                    if ($categoryId === $musicCategoryId) {
                        // strip trailing parenthetical notes, e.g. "(Album Release)"
                        $cleanTitle = preg_replace('/\s*\([^)]*\)\s*$/', '', $name);

                        // split on: comma, ampersand, " + ", " w/ ", " with ", " and "
                        $parts = preg_split(
                            '/,\s*|\s*&\s*|\s+\+\s+|\s+[Ww]\/\s*|\s+[Ww]ith\s+|\s+[Aa]nd\s+/',
                            $cleanTitle
                        );

                        $bands = array_values(array_filter(array_map('trim', $parts)));

                        // guard: if splitting produced only one entry that looks like a full
                        // event description (e.g. "Jazz Jam Night") just keep it as the band
                        if (empty($bands)) {
                            $bands = [trim($name)];
                        }
                    }

                    // ── build final event array ──────────────────────────────
                    $endDateStr = null;
                    $endTimeStr = $startDate->copy()->addHours(3)->format('g:i A');

                    if ($endDate) {
                        $endTimeStr = $endDate->format('g:i A');

                        if ($endDate->format('Y-m-d') !== $startDate->format('Y-m-d')) {
                            $endDateStr = $endDate->format('Y-m-d');
                        }
                    }

                    $event = [
                        'name'               => $name,
                        'location_id'        => $provider->location_id,
                        'user_id'            => 1,
                        'category_id'        => $categoryId,
                        'event_type_id'      => 2,
                        'start_date'         => $startDate->format('Y-m-d'),
                        'start_time'         => $startDate->format('g:i A'),
                        'end_date'           => $endDateStr,
                        'end_time'           => $endTimeStr,
                        'website'            => $website,
                        'price'              => $price,
                        'is_sold_out'        => $isSoldOut,
                        'is_family_friendly' => false,
                        'short_description'  => $shortDescription,
                        'bands'              => $bands,
                        'tags'               => [],
                    ];

                    $validator = $this->validate([$event]);

                    if (!$validator) {
                        $this->error('RLC: validation failed for `' . $name . '`');
                        continue;
                    }

                    $events[] = $event;

                    if ($itemId) {
                        $seenIds[] = $itemId;
                    }

                    ParseMusicEvent::dispatch($event, $spotify);

                    $this->info('RLC: dispatched job for `' . $name . '`');
                } catch (\Exception $e) {
                    $this->error('RLC: exception on item — ' . $e->getMessage());
                }
            }
        }

        $this->info(count($events) . ' events parsed for `' . $provider->name . '`');

        if (count($events)) {
            $provider->last_scraped = Carbon::now();
            $provider->save();
        }

        return $events;
    }

    /**
    * Provider The Masquerade
    *
    * Scrapes the event listing page for event details, then dispatches
    * CrawlMasqueradeLink jobs to fetch individual event pages for pricing.
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerTheMasquerade(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        $client = new Guzzle([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ],
        ]);

        $today    = Carbon::now('America/New_York');
        $response = $client->get($provider->scrape_url);
        $html     = (string) $response->getBody();
        $crawler  = new Crawler($html);

        // collect events from listing page
        // "Other Location" events have no span.js-listVenue — skip them
        $events = $crawler->filter('article.event')->each(function ($node) use ($today, $provider) {
            try {
                // skip "Other Location" events — they have no span.js-listVenue
                $venueNode = $node->filter('span.js-listVenue');
                if ($venueNode->count() === 0) {
                    return null;
                }

                // parse date from content attribute: "February 20, 2026 6:30 pm"
                $dateNode = $node->filter('div.eventStartDate');
                if ($dateNode->count() === 0) {
                    return null;
                }
                $startDate = Carbon::parse($dateNode->attr('content'), 'America/New_York');

                // skip past events
                if ($startDate->lt($today->copy()->startOfDay())) {
                    return null;
                }

                // event name
                $nameNode = $node->filter('h2.eventHeader__title');
                if ($nameNode->count() === 0) {
                    return null;
                }
                $name = trim($nameNode->text());
                if (empty($name)) {
                    return null;
                }

                // support acts (h4.eventHeader__support — absent when no support)
                $bands       = [];
                $supportNode = $node->filter('h4.eventHeader__support');
                if ($supportNode->count() > 0) {
                    $supportText = trim($supportNode->text());
                    if (!empty($supportText)) {
                        $parts = preg_split('/,\s*|&amp;|&|\s+\+\s+|\s+w\/\s+|\s+with\s+|\s+and\s+/i', $supportText);
                        foreach ($parts as $part) {
                            $part = trim($part);
                            if (!empty($part) && strtolower($part) !== strtolower($name)) {
                                $bands[] = $part;
                            }
                        }
                    }
                }

                // doors time from listing .time-show: "Doors 6:30 pm / All Ages"
                $startTime    = '';
                $timeShowNode = $node->filter('.time-show');
                if ($timeShowNode->count() > 0) {
                    $timeText = trim($timeShowNode->text());
                    if (preg_match('/Doors\s+([\d:]+\s*[apm]+)/i', $timeText, $m)) {
                        $startTime = $m[1];
                    }
                }

                // ticket link (btn-purple) and sold-out detection
                $website    = '';
                $isSoldOut  = false;
                $ticketNode = $node->filter('a.btn-purple');
                if ($ticketNode->count() > 0) {
                    $btnText   = strtolower(trim($ticketNode->text()));
                    $isSoldOut = (strpos($btnText, 'sold out') !== false);
                    $website   = $ticketNode->attr('href') ?? '';
                }

                // detail page URL ("More Info" btn-grey link)
                $detailUrl  = '';
                $detailNode = $node->filter('a.btn-grey');
                if ($detailNode->count() > 0) {
                    $detailUrl = $detailNode->attr('href') ?? '';
                }

                // fall back to detail page if no external ticket link
                if (empty($website)) {
                    $website = $detailUrl;
                }

                return [
                    'name'        => $name,
                    'start_date'  => $startDate->format('Y-m-d'),
                    'start_time'  => $startTime,
                    'price'       => '',    // extracted from detail page in job
                    'website'     => $website,
                    'detail_url'  => $detailUrl,
                    'is_sold_out' => $isSoldOut,
                    'bands'       => $bands,
                    'location_id' => $provider->location_id,
                    'category_id' => $provider->location->category_id,
                ];
            } catch (\Exception $e) {
                return null;
            }
        });

        // filter out nulls from skipped / failed events
        $events = array_values(array_filter($events));

        $this->info(count($events) . ' Masquerade events found to crawl');

        // dispatch CrawlMasqueradeLink jobs with staggered delays
        foreach ($events as $key => $event) {
            $delay = ($key + 1) * rand(5, 10);

            CrawlMasqueradeLink::dispatch($event, $spotify)
                ->delay(now()->addSeconds($delay));

            $this->info('Masquerade: dispatched job for `' . $event['name'] . '`. Delay: ' . $delay . 's');
        }

        $provider->last_scraped = Carbon::now();
        $provider->save();

        return $events;
    }

    /**
    * Provider Fox Theatre
    *
    * Scrapes the event listing page HTML. Individual event detail pages block
    * server-side requests (HTTP 406), so all data is extracted from the listing.
    * Price is not published on the listing page and is set to 'See Website'.
    *
    * Note: the listing page serves only the first ~12 events server-side;
    * additional events are loaded via AJAX (also inaccessible server-side).
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerFoxTheatre(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        $client = new Guzzle([
            'timeout' => 30,
            'headers' => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ],
        ]);

        $today    = Carbon::now('America/New_York');
        $response = $client->get($provider->scrape_url);
        $html     = (string) $response->getBody();
        $crawler  = new Crawler($html);

        $events = [];

        $crawler->filter('div.eventItem')->each(function ($node) use ($today, $provider, $spotify, &$events) {
            try {
                // event name
                $titleLink = $node->filter('h3.title a');
                if ($titleLink->count() === 0) {
                    return;
                }
                $name = trim($titleLink->text());
                if (empty($name)) {
                    return;
                }

                // detail page URL
                $detailUrl = $titleLink->attr('href') ?? '';

                // parse date — two formats:
                //   single:     "Feb 21, 2026"
                //   same-month range: "Apr  7 - 12, 2026"
                //   cross-month range: "Feb 28 - Mar 15, 2026"
                // for ranges we always use the first (start) date
                $dateNode = $node->filter('.date');
                if ($dateNode->count() === 0) {
                    return;
                }
                $dateText = trim(preg_replace('/\s+/', ' ', $dateNode->text()));

                if (strpos($dateText, ' - ') !== false) {
                    // extract year from the end of the full string
                    preg_match('/(\d{4})\s*$/', $dateText, $yearMatch);
                    $year      = $yearMatch[1] ?? $today->year;
                    $firstPart = trim(explode(' - ', $dateText)[0]);
                    $dateText  = $firstPart . ', ' . $year;
                }

                try {
                    $startDate = Carbon::parse($dateText, 'America/New_York');
                } catch (\Exception $e) {
                    $this->error('Fox Theatre: cannot parse date `' . $dateText . '`');
                    return;
                }

                // skip past events
                if ($startDate->lt($today->copy()->startOfDay())) {
                    return;
                }

                // ticket link and sold-out detection
                $website   = $detailUrl;
                $isSoldOut = false;
                $ticketNode = $node->filter('a.tickets');
                if ($ticketNode->count() > 0) {
                    $btnText = strtolower(trim($ticketNode->text()));
                    if (strpos($btnText, 'sold out') !== false) {
                        $isSoldOut = true;
                    }
                    $ticketHref = $ticketNode->attr('href') ?? '';
                    if (!empty($ticketHref)) {
                        $website = $ticketHref;
                    }
                }

                // default start time — time is not present on the listing page
                $startTimeObj = Carbon::parse($startDate->format('Y-m-d') . ' 20:00:00');
                $startTime    = $startTimeObj->format('g:i A');
                $endTime      = $startTimeObj->copy()->addHours(3)->format('g:i A');

                $event = [
                    'name'               => $name,
                    'location_id'        => $provider->location_id,
                    'user_id'            => 1,
                    'category_id'        => $provider->location->category_id,
                    'event_type_id'      => 2,
                    'start_date'         => $startDate->format('Y-m-d'),
                    'price'              => 'See Website',
                    'start_time'         => $startTime,
                    'end_time'           => $endTime,
                    'website'            => $website,
                    'is_sold_out'        => $isSoldOut,
                    'is_family_friendly' => false,
                    'tags'               => [],
                    'bands'              => [$name],
                ];

                $validator = $this->validate([$event]);
                if (!$validator) {
                    $this->error('Fox Theatre: validation failed for `' . $name . '`');
                    return;
                }

                $events[] = $event;

                ParseMusicEvent::dispatch($event, $spotify);

                $this->info('Fox Theatre: dispatched job for `' . $name . '`');
            } catch (\Exception $e) {
                $this->error('Fox Theatre: exception — ' . $e->getMessage());
            }
        });

        $this->info(count($events) . ' events parsed for `' . $provider->name . '`');

        if (count($events)) {
            $provider->last_scraped = Carbon::now();
            $provider->save();
        }

        return $events;
    }

    /**
    * Provider The Tabernacle
    *
    * The site is a React/Chakra UI app that embeds structured data as JSON-LD
    * (schema.org MusicEvent) directly in the static HTML — no JS execution needed.
    * We extract those objects and parse them.
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerTheTabernacle(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        // Fetch page HTML — file_get_contents is fine since all data is server-rendered
        $html = file_get_contents($provider->scrape_url);
        if (empty($html)) {
            $this->error('Failed to fetch ' . $provider->scrape_url);
            return false;
        }

        $rawEvents = $this->extractMusicEventJsonLd($html);
        $events    = [];

        foreach ($rawEvents as $ev) {
            if (empty($ev['name']) || empty($ev['startDate'])) continue;

            // Date/time
            $startDate = '';
            $startTime = '8:00 PM';
            $endTime   = '11:00 PM';
            try {
                $dateObj   = Carbon::parse($ev['startDate']);
                $startDate = $dateObj->format('Y-m-d');
                $startTime = $dateObj->format('g:i A');
                $endTime   = $dateObj->copy()->addHours(3)->format('g:i A');
            } catch (\Exception $e) {}
            if (empty($startDate)) continue;
            if (Carbon::parse($startDate)->lt($this->today)) continue;

            $name = html_entity_decode(trim($ev['name']), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $isSoldOut = false;
            $status    = strtolower($ev['eventStatus'] ?? '');
            if (strpos($status, 'cancelled') !== false || strpos($status, 'soldout') !== false) {
                $isSoldOut = true;
            }

            $events[] = [
                'name'               => $name,
                'location_id'        => $provider->location_id,
                'user_id'            => 1,
                'category_id'        => $provider->location->category_id,
                'event_type_id'      => 2,
                'start_date'         => $startDate,
                'start_time'         => $startTime,
                'end_time'           => $endTime,
                'website'            => trim($ev['url'] ?? ''),
                'price'              => $isSoldOut ? 'N/A' : 'See Website',
                'is_sold_out'        => $isSoldOut,
                'is_family_friendly' => $provider->location->is_family_friendly,
                'bands'              => $this->extractBandsFromEventTitle($name),
                'tags'               => [],
            ];
        }

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        $validator = $this->validate($events);
        if (!$validator) return false;

        foreach ($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);
            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        if (count($events)) {
            $provider->last_scraped = Carbon::now();
            $provider->save();
        }

        return $events;
    }

    /**
    * Extract schema.org MusicEvent JSON-LD objects from an HTML page.
    *
    * Used by Live Nation / Ticketmaster-powered venue sites (Tabernacle,
    * Buckhead Theatre, etc.) that embed structured event data server-side.
    *
    * @param  string $html
    * @return array
    */
    private function extractMusicEventJsonLd(string $html): array
    {
        $needle    = '{"@context":"https://schema.org","@type":"MusicEvent"';
        $positions = [];
        $offset    = 0;
        while (($pos = strpos($html, $needle, $offset)) !== false) {
            $positions[] = $pos;
            $offset = $pos + 1;
        }

        $events = [];
        foreach ($positions as $pos) {
            $depth  = 0;
            $inStr  = false;
            $escape = false;
            $len    = strlen($html);
            for ($i = $pos; $i < $len; $i++) {
                $ch = $html[$i];
                if ($escape)              { $escape = false; continue; }
                if ($ch === '\\' && $inStr) { $escape = true; continue; }
                if ($ch === '"')          { $inStr = !$inStr; continue; }
                if ($inStr)               { continue; }
                if ($ch === '{')          { $depth++; }
                elseif ($ch === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $ev = json_decode(substr($html, $pos, $i - $pos + 1), true);
                        if ($ev) $events[] = $ev;
                        break;
                    }
                }
            }
        }
        return $events;
    }

    /**
    * Extract artist band(s) from a Ticketmaster-style event title.
    *
    * Common patterns:
    *   "Artist - Tour Name"          → ['Artist']
    *   "Artist: Tour/Show Name"      → ['Artist']
    *   "A & B: Tour Name"            → ['A', 'B']
    *   "Promoter Presents: Artist"   → ['Artist']
    *   "Artist with special guest X" → ['Artist']
    *   "Artist"                      → ['Artist']
    *
    * @param  string $name
    * @return array
    */
    private function extractBandsFromEventTitle(string $name): array
    {
        // "X Presents: Headliner" → headliner is the act
        if (preg_match('/\bpresents:\s+(.+)$/i', $name, $m)) {
            return [trim($m[1])];
        }

        // Strip "with special guest ..." or "w/ ..." suffix before delimiter detection
        $stripped = preg_replace('/\s+with\s+special\s+guest.*/i', '', $name);
        $stripped = preg_replace('/\s+w\/\s+.*/i', '', $stripped);

        // Find which major delimiter comes first: " - " or ": "
        $dashPos  = strpos($stripped, ' - ');
        $colonPos = strpos($stripped, ': ');

        if ($dashPos !== false && ($colonPos === false || $dashPos < $colonPos)) {
            $artist = trim(substr($stripped, 0, $dashPos));
        } elseif ($colonPos !== false) {
            $artist = trim(substr($stripped, 0, $colonPos));
        } else {
            $artist = trim($stripped);
        }

        // Split by " & " for co-headliners
        if (strpos($artist, ' & ') !== false) {
            return array_map('trim', explode(' & ', $artist));
        }

        return [$artist ?: $name];
    }

    /**
    * Provider Buckhead Theatre
    *
    * Live Nation venue. React-based site that embeds all event data as
    * schema.org MusicEvent JSON-LD in the static HTML — same platform
    * as The Tabernacle.
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerBuckheadTheatre(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        $html = file_get_contents($provider->scrape_url);
        if (empty($html)) {
            $this->error('Failed to fetch ' . $provider->scrape_url);
            return false;
        }

        $rawEvents = $this->extractMusicEventJsonLd($html);
        $events    = [];

        foreach ($rawEvents as $ev) {
            if (empty($ev['name']) || empty($ev['startDate'])) continue;

            // Date/time
            $startDate = '';
            $startTime = '8:00 PM';
            $endTime   = '11:00 PM';
            try {
                $dateObj   = Carbon::parse($ev['startDate']);
                $startDate = $dateObj->format('Y-m-d');
                $startTime = $dateObj->format('g:i A');
                $endTime   = $dateObj->copy()->addHours(3)->format('g:i A');
            } catch (\Exception $e) {}
            if (empty($startDate)) continue;
            if (Carbon::parse($startDate)->lt($this->today)) continue;

            $name = html_entity_decode(trim($ev['name']), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $isSoldOut = false;
            $status    = strtolower($ev['eventStatus'] ?? '');
            if (strpos($status, 'cancelled') !== false || strpos($status, 'soldout') !== false) {
                $isSoldOut = true;
            }

            $events[] = [
                'name'               => $name,
                'location_id'        => $provider->location_id,
                'user_id'            => 1,
                'category_id'        => $provider->location->category_id,
                'event_type_id'      => 2,
                'start_date'         => $startDate,
                'start_time'         => $startTime,
                'end_time'           => $endTime,
                'website'            => trim($ev['url'] ?? ''),
                'price'              => $isSoldOut ? 'N/A' : 'See Website',
                'is_sold_out'        => $isSoldOut,
                'is_family_friendly' => $provider->location->is_family_friendly,
                'bands'              => $this->extractBandsFromEventTitle($name),
                'tags'               => [],
            ];
        }

        $this->info(count($events) . ' events found for provider `' . $provider->name . '`');

        $validator = $this->validate($events);
        if (!$validator) return false;

        foreach ($events as $event) {
            ParseMusicEvent::dispatch($event, $spotify);
            $this->info('Dispatching job for event `' . $event['name'] . '`');
        }

        if (count($events)) {
            $provider->last_scraped = Carbon::now();
            $provider->save();
        }

        return $events;
    }
}
