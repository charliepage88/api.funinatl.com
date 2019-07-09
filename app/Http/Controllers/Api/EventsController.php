<?php

namespace App\Http\Controllers\Api;

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
        $events = Event::search($request->get('query'));

        // filters
        if ($request->get('is_family_friendly')) {
            $events->where('is_family_friendly', '=', true);
        }

        if ($request->get('category')) {
            $events->where('category_id', '=', $request->get('category'));
        }

        if ($request->get('location')) {
            $events->where('location_id', '=', $request->get('location'));
        }

        $events = $events->get();

        // return response
        return response()->json(compact('events'));
    }
}
