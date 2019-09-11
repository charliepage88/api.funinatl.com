<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Category;
use App\Event;
use App\EventType;
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

        if ($source = $request->get('source')) {
            $events->where('source', '=', $source);
        }

        // get paginated results
        $events = $events->paginate(15);

        // get related data
        $data = $this->getRelatedData();

        $locations = $data['locations'];
        $categories = $data['categories'];
        $eventTypes = $data['eventTypes'];

        return view('admin.events.index', compact(
            'events',
            'locations',
            'categories',
            'eventTypes'
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
                'event_type_id' => 'required',
                'start_date' => 'required',
                'price' => 'required',
                'start_time' => 'required',
                'website' => 'required'
            ]);

            $event->fill($request->except('photo', 'tags', 'bands'));

            $event->user_id = 1;
            $event->event_type_id = 1;
            $event->source = 'custom';

            if (!$request->has('active')) {
                $event->active = false;
            }

            $event->save();

            if ($request->has('photo')) {
                $event->addMedia($request->file('photo'))->toMediaCollection('events');
            }

            if ($request->has('tags') && $request->input('tags') !== null) {
                $event->syncTags(explode(',', $request->input('tags')));
            }

            if ($request->has('bands')) {
                $bands = $request->input('bands');

                if (!empty($bands)) {
                    $event->syncBands(explode(',', $bands));
                }

                $event->getFirstBandWithImage();
            }

            return redirect(route('admin.events.index'))->with('is-success', 'Event has been created!');
        }

        // get related data
        $data = $this->getRelatedData();

        $locations = $data['locations'];
        $categories = $data['categories'];
        $eventTypes = $data['eventTypes'];

        return view('admin.events.create', compact(
            'event',
            'categories',
            'locations',
            'eventTypes'
        ));
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
                'event_type_id' => 'required',
                'start_date' => 'required',
                'price' => 'required',
                'start_time' => 'required',
                'website' => 'required'
            ]);

            $event->fill($request->except('photo', 'tags', 'bands'));

            if (!$request->has('featured')) {
                $event->featured = false;
            }

            if (!$request->has('is_family_friendly')) {
                $event->is_family_friendly = false;
            }

            if (!$request->has('active')) {
                $event->active = false;
            }

            $event->save();

            if ($request->has('photo')) {
                $event->addMedia($request->file('photo'))->toMediaCollection('events');
            }

            if ($request->has('tags')) {
                if ($request->input('tags') === null) {
                    $event->syncTags([]);
                } else {
                    $event->syncTags(explode(',', $request->input('tags')));
                }
            }

            if ($request->has('bands')) {
                $bands = $request->input('bands');

                if (empty($bands)) {
                    $event->syncBands([]);
                } else {
                    $event->syncBands(explode(',', $bands));
                }
            }

            $event->getFirstBandWithImage();

            return redirect(route('admin.events.index'))->with('is-success', 'Event has been saved!');
        }

        // get related data
        $data = $this->getRelatedData();

        $locations = $data['locations'];
        $categories = $data['categories'];
        $eventTypes = $data['eventTypes'];

        return view('admin.events.edit', compact(
            'event',
            'categories',
            'locations',
            'eventTypes'
        ));
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

    /**
    * Get Related Data
    *
    * @return array
    */
    private function getRelatedData()
    {
        $categories = Category::orderBy('name', 'asc')->pluck('name', 'id')->toArray();
        $locations = Location::orderBy('name', 'asc')->pluck('name', 'id')->toArray();
        $eventTypes = EventType::orderBy('name', 'asc')->pluck('name', 'id')->toArray();

        return compact('categories', 'locations', 'eventTypes');
    }
}
