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
            ->where('id', '=', 4)
            ->orWhereNull('last_scraped')
            ->get();

        $scraper = new WebScraper;
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

        $events = $crawler->filter($path)
            ->reduce(function ($node, $index) {
                if ($index > 1) {
                    $values = $node->filter('span')
                        ->reduce(function ($childNode) {
                            $status = true;

                            try {
                                $text = trim($childNode->text());

                                if (empty($text)) {
                                    $status = false;
                                }

                                if (strstr($text, 'Check out the')) {
                                    $status = false;
                                }

                                if (strstr($text, 'Like Us on')) {
                                    $status = false;
                                }
                            } catch (\Exception $e) {
                                $status = false;
                            }

                            return $status;
                        })
                        ->each(function ($childNode) {
                            return $childNode->text();
                        });
                } else {
                    $values = [];
                }

                return $index > 1 && count($values);
            })
            ->each(function ($node, $index) use ($provider) {
                $events = [];

                $values = $node->filter('span')
                    ->reduce(function ($childNode) {
                        $text = trim($childNode->text());
                        $status = true;

                        if ($text == 'Bill Sheffield') {
                            $status = false;
                        }

                        return $status;
                    })
                    ->each(function ($childNode) {
                        $html = trim($childNode->html());
                        $text = trim($childNode->text());
                        $name = null;
                        $value = $html;

                        if (strstr($text, 'Early Show')) {
                            $value = 'Bill Sheffield :: ' . $text;
                        }

                        try {
                            $name = $childNode->filter('strong')->each(function ($subChildNode) {
                                return $subChildNode->text();
                            });

                            if (count($name) === 1) {
                                $name = $name[0];
                            } elseif (count($name) > 1) {
                                $names = $childNode->filter('strong')->each(function ($subChildNode) {
                                    return $subChildNode->html();
                                });

                                $name = implode('---', $names);
                            } else {
                                $name = null;
                            }
                        } catch(\Exception $e) {

                        }

                        if (!empty($name)) {
                            $value = $name . ' :: ';

                            if (strstr($text, ' - ')) {
                                $ex = explode(' - ', $text);

                                $value .= trim($ex[0]);
                            }
                        }

                        return $value;
                    });

                foreach($values as $index => $value) {
                    $data = [
                        'name' => '',
                        'location_id' => $provider->location_id,
                        'user_id' => 1,
                        'category_id' => $provider->location->category_id,  
                        'event_type_id' => 2,
                        'start_date' => '',
                        'price' => '$10',
                        'start_time' => '',
                        'end_time' => '',
                        'website' => $provider->location->website,
                        'is_sold_out' => false
                    ];

                    $ex = explode(' :: ', $value);

                    if (!empty($ex[1]) && strstr($ex[1], 'Early Show')) {
                        $lastValue = explode(' :: ', $values[$index - 1]);

                        $data['start_date'] = Carbon::parse($lastValue[1])->format('Y-m-d');
                        $data['start_time'] = '6:00 PM';
                        $data['end_time'] = '9:00 PM';
                        $data['name'] = $ex[0];
                    } else {
                        if (strstr($ex[0], '---')) {
                            $bands = explode('---', $ex[0]);

                            $data['name'] = $bands[0];
                            $data['start_date'] = Carbon::parse($ex[1])->format('Y-m-d');
                            $data['start_time'] = '10:00 PM';
                            $data['end_time'] = '2:00 AM';

                            $val = [
                                'name' => $bands[1],
                                'location_id' => $provider->location_id,
                                'user_id' => 1,
                                'category_id' => $provider->location->category_id,  
                                'event_type_id' => 2,
                                'start_date' => Carbon::parse($ex[1])->addDay()->format('Y-m-d'),
                                'price' => '$10',
                                'start_time' => '10:00 PM',
                                'end_time' => '2:00 AM',
                                'website' => $provider->location->website,
                                'is_sold_out' => false
                            ];

                            $events[] = $val;
                        } else {
                            $data['start_date'] = Carbon::parse($ex[1])->format('Y-m-d');
                            $data['start_time'] = '10:00 PM';
                            $data['end_time'] = '2:00 AM';
                            $data['name'] = $ex[0];
                        }
                    }

                    $events[] = $data;
                }

                return $events;
            });

        $results = [];
        foreach($events as $rows) {
            foreach($rows as $row) {
                $results[] = $row;
            }
        }

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
}
