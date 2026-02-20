<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\BrowserKit\HttpBrowser as WebScraper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

use App\Provider;

use DB;

class TestCrawlersCommand extends PopulateEventsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:test-crawlers {provider? : Optional provider slug to test a single provider}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all provider crawlers — reports event counts only, no jobs dispatched, no data saved.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Intercept all job dispatches — nothing hits the queue
        Bus::fake();

        $this->today = Carbon::today()->startOfDay();

        $providers = Provider::isActive()->orderBy('id')->get();

        // Optionally filter to a single provider by slug
        $filterSlug = $this->argument('provider');
        if ($filterSlug) {
            $providers = $providers->where('slug', $filterSlug)->values();

            if ($providers->isEmpty()) {
                $this->error('No active provider found with slug: ' . $filterSlug);
                $this->line('Available slugs: ' . Provider::isActive()->pluck('slug')->join(', '));
                return 1;
            }
        }

        $scraper    = new WebScraper;
        $spotify    = $this->initSpotify();
        $realOutput = $this->output;
        $nullOutput = new OutputStyle(new ArrayInput([]), new NullOutput());

        $results = [];
        $passed  = 0;
        $failed  = 0;

        foreach ($providers as $provider) {
            $name         = $provider->name;
            $providerName = str_replace(['"', "'"], '', $name);
            $methodName   = Str::camel('provider' . $providerName);

            $realOutput->writeln('<comment>── ' . $name . '</comment>');

            if (!method_exists($this, $methodName)) {
                $results[] = [$name, '<fg=yellow>MISSING</>', '—', '—', 'No method: ' . $methodName];
                $failed++;
                continue;
            }

            $started = microtime(true);

            // Silence all internal $this->info() / $this->error() output from
            // the provider method, then restore after so our own output works.
            $this->setOutput($nullOutput);

            DB::beginTransaction();

            try {
                $events  = $this->$methodName($provider, $scraper, $spotify);
                $elapsed = round(microtime(true) - $started, 2);
                $count   = is_array($events) ? count($events) : 0;

                $results[] = [$name, '<fg=green>OK</>', $count, $elapsed . 's', ''];
                $passed++;
            } catch (\Exception $e) {
                $elapsed   = round(microtime(true) - $started, 2);
                $results[] = [$name, '<fg=red>ERROR</>', '—', $elapsed . 's', Str::limit($e->getMessage(), 80)];
                $failed++;
            } finally {
                DB::rollBack();
                $this->setOutput($realOutput);
            }
        }

        $this->line('');
        $this->table(
            ['Provider', 'Status', 'Events Found', 'Time', 'Error'],
            $results
        );

        $this->line('');
        $this->info($passed . ' passed, ' . $failed . ' failed out of ' . count($results) . ' providers.');

        return $failed > 0 ? 1 : 0;
    }
}
