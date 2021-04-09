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
use App\Jobs\Locations\CrawlAisleFiveLink;
use App\Jobs\Locations\CrawlLaughingSkullLoungeLink;
use App\Jobs\Locations\CrawlTerminalWestLink;
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
            // ->where('last_scraped', '<=', $this->today->format('Y-m-d H:i:s'))
            ->where('id', '=', 11)
            // ->orWhereNull('last_scraped')
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

                        if (empty($event['price'])) {
                            $event['price'] = 'N/A';
                        }
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

                $event['bands'][] = Str::title(trim($event['name']));
            }

            // look for other info attached to event
            try {
                $info = trim($node->filter('.schedule-show-event-title')->text());

                if (!empty($info)) {
                    $event['name'] .= ', ' . $info;
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
        if (count($events)) {
          $provider->last_scraped = Carbon::now();

          $provider->save();
        }

        return $events;
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
      $eventsData = $crawler->filter('div.anim')->each(function ($node, $index) use ($provider, $possibleValues, &$currentValidTime) {
        $events = [];

        // look for date match
        // for dynamic events
        try {
          $findDate = $node->filter('p')->reduce(function ($childNode) {
            return !empty($childNode->text());
          })->each(function ($childNode, $index) {
            return trim($childNode->text());
          });

          if (empty($findDate) || empty($findDate[0])) {
            return false;
          }

          if (in_array($findDate[0], $possibleValues)) {
            foreach ($findDate as $i => $value) {
              if ($i === 0) {
                continue;
              }

              // parse event data
              $ex = explode(' - ', $value);

              $eventName = str_replace($ex[0] . ' - ', '', Str::title(trim($ex[1])));

              $startDate = Carbon::parse(trim($ex[0]));
              $endDate = $startDate->copy();

              // if sunday, then hours = 9pm - 12am
              // otherwise, 10pm - 2am
              // collect start time & end time here
              if ((int) $startDate->format('w') === Carbon::SUNDAY) {
                $startDate->setTime(21, 0, 0, 0);
                $endDate->addHours(3);
              } else {
                $startDate->setTime(22, 0, 0, 0);
                $endDate->addHours(4);
              }

              // if event is passed, skip it
              if ($this->today->lte($startDate)) {
                $event = [
                  'name'          => $eventName,
                  'location_id'   => $provider->location_id,
                  'category_id'   => $provider->location->category_id,
                  'event_type_id' => 2,
                  'user_id'       => 1,
                  'start_date'    => $startDate->format('Y-m-d'),
                  'start_time'    => $startDate->format('g:i A'),
                  'end_time'      => $endDate->format('g:i A'),
                  'website'       => $provider->location->website,
                  'is_sold_out'   => false,
                  'price'         => '$10.00',
                  'bands'         => [
                    $eventName
                  ]
                ];

                $events[] = $event;
              }
            }
          }
        } catch (\Exception $e) {
          //
        }

        return $events;
      });

      $events = collect([]);
      foreach ($eventsData as $rows) {
        foreach ($rows as $row) {
          $events->push($row);
        }
      }

      // collect events data for non-dynamic events
      // /weeknight-music.html
      $bandsByWeekday = [
        Carbon::SUNDAY    => 'Uncle Sugar',
        Carbon::MONDAY    => 'Northside Tavern Jam with Lola',
        Carbon::TUESDAY   => 'Swami Gone Bananas',
        Carbon::WEDNESDAY => 'Eddie 9V',
        Carbon::THURSDAY  => 'The Breeze Kings'
      ];

      $today = $this->today->copy();
      $endDate = $today->copy()->addWeeks(4);

      foreach ($bandsByWeekday as $dow => $bandName) {
        $startDate = $today->copy()->next($dow);

        $dates = [];
        for ($date = $startDate->copy(); $date->lte($today->copy()->addWeeks(4)); $date->addWeek()) {
          $dates[] = $date->copy();
        }

        foreach ($dates as $date) {
          $endDate = $date->copy();

          if ((int) $date->format('w') === Carbon::SUNDAY) {
            $date->setTime(21, 0, 0, 0);
            $endDate->addHours(3);
          } else {
            $date->setTime(22, 0, 0, 0);
            $endDate->addHours(4);
          }

          $event = [
            'name'          => $bandName,
            'location_id'   => $provider->location_id,
            'category_id'   => $provider->location->category_id,
            'event_type_id' => 2,
            'user_id'       => 1,
            'start_date'    => $date->format('Y-m-d'),
            'start_time'    => $date->format('g:i A'),
            'end_time'      => $endDate->format('g:i A'),
            'website'       => $provider->location->website,
            'is_sold_out'   => false,
            'price'         => '$10.00',
            'bands'         => [
              $bandName
            ],
            'timestamp'     => (int) $date->format('U')
          ];

          $events->push($event);
        }
      }

      $events = $events->sortBy('timestamp');

      $events = $events->values()->all();

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

          // get website
          $event['website'] = rtrim($node->filter('.right-buttons > a.more')->attr('href'), '/');

          // check if event is cancelled
          try {
            $buttonText = trim($node->filter('.right-buttons > a.button')->text());
            $buttonText = strtolower($buttonText);

            if (!empty($buttonText) && $buttonText === 'cancelled') {
              $event['is_cancelled'] = true;
            }
          } catch (\Exception $e) {
            // do nothing
          }

          if (isset($event['is_cancelled'])) {
            return $event;
          }

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
              $info = trim($node->filter('.middle-info > p')->text());

              if (!empty($info)) {
                  $event['description'] = $info;
              }
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
                  $ex = explode(' ', $row['time']);

                  $firstTime = null;
                  foreach($ex as $part) {
                      if (!empty($part) && (strstr($part, 'PM') || strstr($part, 'AM'))) {
                          $firstTime = trim($part);

                          break;
                      }
                  }

                  if (!empty($firstTime)) {
                      $dateObj = Carbon::parse($event['start_date'] . ' ' . $firstTime);

                      $event['start_time'] = $dateObj->format('g:i A');
                      $event['end_time'] = $dateObj->copy()->addHours(3)->format('g:i A');
                  }
              }
          }

          // get date
          $event['start_date'] = trim($node->filter('.right-buttons > .date')->text());
          $event['start_date'] = Carbon::parse($event['start_date']);

          // see if date could be for next year
          $today = $this->today->copy();
          $isNextYear = $event['start_date']->isBefore($today);

          if ($isNextYear) {
              $isNextYear = ($event['start_date']->diffInMonths($today) > 4);
          }

          if ($isNextYear) {
              $event['start_date'] = $event['start_date']->addYears(1)->format('Y-m-d');
          } else {
              $event['start_date'] = $event['start_date']->format('Y-m-d');
          }

          return $event;
      });

      // filter out cancelled events
      foreach($events as $key => $event) {
        if (isset($event['is_cancelled'])) {
          unset($events[$key]);
        }
      }

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
              } elseif (strstr($valueLower, 'weekend pass')) {
                  $event['event_type_id'] = $eventTypes['festival']->id;

                  $prices = str_replace('Weekend Pass ', '', $value);

                  if (strstr($prices, '/')) {
                      $ex = explode('/', $prices);

                      $price = trim(str_replace($findToReplace, '', $ex[1]));

                      if (!empty($event['price'])) {
                          $event['price'] = $event['price'] . ' - ' . $price;
                      } else {
                          $event['price'] = $price;
                      }
                  }

                  if (strstr($prices, ' or ')) {
                      $ex = explode(' or ', $prices);

                      if (isset($ex[0])) {
                          $event['price'] = trim(str_replace(' per day', '', $ex[0]));
                      }

                      if (isset($ex[1])) {
                          $ex[1] = trim(str_replace('Weekend Pass', '', $ex[1]));

                          if (!empty($event['price'])) {
                              $event['price'] .= ' - ' . $ex[1];
                          } else {
                              $event['price'] = $ex[1];
                          }
                      }
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
                  $event['name'] = $event['name'];

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
              $event['price'] = '$10.00';
            }

            if ($event['name'] === 'Hannah Thomas') {
              $event['price'] = '$5.00';
            }

            if ($event['name'] === 'The Breaks') {
              $event['price'] = '$5.00';
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
        // won't work with our scraper
        // use symfony dom crawler manually
        $html = file_get_contents($provider->scrape_url);
        $crawler = new Crawler($html);

        // let's just collect links, that's it
        $today = Carbon::now();

        $urls = $crawler->filter('.calendar-day-event')
            ->reduce(function ($node) use ($today) {
                $status = true;

                try {
                    $event = $node->filter('.event-title')->text();

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

                $url = rtrim($node->filter('.event-title')->eq(0)->attr('href'), '/');

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
        $max = 60;
        foreach($urls as $event) {
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

        return $urls;
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
      $fullUrl = $provider->scrape_url . '?fromDate=' . $startDate;
      // $fullUrl .= '&thruDate=' . $endDate;

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
        $event['name'] = trim(html_entity_decode($data['title']));
        // $event['website'] = str_replace('?utm_medium=api', '', $data['url']);
        $event['website'] = trim($data['url']);

        // get supporting bands
        if (!empty($data['supportsName'])) {
          $supportingBands = html_entity_decode(trim($data['supportsName']));

          $event['short_description'] = 'With ' . $supportingBands;

          // collect bands
          $ex = explode(', ', $supportingBands);

          foreach($ex as $row) {
            $event['bands'][] = Str::title(trim($row));
          }
        }

        // parse name
        if (strstr($event['name'], ', ')) {
          $ex = explode(', ', $event['name']);

          foreach($ex as $row) {
            $event['bands'][] = Str::title(trim($row));
          }
        } else {
          $event['bands'][] = $event['name'];
        }

        if (strstr($event['name'], 'Open Mic')) {
          $event['event_type_id'] = $eventTypes['on-going']->id;
          $event['price'] = '$5.00 - $20.00';

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

        // if price is empty
        if (empty($event['price'])) {
          try {
            $this->info('empty price lookup. url: ' . $data['purchaseUrl']);

            $crawler = $scraper->request('GET', $data['purchaseUrl']);

            $event['price'] = trim($crawler->filter('.js-display-price')->text());
            $event['price'] = str_replace('–', '-', $event['price']);
          } catch (\Exception $e) {
            // do nothing

            $this->error($e->getMessage() . ' :: ' . $event['name']);
          }
        }

        // if price is STILL empty, default to "N/A"
        if (empty($event['price'])) {
          $event['price'] = 'N/A';
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
          'image_url' => !empty($row->attach) ? $row->attach : '',
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

        // tags
        if (!empty($row->categories)) {
          $event['tags'] = explode(',', trim($row->categories));
        }

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
          //
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
        $event['start_date'] = Carbon::parse($event['start_date']);

        // see if date could be for next year
        $today = $this->today->copy();
        $isNextYear = $event['start_date']->isBefore($today);

        if ($isNextYear) {
          $isNextYear = ($event['start_date']->diffInMonths($today) > 4);
        }

        if ($isNextYear) {
          $event['start_date'] = $event['start_date']->addYears(1)->format('Y-m-d');
        } else {
          $event['start_date'] = $event['start_date']->format('Y-m-d');
        }

        // get start time
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
      $url = $provider->scrape_url;

      $crawler = $scraper->request('GET', $url);

      $events = [];
      $eventUrls = [];
      $crawler->filter('#ft-date-list > li')->each(function ($node) use (&$events, &$eventUrls, $provider) {
        // get date
        $startDate = Carbon::parse(trim($node->filter('h2')->text()));

        $item = $node->filter('li')->each(function ($eventNode) use (&$events, $provider, $startDate) {
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

          $event['start_date'] = $startDate->format('Y-m-d');

          // get event name & start/end time
          $eventName = trim($eventNode->filter('h3')->text());

          $ex = explode(' @ ', $eventName);

          $startTime = trim($ex[1]);
          $replaceDate = $startDate->format('l F j') . ' @ ' . $ex[1];

          $event['name'] = str_replace(' - ' . $replaceDate, '', $eventName);

          $dateObj = Carbon::parse($event['start_date'] . ' ' . $startTime);

          $event['start_time'] = $dateObj->format('g:i A');
          $event['end_time'] = $dateObj->copy()->addHours(3)->format('g:i A');

          // website
          $event['website'] = 'https://www.freshtix.com' . $eventNode->filter('h3 a')->attr('href');

          // get price
          $event['price'] = trim($eventNode->filter('.ft-event-price-range')->text());

          return $event;
        });

        foreach ($item as $event) {
          if (!in_array($event['website'], $eventUrls)) {
            $eventUrls[] = $event['website'];
            $events[] = $event;
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

      return $events;
    }

    /**
    * Provider Red Light Cafe
    *
    * @param Provider      $provider
    * @param WebScraper    $scraper
    * @param SpotifyWebAPI $spotify
    *
    * @return array
    */
    public function providerRedLightCafe(Provider $provider, $scraper, SpotifyWebAPI $spotify)
    {
        // get client
        $client = new Guzzle;

        // get website url
        $locationWebsite = $provider->location->website;

        // get events for 4 months
        $today = Carbon::now();
        $endDate = $today->copy()->addMonths(4);
        $months = [];

        $months[] = $today->copy();

        for ($date = $today->copy(); $date->lte($endDate); $date->addMonth()) {
            $months[] = $date->copy();
        }

        // get categories
        $items = Category::all();

        $categories = [];
        $categorySlugs = [];
        foreach($items as $item) {
            $categories[$item->slug] = $item;
            $categorySlugs[] = '`' . $item->slug . '`';
        }

        $categorySlugs = implode(', ', $categorySlugs);

        // get event data
        $events = [];
        foreach($months as $date) {
            $url = $provider->scrape_url . '?month=' . $date->format('m-Y');
            $url .= '&collectionId=516db1d0e4b0633e7b269026';

            $response = $client->get($url);
            $response = json_decode((string) $response->getBody(), true);

            foreach($response as $item) {
                // init data
                $event = [
                    'name' => $item['title'],
                    'website' => $locationWebsite . $item['fullUrl'],
                    'image_url' => $item['assetUrl'],
                    'start_date' => '',
                    'start_time' => '',
                    'end_date' => '',
                    'end_time' => '',
                    'category_id' => $provider->location->category_id,
                    'location_id' => $provider->location_id,
                    'user_id' => 1,
                    'event_type_id' => 2,
                    'price' => '',
                    'is_sold_out' => false,
                    'short_description' => '',
                    'tags' => [],
                    'bands' => []
                ];

                // parse start date & time
                $start_date = floor(($item['startDate'] / 1000));
                $start_date = Carbon::createFromFormat('U', $start_date)
                    ->subHours(4);

                $event['start_date'] = $start_date->format('Y-m-d');
                $event['start_time'] = $start_date->format('g:i A');

                // parse end date & time
                if (!empty($item['endDate'])) {
                    $end_date = floor(($item['endDate'] / 1000));
                    $end_date = Carbon::createFromFormat('U', $end_date)
                        ->subHours(4);

                    $event['end_date'] = $end_date->format('Y-m-d');
                    $event['end_time'] = $end_date->format('g:i A');

                    if ($event['start_date'] === $event['end_date']) {
                        $event['end_date'] = null;
                    }
                } else {
                    $event['end_time'] = $start_date->addHours(3)->format('g:i A');
                }

                // cache check
                $cacheKey = $provider->id . '-' . md5($event['name']) . '-' . $event['start_date'];

                if (Cache::has($cacheKey)) {
                    $this->info('Already in DB...');

                    continue;
                }

                // get event price
                // and meta info
                $crawler = new Crawler($item['excerpt']);

                $metaItems = $crawler->filter('p')->each(function ($node) {
                    $text = trim($node->text());
                    $html = trim($node->html());
                    $metaValue = null;
                    $metaType = 'price';

                    if (strstr($text, '$')) {
                        $ex = explode('</strong>', $html);

                        if (!empty($ex[1])) {
                            $ex2 = explode('<br>', $ex[1]);

                            $metaValue = $ex2[0];
                        } elseif (strstr($ex[0], ' | ')) {
                            $ex2 = explode(' | ', $ex[0]);

                            foreach($ex2 as $ex2Row) {
                                if (strstr($ex2Row, '$')) {
                                    if (strstr($ex2Row, ') ')) {
                                        $ex3 = explode(') ', $ex2Row);

                                        $metaValue = $ex3[0] . ')';
                                    } elseif (empty($metaValue)) {
                                        $metaValue = $ex2Row;
                                    }
                                }
                            }
                        } elseif (strstr($ex[0], ' free ')) {
                            $metaValue = 'Free';
                        } else {
                            $metaValue = $this->ask('No price could be found, please enter a value based off of this: `' . $ex[0] . '`');
                        }
                    } elseif (strstr($text, ' TICKETS')) {
                        $metaValue = null;
                        $metaType = null;
                    } else {
                        $metaValue = $text;
                        $metaType = 'description';
                    }

                    return [
                        'type' => $metaType,
                        'value' => trim($metaValue)
                    ];
                });

                foreach($metaItems as $metaRow) {
                    if (!empty($metaRow['type']) && !empty($metaRow['value'])) {
                        switch ($metaRow['type']) {
                            case 'price':
                                $event['price'] = $metaRow['value'];
                            break;

                            case 'description':
                                $event['short_description'] = $metaRow['value'];
                            break;
                        }
                    }
                }

                // check if event is "music" or not
                $nameLower = strtolower($event['name']);

                if (strstr($nameLower, 'comedy')) {
                    $event['category_id'] = $categories['comedy']->id;
                }

                if (strstr($nameLower, 'songwriters')) {
                    $event['category_id'] = $categories['arts-theatre']->id;
                }

                $categoryOtherTerms = [
                    'nerdtastic',
                    'open mic',
                    'poker tournament'
                ];

                foreach($categoryOtherTerms as $term) {
                    if (strstr($nameLower, $term)) {
                        $event['category_id'] = $categories['other']->id;
                    }
                }

                // @TODO - check for tags

                // gather bands if music event
                if ($event['category_id'] === $categories['music']->id) {
                    $this->info($event['name']);

                    if (!empty($event['short_description'])) {
                        $this->info($event['short_description']);
                    }

                    $isMusicCategory = $this->ask('Is this a music event? (y/n)');

                    if ($isMusicCategory === 'y') {
                        $bandsInfo = $this->ask('What bands are playing? (ex. abc,def,ghi)');

                        $event['bands'] = explode(',', $bandsInfo);
                    } else {
                        $categorySlug = $this->ask('What category is this event instead of `music`? Options: (' . $categorySlugs . ')');

                        $event['category_id'] = $categories[$categorySlug]->id;
                    }
                }

                // only add event if date >= today
                if ($start_date->greaterThanOrEqualTo($today)) {
                    $find = [
                        '&amp;'
                    ];

                    $event['name'] = trim(str_replace($find, '', $event['name']));
                    $event['name'] = str_replace('  ', ' ', $event['name']);

                    $validator = $this->validate([ $event ]);

                    if (!$validator) {
                        $this->error('Empty data for event: ' . json_encode($event));
                    } else {
                        $events[] = $event;

                        Cache::put($cacheKey, true);

                        ParseMusicEvent::dispatch($event, $spotify);

                        $this->info('Fired off job to create event.');
                    }
                }
            }
        }

        $this->info(count($events) . ' events parsed for `' . $provider->name . '`');

        // save last scraped time
        $provider->last_scraped = Carbon::now();

        $provider->save();

        return $events;
    }
}
