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
                'upcoming'          => 0,
                'upcoming_increase' => 0
            ],

            'users' => [
                'total'        => 0,
                'new'          => 0,
                'new_increase' => 0
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
                'options' => [],
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
}
