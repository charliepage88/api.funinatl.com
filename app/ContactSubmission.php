<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    /**
    * @var array
    */
    protected $fillable = [
        'name',
        'email',
        'body',
        'reviewed'
    ];

    /**
    * @var array
    */
    protected $casts = [
        'reviewed' => 'boolean'
    ];
}
