<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Category;
use App\Event;
use App\EventType;
use App\Location;

use Storage;

class PopulateDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate data.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*
        // attach default image for all categories
        $categories = Category::all();

        foreach($categories as $category) {
            $filename = 'category-' . $category->slug . '.jpg';
            $fileExists = Storage::disk('public')->exists($filename);

            if ($fileExists) {
                $path = storage_path('app/public') . '/' . $filename;

                // then add the url
                $category->addMedia($path)->toMediaCollection('categories', 'spaces');

                $this->info('Uploaded image for category `' . $category->name . '`');
            }
        }
        */
    }
}
