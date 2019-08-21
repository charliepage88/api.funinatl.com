<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Category;
use App\Report;
use App\Http\Controllers\Controller;

class CategoriesController extends Controller
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
        $categories = Report::getCachedCategories();

        return response()->json(compact('categories'));
    }
}
