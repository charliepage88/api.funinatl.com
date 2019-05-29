<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use Newsletter;
use Validator;

class NewsletterController extends Controller
{
    /**
    * Subscribe
    *
    * @param Request $reqest
    *
    * @return Response
    */
    public function subscribe(Request $reqest)
    {
        // validate
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'errors' => $validator->errors() ], 422);            
        }

        $isSubscribed = Newsletter::isSubscribed($request->email);

        if (!$isSubscribed) {
            Newsletter::subscribe($request->email);
        }

        return response()->json([ 'status' => true ]);
    }
}
