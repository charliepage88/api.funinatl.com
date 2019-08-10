<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

use DB;

class Report extends Model
{
    /**
    * Get Admin Dashboard Stats
    *
    * @return array
    */
    public static function getAdminDashboardStats()
    {
        // init stats array
        $stats = [
            'events' => [
                'upcoming'                     => 0,
                'upcoming_increase'            => 0,
                'submissions_pending'          => 0,
                'submissions_pending_increase' => 0
            ],

            'users' => [
                'total'        => 0,
                'new'          => 0,
                'new_increase' => 0
            ],

            'locations' => [
                'submissions_pending'          => 0,
                'submissions_pending_increase' => 0
            ],

            'site' => [
                'contact_pending'          => 0,
                'contact_pending_increase' => 0
            ]
        ];

        // event stats

        // get stats for current time period
        $startDate = Carbon::now();
        $endDate = $startDate->copy()->addMonth();

        $stats['events']['upcoming'] = DB::table('events')
            ->where('start_date', '>=', $startDate->format('Y-m-d'))
            ->where('start_date', '<=', $endDate->format('Y-m-d'))
            ->count();

        // compare to last week to see if it has
        // increased or not
        $startDate = Carbon::now()->subWeek();
        $endDate = $startDate->copy()->addMonth();

        $lastWeekCount = DB::table('events')
            ->where('start_date', '>=', $startDate->format('Y-m-d'))
            ->where('start_date', '<=', $endDate->format('Y-m-d'))
            ->count();

        if ($stats['events']['upcoming'] > $lastWeekCount) {
            $stats['events']['upcoming_increase'] = 2;
        } elseif($stats['events']['upcoming'] < $lastWeekCount) {
            $stats['events']['upcoming_increase'] = 1;
        }

        // get stats for user submitted events
        $items = DB::table('events')
            ->where('source', '=', 'submission')
            ->where('active', '=', 0)
            ->get();

        $stats['events']['submissions_pending'] = $items->count();

        // compare to last week to see if it has
        // increased or not
        if ($items->count()) {
            $startDate = Carbon::now()->subWeek();
            $endDate = $startDate->copy()->addMonth();

            $lastWeekCount = DB::table('events')
                ->where('source', '=', 'submission')
                ->where('start_date', '>=', $startDate->format('Y-m-d'))
                ->where('start_date', '<=', $endDate->format('Y-m-d'))
                ->whereNotIn('id', $items->pluck('id'))
                ->count();

            if ($stats['events']['submissions_pending'] > $lastWeekCount) {
                $stats['events']['submissions_pending_increase'] = 2;
            } elseif($stats['events']['submissions_pending'] < $lastWeekCount) {
                $stats['events']['submissions_pending_increase'] = 1;
            }
        }

        // user stats

        // get total count
        $stats['users']['total'] = DB::table('users')->count();

        // get new user count
        $startDate = Carbon::now()->subWeek();

        $stats['users']['new'] = DB::table('users')
            ->where('created_at', '>=', $startDate->format('Y-m-d'))
            ->count();

        // compare to previous week
        $startDate = Carbon::now()->subWeeks(2);

        $lastWeekCount = DB::table('users')
            ->where('created_at', '>=', $startDate->format('Y-m-d'))
            ->count();

        if ($stats['users']['new'] > $lastWeekCount) {
            $stats['users']['new_increase'] = 2;
        } elseif ($stats['users']['new'] < $lastWeekCount) {
            $stats['users']['new_increase'] = 1;
        }

        // location stats

        // get stats for user submitted locations
        $items = DB::table('locations')
            ->where('source', '=', 'submission')
            ->where('active', '=', 0)
            ->get();

        $stats['locations']['submissions_pending'] = $items->count();

        // compare to last week to see if it has
        // increased or not
        if ($items->count()) {
            $startDate = Carbon::now()->subWeek();
            $endDate = $startDate->copy()->addMonth();

            $lastWeekCount = DB::table('locations')
                ->where('source', '=', 'submission')
                ->where('created_at', '>=', $startDate->format('Y-m-d H:i:s'))
                ->where('created_at', '<=', $endDate->format('Y-m-d H:i:s'))
                ->whereNotIn('id', $items->pluck('id'))
                ->count();

            if ($stats['locations']['submissions_pending'] > $lastWeekCount) {
                $stats['locations']['submissions_pending_increase'] = 2;
            } elseif($stats['locations']['submissions_pending'] < $lastWeekCount) {
                $stats['locations']['submissions_pending_increase'] = 1;
            }
        }

        // site stats

        // get stats for contact submissions
        $items = DB::table('contact_submissions')
            ->where('reviewed', '=', false)
            ->get();

        $stats['site']['contact_pending'] = $items->count();

        // compare to last week to see if it has
        // increased or not
        if ($items->count()) {
            $startDate = Carbon::now()->subWeek();
            $endDate = $startDate->copy()->addMonth();

            $lastWeekCount = DB::table('contact_submissions')
                ->where('created_at', '>=', $startDate->format('Y-m-d H:i:s'))
                ->where('created_at', '<=', $endDate->format('Y-m-d H:i:s'))
                ->whereNotIn('id', $items->pluck('id'))
                ->count();

            if ($stats['site']['contact_pending'] > $lastWeekCount) {
                $stats['site']['contact_pending_increase'] = 2;
            } elseif($stats['site']['contact_pending'] < $lastWeekCount) {
                $stats['site']['contact_pending_increase'] = 1;
            }
        }

        return $stats;
    }

    /**
    * Get Admin Dashboard Charts
    *
    * @return array
    */
    public static function getAdminDashboardCharts()
    {
        // init charts array
        $charts = [
            'events_timeline' => [
                'options' => [
                    'responsive' => true
                ],
                'data' => []
            ]
        ];

        // events timeline chart
        $data = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => '# of Events',
                    'data' => [],
                    'fill' => false,
                    'lineTension' => 0.1,
                    'borderColor' => 'rgb(75, 192, 192)'
                ]
            ]
        ];

        $startDate = Carbon::now()->subMonth()->startOf('month');
        $endDate = $startDate->copy()->addMonths(6)->endOf('month');

        for($date = $startDate; $date->lte($endDate); $date->addMonth()) {
            // add label
            $data['labels'][] = $date->format('F');

            // populate data
            $start = $date->copy()->startOf('month')->format('Y-m-d');
            $end = $date->copy()->endOf('month')->format('Y-m-d');

            $count = DB::table('events')
                ->where('start_date', '>=', $start)
                ->where('start_date', '<=', $end)
                ->count();

            $data['datasets'][0]['data'][] = $count;
        }

        $charts['events_timeline']['data'] = $data;

        return $charts;
    }

    /**
    * Get Events By Period
    *
    * @param string $start_date
    * @param string $end_date
    * @param array  $params
    *
    * @return EventCollection
    */
    public static function getEventsByPeriod(string $start_date, string $end_date, $params = [])
    {
        // init var
        $response = [];
        $errorResponse = function ($key) {
            $response = [
                'events' => [],
                'status' => false,
                'error' => 'Cannot find ' . $key . '.'
            ];

            $response[$key] = [];

            return $response;
        };

        // get events
        $query = Event::shouldShow()
            ->where('start_date', '>=', $start_date)
            ->where('start_date', '<=', $end_date)
            ->orderBy('start_date', 'asc');

        // parse params
        if (!empty($params)) {
            // filter by category
            if (!empty($params['category'])) {
                $category = Category::bySlug($params['category']);

                if (!empty($category->id)) {
                    $query->where('category_id', '=', $category->id);

                    $response['category'] = $category->toSearchableArray();
                } else {
                    return $errorResponse('category');
                }
            }

            // filter by location
            if (!empty($params['location'])) {
                $location = Location::bySlug($params['location']);

                if (!empty($location)) {
                    $query->where('location_id', '=', $location->id);

                    $response['location'] = $location->toSearchableArray();
                }
            }

            // filter by tag
            if (!empty($params['tag'])) {
                $tag = Tag::bySlug($params['tag']);

                if (!empty($tag)) {
                    $eventIds = $tag->findIdsByModelId(new Event);

                    $query->whereIn('id', $eventIds);

                    $response['tag'] = $tag->toSearchableArray();
                }
            }
        }

        // get the events data
        $events = $query->get();

        // set weekend/weekdays
        Carbon::macro('isWeekendDay', function () {
            return $this->isFriday() || $this->isSaturday() || $this->isSunday();
        });

        $start_date = Carbon::parse($start_date);
        $end_date = Carbon::parse($end_date);
        $now = Carbon::now();

        // collect dates inbetween start & end date
        // group by weekday vs weekend
        $dates = [];
        $existingDates = [];
        for ($date = $start_date->copy(); $date->lte($end_date); $date->addWeek()) {
            $formattedDate = $date->format('Y-m-d');
            $endOfWeek = $date->copy()->addWeek();

            if ($endOfWeek->greaterThan($end_date)) {
                $endOfWeek = $date->copy()->addDay();
            }

            for ($day = $date->copy(); $day->lte($endOfWeek); $day->addDay()) {
                $dayFormatted = $day->format('Y-m-d');

                if (!in_array($dayFormatted, $existingDates)) {
                    $dates[] = $day->copy();
                    $existingDates[] = $dayFormatted;
                }
            }
        }

        $results = [];
        $lastIndex = 0;
        foreach($dates as $key => $date) {
            $formattedDate = $date->format('Y-m-d');

            if ($key > 0) {
                $lastDate = $dates[($key - 1)];

                if ($lastDate->isWeekendDay() !== $date->isWeekendDay()) {
                    $lastIndex++;
                }
            }

            if (!isset($results[$lastIndex])) {
                if ($now->format('Y-m-d') === $start_date->format('Y-m-d')) {
                    $diff = $date->diffInDays($start_date->copy());

                    if ($date->isWeekendDay()) {
                        $label = 'Weekend of ' . $date->format('F jS');
                        $label .= ' - ' . $date->copy()->addDays(2)->format('jS');

                        if ($diff === 0) {
                            $label = 'This Weekend';
                        } elseif ($diff === 3) {
                            $label = 'Next Weekend';
                        }
                    } else {
                        $label = 'Week of ' . $date->format('F jS');
                        $label .= ' - ' . $date->copy()->addDays(3)->format('jS');

                        if ($diff === 0) {
                            $label = 'This Week';
                        } elseif ($diff == 3) {
                            $label = 'Next Week';
                        }
                    }
                } else {
                    if ($date->isWeekendDay()) {
                        $label = 'Weekend of ' . $date->format('F jS');
                        $label .= ' - ' . $date->copy()->addDays(2)->format('jS');
                    } else {
                        $label = 'Week of ' . $date->format('F jS');
                        $label .= ' - ' . $date->copy()->addDays(3)->format('jS');
                    }
                }

                $results[$lastIndex] = [
                    'label' => $label,
                    'days' => []
                ];
            }

            $daysEvents = $events->filter(function (Event $event) use ($formattedDate) {
                return $event->start_date->format('Y-m-d') === $formattedDate;
            });

            $sorted = $daysEvents->sortBy(function (Event $event) {
                if (empty($event->start_time)) {
                    return -1;
                }

                // format date
                $date = $event->start_date->format('Y-m-d');

                // format time
                $ex = explode(':', $event->start_time);
                $time = (int) $ex[0];

                if (strlen($time) === 1) {
                    $time = '0' . $time;
                }

                if (strstr($ex[1], 'PM')) {
                    $minutes = str_replace(' PM', '', $ex[1]);

                    if ($time != 12) {
                        $time += 12;
                    }
                } else {
                    $minutes = str_replace(' AM', '', $ex[1]);
                }

                $time .= ':' . $minutes . ':00';

                $start_time = Carbon::parse($date . ' ' . $time)->format('U');

                return (int) $start_time;
            });

            $eventsForDay = [];
            foreach($sorted->values()->all() as $event) {
                $eventsForDay[] = $event->toSearchableArray();
            }

            if (!empty($eventsForDay)) {
                $results[$lastIndex]['days'][] = [
                    'date' => $formattedDate,
                    'events' => $eventsForDay
                ];
            }
        }

        // unset days with empty events
        foreach($results as $key => $row) {
            if (empty($row['days'])) {
                unset($results[$key]);
            }
        }

        if (!empty($response)) {
            $response['events'] = $results;
        } else {
            $response = $results;
        }

        return $response;
    }
}
