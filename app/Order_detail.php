<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order_detail extends Model
{
    protected $fillable = [
        'order_id', 'catalog_id', 'quantity', 'price', 'discount', 'done', 'paid'
    ];
}
