<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use App\Event;

use Cache;
use DB;
use Storage;

class RunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run misc tasks.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');

        if (empty($action)) {
            $this->error('No method defined.');

            return false;
        }

        $methodName = Str::camel($action);

        if (method_exists($this, $methodName)) {
            $this->$methodName();
        } else {
            $this->error('Cannot find method name `' . $methodName . '`');
        }
    }

    /**
    * Sync
    *
    * @return void
    */
    private function sync()
    {
        $this->info('RunCommand -> sync');

        // init vars
        $webhookToken = config('services.webhooks.sync.token');
        $webhookUrl = config('services.webhooks.sync.url');
        $headers = [
            'Authorization' => 'Bearer ' . $webhookToken,
            'Accept' => 'application/json'
        ];

        $tables = [
            'categories',
            'events',
            'event_music_bands',
            'event_types',
            'locations',
            'media',
            'meta',
            'music_bands',
            'providers',
            'taggables',
            'tags'
        ];

        $body = [];
        foreach($tables as $table) {
            $body[$table] = [];

            $records = DB::table($table)->get();

            foreach($records as $record) {
                $value = (array) $record;

                $body[$table][] = $value;
            }
        }

        // send request to webhook
        $client = new Guzzle;

        $response = $client->request('POST', $webhookUrl, [
            'headers' => $headers,
            'json' => [
                'body' => $body
            ]
        ]);

        $this->info('Sync Status: ' . $response->getStatusCode());
    }
}
