<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UrlMapping extends Model
{
    protected $fillable = [
        'slug',
        'url',
        'admin_hash',
        'email',
    ];
}
