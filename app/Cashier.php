<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cashier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'store_id', 'owner_id'
    ];
}
