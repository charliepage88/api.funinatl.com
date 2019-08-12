<?php

namespace App\Console\Commands;

use Carbon\Carbon;
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
        
    }
}
