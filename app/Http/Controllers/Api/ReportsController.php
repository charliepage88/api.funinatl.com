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
        $tweetContent = $request->input('tweet_content');
        $tweetable_event_ids = $request->input('tweetable_event_ids');

        $events = Event::whereIn('id', $request->input('event_ids'))->get();

        // set meta for is_tweetable flag
        $setTweetContent = false;

        foreach($events as $event) {
            if (!empty($tweetContent) && $event->hasMeta('tweet_content')) {
                $event->updateMeta('tweet_content', $tweetContent);

                $setTweetContent = true;
            }

            if (in_array($event->id, $tweetable_event_ids)) {
                $event->addOrUpdateMeta('is_tweetable', true);
            } else {
                $event->addOrUpdateMeta('is_tweetable', false);
            }
        }

        // set tweet content
        // if not already set
        if (!$setTweetContent && !empty($tweetContent)) {
            foreach($events as $event) {
                $event->addMeta('tweet_content', $tweetContent);

                $setTweetContent = true;

                break;
            }
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
