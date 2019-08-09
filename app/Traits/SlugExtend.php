<?php

namespace App\Traits;

trait SlugExtend
{
    /**
    * Scope By Slug
    *
    * @param object $query
    * @param string $slug
    *
    * @return object
    */
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', '=', $slug)->first();
    }
}