<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client as Guzzle;
use Illuminate\Http\Request;

use App\User;
use App\Http\Controllers\Controller;

use Validator;

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

    /**
    * Login
    *
    * @param Request $request
    *
    * @return Response
    */
    public function login(Request $request)
    {
        // validate
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // get token
        $client = new Guzzle;

        $response = $client->post(config('app.url') . '/oauth/token', [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => config('services.oauth.client_id'),
                'client_secret' => config('services.oauth.client_secret'),
                'username' => $request->email,
                'password' => $request->password,
                'scope' => '*'
            ]
        ]);

        $response = json_decode((string) $response->getBody(), true);

        if (!empty($response['access_token'])) {
            $token = $response['access_token'];

            // format user
            $user = User::where('email', '=', $request->email)->first();
            $user = $user->toSearchableArray();
        } else {
            $token = null;
            $user = null;
        }

        return response()->json(compact('token', 'user'));
    }

    /**
    * Register
    *
    * @param Request $request
    *
    * @return Response
    */
    public function register(Request $request)
    {
        // validate
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'recaptcha_token' => 'required|recaptchav3:login,0.5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // save user
        $user = new User;

        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);

        $user->save();

        // format user
        $user = $user->toSearchableArray();

        // get token
        $client = new Guzzle;

        $response = $client->post(config('app.url') . '/oauth/token', [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => config('services.oauth.client_id'),
                'client_secret' => config('services.oauth.client_secret'),
                'username' => $request->email,
                'password' => $request->password,
                'scope' => '*'
            ]
        ]);

        $response = json_decode((string) $response->getBody(), true);

        if (!empty($response['access_token'])) {
            $token = $response['access_token'];
        } else {
            $token = null;
        }

        return response()->json(compact('token', 'user'));
    }
}
