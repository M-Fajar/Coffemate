<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    public function stores()
    {
    	return $this->belongsToMany('App\Store');
    }
    
    protected $fillable = [
        'category_id', 'menu_name', 'description', 'images', 'price', 'owner_id'
    ];
}
