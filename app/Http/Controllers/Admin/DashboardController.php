<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Report;
use App\Http\Controllers\Controller;

use Auth;

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
        $stats = Report::getAdminDashboardStats();
        $charts = Report::getAdminDashboardCharts();

        return view('admin.dashboard', compact('stats', 'charts'));
    }

    /**
    * Logout
    *
    * @param Request $request
    *
    * @return Response
    */
    public function logout(Request $request)
    {
        Auth::logout();

        return redirect(route('home'))->with('is-success', 'You have logged out!');
    }
}
