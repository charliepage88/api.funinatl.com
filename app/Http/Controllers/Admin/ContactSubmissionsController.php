<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\ContactSubmission;
use App\Http\Controllers\Controller;

class ContactSubmissionsController extends Controller
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
        $items = ContactSubmission::orderBy('created_at', 'asc')->paginate(15);

        return view('admin.contact_submissions.index', compact('items'));
    }

    /**
    * Destroy
    *
    * @param ContactSubmission $submission
    *
    * @return Redirect
    */
    public function destroy(ContactSubmission $submission)
    {
        $submission->delete();

        return redirect(route('admin.contact_submissions.index'))->with('is-success', 'Contact submission has been deleted!');
    }
}
