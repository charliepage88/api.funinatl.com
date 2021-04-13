<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\MusicBand;
use App\Http\Controllers\Controller;

class MusicBandsController extends Controller
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
    $bands = MusicBand::paginate(15);

    return view('admin.bands.index', compact('bands'));
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
    $band = new MusicBand;

    if ($request->getMethod() === 'POST') {
      $request->validate([
        'name' => 'required|max:255'
      ]);

      $band->fill($request->all());

      $band->save();

      return redirect(route('admin.bands.index'))->with('is-success', 'Band has been created!');
    }

    return view('admin.bands.create', compact('band'));
  }

  /**
  * Edit
  *
  * @param Request   $request
  * @param MusicBand $band
  *
  * @return Redirect|Response
  */
  public function edit(Request $request, MusicBand $band)
  {
    if ($request->getMethod() === 'POST') {
      $request->validate([
        'name' => 'required|max:255'
      ]);

      $band->name = $request->input('name');

      $band->save();

      return redirect(route('admin.bands.index'))->with('is-success', 'Band has been saved!');
    }

    return view('admin.bands.edit', compact('band'));
  }

  /**
  * Destroy
  *
  * @param MusicBand $band
  *
  * @return Redirect
  */
  public function destroy(MusicBand $band)
  {
    $band->delete();

    return redirect(route('admin.bands.index'))->with('is-success', 'Band has been deleted!');
  }
}
