<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
    * Admin Dashboard
    *
    * @param Request $request
    *
    * @return Response
    */
    public function adminDashboard(Request $request)
    {
        return view('admin.dashboard');
    }
}
