<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Category;
use App\Event;
use App\Location;
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
        $routes = [];

        // static pages
        $pages = [
            '/',
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
        $categories = Category::isActive()->get();

        foreach($categories as $category) {
            $routes[] = [
                'route' => '/category/' . $category->slug,
                'payload' => [],
                'payload' => $category->toSearchableArray()
            ];
        }

        // events
        $events = Event::shouldShow()->get();

        foreach($events as $event) {
            $routes[] = [
                'route' => '/event/' . $event->slug,
                'payload' => [],
                'payload' => $event->toSearchableArray()
            ];
        }

        // locations
        $locations = Location::isActive()->get();

        foreach($locations as $location) {
            $routes[] = [
                'route' => '/location/' . $location->slug,
                'payload' => [],
                'payload' => $location->toSearchableArray()
            ];
        }

        // tags
        $tags = Tag::all();

        foreach($tags as $tag) {
            $routes[] = [
                'route' => '/tag/' . $tag->slug,
                'payload' => [],
                'payload' => $tag->toSearchableArray()
            ];
        }

        return response()->json(compact('routes'));
    }
}
