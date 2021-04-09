<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Event;
use App\Report;
use App\Http\Controllers\Controller;

use Validator;

class EventsController extends Controller
{
    /**
    * Submit
    *
    * @param Request $request
    *
    * @return Response
    */
    public function submit(Request $request)
    {
        // validate
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'category_id' => 'required',
            'start_date' => 'required',
            'start_time' => 'required',
            'price' => 'required',
            'website' => 'required',
            'recaptcha_token' => 'required|recaptchav3:login,0.5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // save event
        $event = new Event;

        $event->fill($request->except('photo', 'tags', 'start_date', 'end_date'));

        $event->user_id = 1;
        $event->event_type_id = 1;
        $event->source = 'submission';
        $event->active = false;

        // parse dates
        $event->start_date = Carbon::parse($request->input('start_date'));

        if ($request->input('end_date')) {
            $event->end_date = Carbon::parse($request->input('end_date'));
        }

        // parse start time
        $startTime = Carbon::parse($request->input('start_time'))->format('H:i:s');
        $date = $event->start_date->format('Y-m-d') . ' ' . $startTime;

        $event->start_time = Carbon::parse($date)->format('g:i A');

        // parse end time
        if ($request->input('end_date') && $request->input('end_time')) {
            $endTime = Carbon::parse($request->input('end_time'))->format('H:i:s');
            $date = $event->end_date->format('Y-m-d') . ' ' . $endTime;

            $event->end_time = Carbon::parse($date)->format('g:i A');
        }

        $event->save();

        // add any photo's
        if ($request->has('photo')) {
            $event->addMedia($request->file('photo'))->toMediaCollection('events');
        }

        // add tags
        if ($request->has('tags') && $request->input('tags') !== null) {
            $event->syncTags(explode(',', $request->input('tags')));
        }

        return response()->json(compact('event'));
    }

    /**
    * Search
    *
    * @param Request $request
    *
    * @return Response
    */
    public function search(Request $request)
    {
        // init query
        $now = Carbon::now()->format('Y-m-d');

        $query = Event::search($request->get('query'))
            ->where('start_date', '>=', $now);

        // filters
        if ($request->get('is_family_friendly')) {
            $query->where('is_family_friendly', '=', true);
        }

        if ($request->get('category')) {
            $query->where('category_id', '=', $request->get('category'));
        }

        if ($request->get('location')) {
            $query->where('location_id', '=', $request->get('location'));
        }

        $events = $query->raw();

        if (!empty($events['hits']['hits'])) {
            $events = $events['hits']['hits'];
        } else {
            $events = [];
        }

        // return response
        return response()->json(compact('events'));
    }

    /**
    * Index By Period
    *
    * @param Request $request
    * @param string  $start_date
    * @param string  $end_date
    *
    * @return Response
    */
    public function indexByPeriod(Request $request, string $start_date, string $end_date)
    {
        $response = Report::getEventsByPeriod($start_date, $end_date);

        if (!empty($response['error'])) {
            abort(404, $response['error']);
        }

        return response()->json($response);
    }

    /**
    * Get By Period And Category
    *
    * @param Request $request
    * @param string  $slug
    * @param string  $start_date
    * @param string  $end_date
    *
    * @return Response
    */
    public function getByPeriodAndCategory(Request $request, string $slug, string $start_date, string $end_date)
    {
        $response = Report::getEventsByPeriod($start_date, $end_date, [
            'category' => $slug
        ]);

        if (!empty($response['error'])) {
            abort(404, $response['error']);
        }

        return response()->json($response);
    }

    /**
    * Get By Period And Location
    *
    * @param Request $request
    * @param string  $slug
    * @param string  $start_date
    * @param string  $end_date
    *
    * @return Response
    */
    public function getByPeriodAndLocation(Request $request, string $slug, string $start_date, string $end_date)
    {
        $response = Report::getEventsByPeriod($start_date, $end_date, [
            'location' => $slug
        ]);

        if (!empty($response['error'])) {
            abort(404, $response['error']);
        }

        return response()->json($response);
    }

    /**
    * Get By Period And Tag
    *
    * @param Request $request
    * @param string  $slug
    * @param string  $start_date
    * @param string  $end_date
    *
    * @return Response
    */
    public function getByPeriodAndTag(Request $request, string $slug, string $start_date, string $end_date)
    {
        $response = Report::getEventsByPeriod($start_date, $end_date, [
            'tag' => $slug
        ]);

        if (!empty($response['error'])) {
            abort(404, $response['error']);
        }

        return response()->json($response);
    }

    /**
    * Get By Slug
    *
    * @param Request $request
    * @param string  $slug
    *
    * @return Response
    */
    public function getBySlug(Request $request, $slug)
    {
      $event = Event::where('slug', $slug)->firstOrFail();

      $event = $event->toSearchableArray();

      return response()->json(compact('event'));
    }
}
