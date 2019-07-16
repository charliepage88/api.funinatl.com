<?php

namespace App;

use ScoutElastic\IndexConfigurator;
use ScoutElastic\Migratable;

class EventIndexConfigurator extends IndexConfigurator
{
    use Migratable;

    /**
    * @var string
    */
    protected $name = 'events';

    /**
     * @var array
     */
    protected $settings = [
        'analysis' => [
            'filter' => [
                'english_possessive_stemmer' => [
                    'type' => 'stemmer',
                    'language' => 'possessive_english'
                ],

                'autocomplete_filter' => [
                    'type' => 'edge_ngram',
                    'min_gram' => 3,
                    'max_gram' => 20,
                ]
            ],
            'analyzer' => [
                'event_analyzer' => [
                    'tokenizer' => 'standard',
                    'filter' => [
                        'english_possessive_stemmer',
                        'lowercase',
                        'stop',
                        'porter_stem'
                    ]
                ],

                'autocomplete' => [
                    'type' => 'custom',
                    'tokenizer' => 'standard',
                    'filter' => [
                        'lowercase',
                        'autocomplete_filter'
                    ]
                ]
            ]
        ]
    ];
}
