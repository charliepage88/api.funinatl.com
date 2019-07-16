<?php

namespace App;

use ScoutElastic\IndexConfigurator;
use ScoutElastic\Migratable;

class MusicBandIndexConfigurator extends IndexConfigurator
{
    use Migratable;

    /**
    * @var string
    */
    protected $name = 'music_bands';

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
                'band_analyzer' => [
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