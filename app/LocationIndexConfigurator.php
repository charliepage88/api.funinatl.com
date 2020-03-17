<?php

namespace App;

use ScoutElastic\IndexConfigurator;
use ScoutElastic\Migratable;

class LocationIndexConfigurator extends IndexConfigurator
{
    use Migratable;

    /**
    * @var string
    */
    protected $name = 'locations_production';

    /**
     * @var array
     */
    protected $settings = [
        'analysis' => [
            'filter' => [
                'english_possessive_stemmer' => [
                    'type' => 'stemmer',
                    'language' => 'possessive_english'
                ]
            ],
            'analyzer' => [
                'location_analyzer' => [
                    'tokenizer' => 'standard',
                    'filter' => [
                        'english_possessive_stemmer',
                        'lowercase',
                        'stop',
                        'porter_stem'
                    ]
                ]
            ]
        ]
    ];
}
