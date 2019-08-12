<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;

class WebhooksController extends Controller
{
    /**
    * Sync
    *
    * @param Request $request
    *
    * @return Response
    */
    public function sync(Request $request)
    {
        // get secret token and body data
        $token = request()->header('Authorization');
        $body = $request->input('body');

        \Log::info($body);
        \Log::info($token);
        die();

        if (!empty($token) && !empty($body)) {
            $token = str_replace('Bearer ', '', $token);

            if ($token !== env('WEBHOOK_SYNC_TOKEN')) {
                abort(403, 'Invalid token `' . env('WEBHOOK_SYNC_TOKEN') . '`');
            } else {
                foreach($body as $table => $records) {
                    DB::table($table)->truncate();

                    $records = collect($records);

                    foreach($records->chunk(25) as $rows) {
                        DB::table($table)->insert($rows);

                        \Log::info('Table `' . $table . '` inserted ' . count($rows) . ' records.');
                    }
                }

                return response()->json([ 'status' => true ]);
            }
        } else {
            abort(404);
        }
    }
}
