<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBook extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_favourite' => 'boolean'
    ];

    public function scopeOnlyFavourite($query) {
        return $query->where('is_favourite', true);
    }
}
