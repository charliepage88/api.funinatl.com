<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\ContactSubmission;
use App\Http\Controllers\Controller;
use App\Notifications\ReplyToContactSubmission;

use Notification;

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
    * Review
    *
    * @param Request $request
    * @param ContactSubmission $submission
    *
    * @return Response|Redirect
    */
    public function review(Request $request, ContactSubmission $submission)
    {
        if ($request->isMethod('post')) {
            // mark as reviewed
            $submission->reviewed = true;

            $submission->save();

            // send reply if message field not empty
            if ($request->input('reply')) {
                $message = 'Reply has been sent to `' . $submission->email . '` and marked as reviewed.';

                // send notification
                Notification::route('mail', $submission->email)
                    ->notify(new ReplyToContactSubmission($submission, $request->input('reply')));
            } else {
                $message = 'Contact form submission has been marked as reviewed.';
            }

            return redirect(route('admin.contact_submissions.index'))->with('is-success', $message);
        }

        return view('admin.contact_submissions.review', compact('submission'));
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
