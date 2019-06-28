<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Category;
use App\Location;
use App\Http\Controllers\Controller;

class LocationsController extends Controller
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
        $locations = Location::orderBy('name', 'asc')->paginate(15);

        return view('admin.locations.index', compact('locations'));
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
        $location = new Location;

        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required|max:255|unique:locations,name',
                'category_id' => 'required',
                'address' => 'required',
                'city' => 'required',
                'state' => 'required',
                'zip' => 'required',
                'website' => 'required'
            ]);

            $location->fill($request->except('photo', 'tags'));

            $location->source = 'admin';

            $location->save();

            if ($request->has('photo')) {
                $location->addMedia($request->file('photo'))->toMediaCollection('locations', 'spaces');
            }

            if ($request->has('tags') && $request->input('tags') !== null) {
                $location->syncTags(explode(',', $request->input('tags')));
            }

            return redirect(route('admin.locations.index'))->with('is-success', 'Location has been created!');
        }

        $categories = Category::orderBy('name', 'asc')->pluck('name', 'id');

        return view('admin.locations.create', compact('location', 'categories'));
    }

    /**
    * Edit
    *
    * @param Request $request
    * @param Location $location
    *
    * @return Redirect|Response
    */
    public function edit(Request $request, Location $location)
    {
        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required|max:255|unique:locations,name,' . $location->id . ',id',
                'category_id' => 'required',
                'address' => 'required',
                'city' => 'required',
                'state' => 'required',
                'zip' => 'required',
                'website' => 'required'
            ]);

            $location->fill($request->except('photo', 'tags'));

            $location->save();

            if ($request->has('photo')) {
                $location->addMedia($request->file('photo'))->toMediaCollection('locations', 'spaces');
            }

            if ($request->has('tags')) {
                if ($request->input('tags') === null) {
                    $location->syncTags([]);
                } else {
                    $location->syncTags(explode(',', $request->input('tags')));
                }
            }

            return redirect(route('admin.locations.index'))->with('is-success', 'Location has been saved!');
        }

        $categories = Category::orderBy('name', 'asc')->pluck('name', 'id');

        return view('admin.locations.edit', compact('location', 'categories'));
    }

    /**
    * Destroy
    *
    * @param Location $location
    *
    * @return Redirect
    */
    public function destroy(Location $location)
    {
        $location->delete();

        return redirect(route('admin.location.index'))->with('is-success', 'Location has been deleted!');
    }
}
