<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;

class User extends Authenticatable
{
    use HasApiTokens,
        HasRolesAndAbilities,
        Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime'
    ];

    /**
    * Events
    *
    * @return Collection
    */
    public function events()
    {
        return $this->hasMany(Event::class, 'user_id');
    }

    /**
     * To Searchable Array
     *
     * @return array
     */
    public function toSearchableArray()
    {
        // user data
        $fields = [
            'id',
            'name',
            'email'
        ];

        $user = [];
        foreach($fields as $field) {
            $user[$field] = $this->$field;
        }

        $user['created_at'] = $this->created_at->toAtomString();
        $user['updated_at'] = $this->updated_at->toAtomString();

        return $user;
    }

    /**
     * Get Formatted Array
     *
     * @return array
     */
    public function getFormattedArray()
    {
        // user data
        $fields = [
            'id',
            'name',
            'email'
        ];

        $user = [];
        foreach($fields as $field) {
            $user[$field] = $this->$field;
        }

        $user['created_at'] = $this->created_at->toAtomString();
        $user['updated_at'] = $this->updated_at->toAtomString();

        return $user;
    }
}
