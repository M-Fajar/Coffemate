<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    public function catalogs()
    {
    	return $this->belongsToMany('App\Catalog');
    }

    protected $fillable = [
        'id', 'owner_id', 'store_name', 'province', 'city', 'district', 'full_address', 'latitude', 'longitude', 'images', 'open_time', 'close_time', 'contact', 'cashiers'
    ];
}
