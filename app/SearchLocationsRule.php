<?php

namespace App;

use ScoutElastic\SearchRule;

class SearchLocationsRule extends SearchRule
{
    /**
     * Build Highlight Payload
     *
     * @return array
     */
    public function buildHighlightPayload()
    {
        return [
            'order' => 'score',
            'fields' => [
                'name' => [
                    'type' => 'plain'
                ]
            ]
        ];
    }

    /**
     * Build Query Payload
     *
     * @return array
     */
    public function buildQueryPayload()
    {
        // autocomplete-like query
        $query = $this->builder->query;

        return [
            'must' => [
                // 'match' => [
                //     'name' => [
                //         'query' => $query,
                //         'fuzziness' => 0,
                //         'operator' => 'and'
                //     ]
                // ]
                'multi_match' => [
                    'query' => $query,
                    'fuzziness' => 0,
                    'operator' => 'or',
                    'fields' => [
                        'name',
                        'tags.name'
                    ],
                    'tie_breaker' => 0.3
                ]
            ]
        ];
    }

    /**
    * Keyword Replacements
    *
    * @return array
    */
    public static function keywordReplacements()
    {
        return [
            'beat' => 'beet',
            'beats' => 'beet'
        ];
    }
}
