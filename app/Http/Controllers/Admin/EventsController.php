<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Category;
use App\Event;
use App\Location;
use App\Http\Controllers\Controller;

class EventsController extends Controller
{
    /**
    * Index
    *
    * @param Request $request
    *
    * @return Response
    */
    public function index(Request $request)
    {
        // init query
        $now = Carbon::now()->format('Y-m-d');

        $events = Event::orderBy('start_date', 'asc')
            ->where('start_date', '>=', $now);

        // filters - tmp solution
        if ($request->get('category_id')) {
            $events->where('category_id', '=', $request->get('category_id'));
        }

        if ($request->get('location_id')) {
            $events->where('location_id', '=', $request->get('location_id'));
        }

        if ($request->get('start_date')) {
            $events->where('start_date', '>=', $request->get('start_date'));
        }

        if ($request->get('end_date')) {
            $events->where('start_date', '<=', $request->get('end_date'));
        }

        // get paginated results
        $events = $events->paginate(15);
        $categories = Category::select([ 'id', 'name' ])->orderBy('name', 'asc')->get();
        $locations = Location::select([ 'id', 'name' ])->orderBy('name', 'asc')->get();

        return view('admin.events.index', compact(
            'events',
            'locations',
            'categories'
        ));
    }

    /**
    * Create
    *
    * @param Request $request
    *
    * @return Redirect|Response
    */
    public function create(Request $request)
    {
        $event = new Event;

        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required|max:255',
                'category_id' => 'required',
                'location_id' => 'required',
                'start_date' => 'required',
                'price' => 'required',
                'start_time' => 'required',
                'website' => 'required'
            ]);

            $event->fill($request->except('photo', 'tags'));

            $event->user_id = 1;
            $event->event_type_id = 1;
            $event->source = 'custom';

            $event->save();

            if ($request->has('photo')) {
                $event->addMedia($request->file('photo'))->toMediaCollection('events', 'spaces');
            }

            if ($request->has('tags') && $request->input('tags') !== null) {
                $event->syncTags(explode(',', $request->input('tags')));
            }

            return redirect(route('admin.events.index'))->with('is-success', 'Event has been created!');
        }

        $categories = Category::orderBy('name', 'asc')->pluck('name', 'id');
        $locations = Location::orderBy('name', 'asc')->pluck('name', 'id');

        return view('admin.events.create', compact('event', 'categories', 'locations'));
    }

    /**
    * Edit
    *
    * @param Request $request
    * @param Event $event
    *
    * @return Redirect|Response
    */
    public function edit(Request $request, Event $event)
    {
        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required|max:255',
                'category_id' => 'required',
                'location_id' => 'required',
                'start_date' => 'required',
                'price' => 'required',
                'start_time' => 'required',
                'website' => 'required'
            ]);

            $event->fill($request->except('photo', 'tags'));

            $event->save();

            if ($request->has('photo')) {
                $event->addMedia($request->file('photo'))->toMediaCollection('events', 'spaces');
            }

            if ($request->has('tags')) {
                if ($request->input('tags') === null) {
                    $event->syncTags([]);
                } else {
                    $event->syncTags(explode(',', $request->input('tags')));
                }
            }

            return redirect(route('admin.events.index'))->with('is-success', 'Event has been saved!');
        }

        $categories = Category::orderBy('name', 'asc')->pluck('name', 'id');
        $locations = Location::orderBy('name', 'asc')->pluck('name', 'id');

        return view('admin.events.edit', compact('event', 'categories', 'locations'));
    }

    /**
    * Destroy
    *
    * @param Event $event
    *
    * @return Redirect
    */
    public function destroy(Event $event)
    {
        $event->delete();

        return redirect(route('admin.event.index'))->with('is-success', 'Event has been deleted!');
    }
}
