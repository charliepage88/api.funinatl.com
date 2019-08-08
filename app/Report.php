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
    * Get Events Index By Period
    *
    * @param string $start_date
    * @param string $end_date
    *
    * @return EventCollection
    */
    public static function getEventsIndexByPeriod(string $start_date, string $end_date)
    {
        $items = Event::shouldShow()
            ->where('start_date', '>=', $start_date)
            ->where('end_date', '<=', $end_date)
            ->get();

        $start_date = Carbon::parse($start_date);
        $end_date = Carbon::parse($end_date);

        $results = [];
        for ($date = $start_date; $date->lte($end_date); $date->addWeek()) {
            $formattedDate = $date->format('Y-m-d');

        }

        return $events;
    }
}
