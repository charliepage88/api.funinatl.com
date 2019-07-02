<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\ContactSubmission;
use App\Http\Controllers\Controller;

use Validator;

class ContactSubmissionsController extends Controller
{
    /**
    * Submit
    *
    * @param Request $request
    *
    * @return Response
    */
    public function submit(Request $request)
    {
        // validate
        $validator = Validator::make($request->all(), [
            'name'  => 'required|max:255',
            'email' => 'required|max:255',
            'body'  => 'required',
            'recaptcha_token' => 'required|recaptchav3:login,0.5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // save submission
        $submission = new ContactSubmission;

        $submission->fill($request->all());

        $submission->reviewed = false;

        $submission->save();

        return response()->json(compact('submission'));
    }
}
