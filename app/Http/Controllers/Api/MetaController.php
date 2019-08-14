<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Category;
use App\Event;
use App\Location;
use App\Report;
use App\Tag;
use App\Http\Controllers\Controller;

use Validator;

class MetaController extends Controller
{
    /**
    * Routes
    *
    * @param Request $request
    *
    * @return Response
    */
    public function routes(Request $request)
    {
        // init vars
        $routes = [];
        $categories = Category::isActive()->get();
        $locations = Location::isActive()->get();
        $tags = Tag::all();

        // get events for home page & _slug pages

        // get start date/end date
        $now = Carbon::now();

        $start_date = $now->copy()->format('Y-m-d');
        $end_date = $now->copy()->addWeeks(2)->format('Y-m-d');

        $payload = Report::getEventsByPeriod($start_date, $end_date);

        $routes[] = [
            'route' => '/',
            'payload' => [
                'eventsByPeriod' => $payload,
                'locations' => $locations->getMongoArray(false),
                'categories' => $categories->getMongoArray(false)
            ]
        ];

        // static pages
        $pages = [
            '/about',
            '/contact',
            '/get-listed',
            '/submit-event',
            '/subscribe'
        ];

        foreach($pages as $page) {
            $routes[] = [
                'route' => $page,
                'payload' => []
            ];
        }

        // categories
        foreach($categories as $category) {
            $payload = Report::getEventsByPeriod($start_date, $end_date, [
                'category' => $category->slug
            ]);

            $routes[] = [
                'route' => '/category/' . $category->slug,
                'payload' => [
                    'eventsByCategory' => $payload
                ]
            ];
        }

        // events
        $events = Event::shouldShow()->get();

        foreach($events as $event) {
            $routes[] = [
                'route' => '/event/' . $event->slug,
                'payload' => [
                    'eventBySlug' => $event->getMongoArray(false)
                ]
            ];
        }

        // locations
        foreach($locations as $location) {
            $payload = Report::getEventsByPeriod($start_date, $end_date, [
                'location' => $location->slug
            ]);

            $routes[] = [
                'route' => '/location/' . $location->slug,
                'payload' => [
                    'eventsByLocation' => $payload
                ]
            ];
        }

        // tags
        foreach($tags as $tag) {
            $payload = Report::getEventsByPeriod($start_date, $end_date, [
                'tag' => $tag->slug
            ]);

            $routes[] = [
                'route' => '/tag/' . $tag->slug,
                'payload' => [
                    'eventsByTag' => $payload
                ]
            ];
        }

        return response()->json(compact('routes'));
    }
}
