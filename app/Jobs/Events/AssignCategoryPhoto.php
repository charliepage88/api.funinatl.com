<?php

namespace App\Jobs\Events;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Event;

use Storage;

class AssignCategoryPhoto implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
    * @var Event
    */
    public $event;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // init var
        $event = $this->event;

        // method to replace photo for event
        // with category default photo
        try {
            \Log::info('replacePhotoWithDefault -> start -> ' . $event->id);

            // get url
            $categoryPhotoUrl = $event->category->photo_url;

            \Log::info($categoryPhotoUrl);

            // get category image
            $contents = file_get_contents($categoryPhotoUrl);

            \Log::info('Image Size: ' . strlen($contents));

            // store locally
            $filename = $event->id . '-' . $event->slug . '.jpg';
            $tmpPath = storage_path('app') . '/' . $filename;

            \Log::info($filename . ' :: ' . $tmpPath);

            Storage::disk('local')->put($filename, $contents);

            \Log::info('File stored locally...');

            // then attach file
            $event->addMedia($tmpPath)->toMediaCollection('events');

            \Log::info('replacePhotoWithDefault -> done -> ' . $event->id);
            \Log::info('---');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }
}
