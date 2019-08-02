<?php

namespace App\Helpers;

class SiteHelper
{
    /**
    * @var array
    */
    public static $states = [
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'DC' => 'District of Columbia',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming'
    ];

    /**
    * Trigger Build
    *
    * @return string
    */
    public static function triggerBuild()
    {
        $response = null;
        try {
            $url = 'https://circleci.com/api/v1.1/project/github/charliepage88/funinatl.com/build?circle-token=' . env('CIRCLE_CI_TOKEN');

            $vars = [
                'branch' => 'master'
            ];

            \Log::info('triggerBuild');
            \Log::info($url);
            
            $vars = json_encode($vars);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);  //Post Fields
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $headers = [
                'Content-Type: application/json'
            ];

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);

            \Log::info($response);

            curl_close($ch);
        } catch (\Exception $e) {
            \Log::info('Error with triggerBuild');
            \Log::info($e->getMessage());
        }

        return $response;
    }

    /**
     * Cdn Asset
     * Get the path to a versioned Mix file.
     *
     * @param  string  $path
     * @param  string  $manifestDirectory
     * @return \Illuminate\Support\HtmlString|string
     *
     * @throws \Exception
     */
    public static function cdn_asset($path, $manifestDirectory = '')
    {
        $mixPath = mix($path, $manifestDirectory);
        $cdnUrl  = env('ASSETS_S3_URL');
        $env     = config('app.env');

        // Reference CDN assets only in production or staging environemnt.
        // In other environments, we should reference locally built assets.
        if ($cdnUrl && ($env === 'production' || $env === 'staging')) {
            $mixPath = $cdnUrl . $mixPath;
        }

        return $mixPath;
    }
}
