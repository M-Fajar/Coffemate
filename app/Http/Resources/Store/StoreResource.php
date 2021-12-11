<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Store\CatalogResource;
use stdClass;

class StoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // $catalogs = [];
        // foreach(json_decode($this->catalogs) as $catalog) {
        //     $data = \App\Catalog::find($catalog);
        //     if ($data) {
        //         array_push($catalogs, new CatalogResource($data));
        //     }
        // }

        // $cashiers = [];
        // foreach(json_decode($this->cashiers) as $cashier) {
        //     $data = \App\User::find($cashier);
        //     if ($data) {
        //         array_push($cashiers, $data);
        //     }
        // }

        $images = json_decode($this->images);
        $image = new stdClass;
        $newimg = [];
        foreach ($images as $key => $img) {
            $image->src = $img;
            array_push($newimg, $image);
        }

        return [
            'id' => $this->id,
            // 'owner_data' => [
            //     \App\User::find($this->owner_id) ?: 'tidak ada owner'
            // ],
            'store_name' => $this->store_name,
            'address'    => [
                'province' => $this->province,
                'city'     => $this->city,
                'district' => $this->district,
                'full_address'  => $this->full_address,
                'map_variables' => [
                    'latitude'  => $this->latitude,
                    'longitude' => $this->longitude,
                ]
            ],
            'images'        => $newimg,
            'open_time'     => $this->open_time,
            'close_time'    => $this->close_time,
            'contact'       => $this->contact,
            'seat'          => unserialize($this->seat)
        ];
    }
}