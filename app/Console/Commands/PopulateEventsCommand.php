<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte\Client as WebScraper;

use App\Provider;

class PopulateEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape websites to populate events.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $scraper = new WebScraper;

        $provider = Provider::first();

        dd($provider->toArray());

        $crawler = $scraper->request('GET', 'https://www.symfony.com/blog/');
    }
}
