<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Event;
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

        $event->fill($request->except('photo', 'tags'));

        $event->user_id = 1;
        $event->event_type_id = 1;
        $event->source = 'submission';
        $event->active = false;

        // parse start time
        if ($request->input('start_date') && $event->has('start_time')) {
            $date = $request->input('start_date') . ' ' . $request->input('start_time');

            $event->start_time = Carbon::parse($date)->format('g:i A');
        }

        // parse end time
        if ($request->input('end_date') && $event->has('end_time')) {
            $date = $request->input('end_date') . ' ' . $request->input('end_time');

            $event->end_time = Carbon::parse($date)->format('g:i A');
        }

        $event->save();

        if ($request->has('photo')) {
            $event->addMedia($request->file('photo'))->toMediaCollection('events', 'spaces');
        }

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
        $query = Event::shouldShow();

        // filters
        if ($request->has('query')) {
            $query->search($request->get('query'));
        }

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

        // return response
        return response()->json(compact('events'));
    }
}
