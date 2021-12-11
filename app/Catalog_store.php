<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Catalog_store extends Model
{
    protected $table = 'catalog_store';

    protected $fillable = [
        'store_id', 'catalog_id', 'discount', 'stock'
    ];
}
