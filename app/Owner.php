<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{

    public function user()
    {
    	return $this->belongsTo('App\User');
    }

    protected $fillable = [
        'user_id', 'name', 'address', 'phone', 'province', 'city', 'district', 'ktp'
    ];
}
