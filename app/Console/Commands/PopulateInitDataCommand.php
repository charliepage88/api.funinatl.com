<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Category;
use App\EventType;
use App\Location;

class PopulateInitDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate initial data.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // create categories
        $categories = [
            'Music',
            'Food/Drinks',
            'Comedy',
            'Arts & Theatre'
        ];

        foreach($categories as $row) {
            $find = Category::where('name', '=', $row)->first();

            if (empty($find)) {
                $category = new Category;

                $category->name = $row;
                $category->active = true;
                $category->is_default = ($row === 'Music');

                $category->save();

                $this->info('Created Category `' . $row . '`');
            }
        }

        // create event types
        $types = [
            'Festival',
            'Event',
            'Special',
            'On-Going'
        ];

        foreach($types as $type) {
            $find = EventType::where('name', '=', $type)->first();

            if (empty($find)) {
                $eventType = new EventType;

                $eventType->name = $type;

                $eventType->save();

                $this->info('Created EventType `' . $type . '`');
            }
        }

        // create locations
        $locations = [
            [
                'name' => 'The Earl',
                'website' => 'http://badearl.com',
                'address' => '488 Flat Shoals Ave SE',
                'city' => 'Atlanta',
                'state' => 'GA',
                'zip' => '30316',
                'description' => "Originally opened by John Searson in 1999, a long-time resident of Atlanta, The Earl has become one of the favorites in East Atlanta and overall a go-to bar/restaurant for the atmosphere and music. Unlike some bars, one of the best burgers in the city actually resides at The Earl. Other options are tasty too, vegans and vegetarians are allowed to come here!

What really sets them apart is the type of people you see here. You walk up and there's a nice cozy outside patio area. Walk inside and there's a plethora of tables and a good sized bar area. The people here are so nice and down to earth, the bartenders obviously have plenty of regulars here.

Smoking is allowed in the bar area and on occasion inside the music room, but typically it's smoke-free for shows. Talking about shows, there's so much to say! They have a tiny little bar area back there, but luckily a ton of room overall to see your band play. The Earl has the most intimate music area at least that I've seen – you can get super close to the band, it's up to you how much hearing loss you're okay with.

The sound is excellent, unfortunately the awesome sound guy they have now is leaving, so hopefully the quality can keep up. Nonetheless, The Earl has great acts pretty consistently and is such a great place to see music at, one of the best in the city.",
                'tags' => [
                    'dive bar',
                    'indie music'
                ]
            ],
            [
                'name' => 'Northside Tavern',
                'website' => 'http://northsidetavern.com',
                'address' => '1058 Howell Mill Rd NW',
                'city' => 'Atlanta',
                'state' => 'GA',
                'zip' => '30318',
                'description' => 'World famous Blues venue, live music 7 nights a week.',
                'tags' => [
                    'dive bar',
                    'blues music'
                ]
            ]
        ];

        foreach($locations as $row) {
            $find = Location::where('name', '=', $row['name'])->first();

            if (empty($find)) {
                $tags = $row['tags'];

                unset($row['tags']);

                $location = new Location;

                $location->fill($row);

                $location->save();

                $location->syncTags($tags);

                $this->info('Created Location `' . $row['name'] . '`');
            }
        }
    }
}
