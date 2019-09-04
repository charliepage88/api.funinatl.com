<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Report;
use App\Http\Controllers\Controller;

class ReportsController extends Controller
{
    /**
    * Daily Tweets
    *
    * @param Request $request
    *
    * @return Response
    */
    public function dailyTweets(Request $request)
    {
        $report = Report::getReportDailyTweets($request->all());

        return view('admin.reports.daily_tweets', compact('report'));
    }
}
