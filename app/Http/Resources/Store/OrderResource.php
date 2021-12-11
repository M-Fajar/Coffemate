<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Store\CatalogResource;
use App\Cashier;
use App\Owner;
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'cashier' => Cashier::find($this->cashier_id)->name?? Owner::find($this->cashier_id)->name?? null,
            'invoice' => $this->invoice,
            'seat'  => $this->seat,
            'customer' => $this->name,
            'phone' => $this->phone,
            'total' => $this->total,
            'paid' => $this->paid,
            'created_at' => $this->created_at,
            'data' => 
                \App\Order_detail::join('catalogs', 'catalogs.id', 'order_details.catalog_id')
                ->where('order_id', '=',$this->id)
                ->select('order_details.id as id', 'menu_name as name', 'quantity', 'done', 'discount', 'order_details.price', 'paid')
                ->get() ?: 'tidak ada detail'
            ,
        ];
    }
}