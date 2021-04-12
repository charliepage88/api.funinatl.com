<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Category;
use App\Event;
use App\Location;
use App\MusicBand;
use App\Report;
use App\Tag;
use App\Http\Controllers\Controller;

use DB;
use Validator;

class MetaController extends Controller
{
  /**
  * Routes
  *
  * @param Request $request
  *
  * @return Response
  */
  public function routes(Request $request)
  {
    $routes = Report::getRoutesList();

    return response()->json(compact('routes'));
  }
}
