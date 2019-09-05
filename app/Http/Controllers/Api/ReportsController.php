<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Event;
use App\Report;
use App\Http\Controllers\Controller;

class ReportsController extends Controller
{
    /**
    * Daily Tweets
    *
    * @param Request $request
    *
    * @return string
    */
    public function dailyTweets(Request $request)
    {
        $report = Report::getReportDailyTweets($request->all());

        return response()->json(compact('report'));
    }

    /**
    * Update Daily Tweets
    *
    * @param Request $request
    *
    * @return string
    */
    public function updateDailyTweets(Request $request)
    {
        // update daily tweets data
        $events = Event::whereIn('id', $request->input('event_ids'))->get();

        foreach($events as $event) {

        }

        // get report data
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        $report = Report::getReportDailyTweets($filters);

        return response()->json(compact('report'));
    }
}
