<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Location;
use App\Http\Controllers\Controller;

use Validator;

class LocationsController extends Controller
{
    /**
    * Subscribe
    *
    * @param Request $reqest
    *
    * @return Response
    */
    public function subscribe(Request $reqest)
    {
        // validate
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:locations,name',
            'category_id' => 'required',
            'address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'website' => 'required'
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

        $location->save();

        if ($request->has('photo')) {
            $location->addMedia($request->file('photo'))->toMediaCollection('locations', 'spaces');
        }

        if ($request->has('tags') && $request->input('tags') !== null) {
            $location->syncTags(explode(',', $request->input('tags')));
        }

        return response()->json(compact('location'));
    }
}
