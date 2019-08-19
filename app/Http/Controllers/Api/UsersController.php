<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\User;
use App\Http\Controllers\Controller;

class UsersController extends Controller
{
    /**
    * Show
    *
    * @param Request $request
    *
    * @return Response
    */
    public function show(Request $request)
    {
        return $request->user();
    }
}
