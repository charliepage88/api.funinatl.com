<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Location;
use App\Report;
use App\Http\Controllers\Controller;

use Validator;

class LocationsController extends Controller
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
            'name' => 'required|max:255|unique:locations,name',
            'category_id' => 'required',
            'address' => 'required',
            'city' => 'required',
            'zip' => 'required',
            'website' => 'required',
            'recaptcha_token' => 'required|recaptchav3:login,0.5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // save location
        $location = new Location;

        $location->fill($request->except('photo', 'tags'));

        $location->active = false;
        $location->source = 'submission';
        $location->state = 'GA';

        $location->save();

        if ($request->has('photo')) {
            $location->addMedia($request->file('photo'))->toMediaCollection('locations');
        }

        if ($request->has('tags') && $request->input('tags') !== null) {
            $location->syncTags(explode(',', $request->input('tags')));
        }

        return response()->json(compact('location'));
    }

    /**
    * Index
    *
    * @param Request $request
    *
    * @return Response
    */
    public function index(Request $request)
    {
        $locations = Report::getCachedLocations();

        return response()->json(compact('locations'));
    }
}
