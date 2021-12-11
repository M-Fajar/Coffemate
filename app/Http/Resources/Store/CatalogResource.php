<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;
class CatalogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $image = new stdClass;
        $images = json_decode($this->images);
        $newImg = [];
        foreach($images as $key => $img) {
            $image->src = $img;
            // array_push($newImg, $images);
        }
        return [
            'id' => $this->id,
            'category' => $this->name,
            'menu_name' => $this->menu_name,
            'description' => $this->description,
            'images' => $image,
            'price' => $this->price,
            'stock' => $this->stock,
            'discount' => $this->discount
        ];
    }
}
