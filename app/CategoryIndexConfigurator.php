<?php

namespace App;

use ScoutElastic\IndexConfigurator;
use ScoutElastic\Migratable;

class CategoryIndexConfigurator extends IndexConfigurator
{
    use Migratable;

    /**
    * @var string
    */
    protected $name = 'categories_production';

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
                'category_analyzer' => [
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
