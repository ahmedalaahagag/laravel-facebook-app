<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fb_id','name', 'access_token', 'is_active','profile_picture'
    ];

    protected $hidden = [
      'created_at','updated_at'
    ];

}
