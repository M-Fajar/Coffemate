<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'owner_id', 'store_id', 'cashier_id', 'total', 'name', 'phone', 'paid', 'delivered', 'invoice'
    ];
}
