<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Location;
use App\Provider;
use App\Http\Controllers\Controller;

class ProvidersController extends Controller
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
        $providers = Provider::orderBy('name', 'asc')->paginate(15);

        return view('admin.providers.index', compact('providers'));
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
        $provider = new Provider;

        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required|max:255|unique:providers,name',
                'location_id' => 'required',
                'scrape_url' => 'required'
            ]);

            $provider->fill($request->all());

            $provider->save();

            return redirect(route('admin.providers.index'))->with('is-success', 'Provider has been created!');
        }

        $locations = Location::orderBy('name', 'asc')->pluck('name', 'id');

        return view('admin.providers.create', compact('provider', 'locations'));
    }

    /**
    * Edit
    *
    * @param Request $request
    * @param Provider $provider
    *
    * @return Redirect|Response
    */
    public function edit(Request $request, Provider $provider)
    {
        if ($request->getMethod() === 'POST') {
            $request->validate([
                'name' => 'required|max:255|unique:providers,name,' . $provider->id . ',id',
                'location_id' => 'required',
                'scrape_url' => 'required'
            ]);

            $provider->fill($request->all());

            $provider->save();

            return redirect(route('admin.providers.index'))->with('is-success', 'Provider has been saved!');
        }

        $locations = Location::orderBy('name', 'asc')->pluck('name', 'id');

        return view('admin.providers.edit', compact('provider', 'locations'));
    }

    /**
    * Destroy
    *
    * @param Provider $provider
    *
    * @return Redirect
    */
    public function destroy(Provider $provider)
    {
        $provider->delete();

        return redirect(route('admin.provider.index'))->with('is-success', 'Provider has been deleted!');
    }
}
