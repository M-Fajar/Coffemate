<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Store;
use App\User;
use App\Owner;
use App\Catalog;
use App\Catalog_store;
use App\Http\Resources\Store\StoreResource;
use App\Services\PayUService\Exception;

class StoreController extends Controller
{
    public function __construct()
    {
        $this->store = new Store;
    }

    public function getAllStore(): object
    {
        return StoreResource::collection($this->store->all());
    }

    public function getAllStoreWithFilter(Request $request): object
    {
        $store = $this->store->where(
            $request->get('key'),
            $request->get('comparator') ?? '=',
            $request->get('value')
        )->get();
        return response()->json([
                'status' => true,
                'data' => StoreResource::collection($store)
            ], 200);
    }

    public function getStoreByOwner(Request $request)
    {
        $owner = Owner::where('user_id', $request->user()->id)->get();
        $store = Store::where('owner_id', $owner[0]->id)->get();
        return response()->json([
                'status' => true,
                'data' => StoreResource::collection($store)
            ], 200);
    }

    public function createStore(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'district' => 'required|string',
            'full_address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'array',
            'open_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i',
            'contact' => 'required|string',
            'total_seat' => 'required|integer',
        ]);

        $seat =[];
        $owner = Owner::where('user_id', $request->user()->id)->get();
        $count = $request->total_seat;
        for($i=0; $i < $count; $i++){
            array_push($seat, false);
        }

        $images=[];
        $host = request()->getHttpHost();
        foreach($request->file('images') as $img){
            if (empty($img)) {
                $img = null;
            } else {
                $fileName = time().'.'.$img->getClientOriginalExtension();
                $img->move('images/', $fileName);
                array_push($images, $host.'/'.'images/'.$fileName);
            }
        }
        
        $this->store->store_name   = $request->store_name;
        $this->store->owner_id     = $owner[0]->id;
        $this->store->province     = $request->province;
        $this->store->city         = $request->city;
        $this->store->district     = $request->district;
        $this->store->full_address = $request->full_address;
        $this->store->latitude     = $request->latitude;
        $this->store->longitude    = $request->longitude;
        $this->store->images       = json_encode($images);
        $this->store->open_time    = $request->open_time;
        $this->store->close_time   = $request->close_time;
        $this->store->contact      = $request->contact;
        $this->store->seat         = serialize($seat);

        if ($this->store->save()) {
            try{
                $catalogs = Catalog::where('owner_id', $owner[0]->id)->get();
                foreach($catalogs as $catalog){
                    $catalog_store = new Catalog_store([
                        "store_id"      => $this->store->id,
                        "catalog_id"    => $catalog->id
                    ]);
                    $catalog_store->save();
                    }
                    return response()->json([
                        'status' => true,
                        'message' => 'Success created store'
                    ], 201);
                }catch(\Exception $ex){
                    $this->store = $this->store->find($this->store->id)->delete();
                    return response()->json([
                        'status'    => false,
                        'message' => $ex
                    ], 400);
                }
        }
        return response()->json([
            'status' => false,
            'response_code' => 500,
            'message' => 'Terjadi kesalahan!'
        ]);
    }

    public function findStoreById($id): object
    {
        return new StoreResource($this->store->find($id)) ?? response()->json([
            'status' => false,
            'response_code' => 404,
            'message' => 'Data kedai tidak ditemukan!'
        ]);
    }

    public function updateStore($id, Request $request): object
    {
        $store = $this->store->find($id);
        if ($store) {
            if ($store->owner_id == $request->user()->id) {
                $store->store_name   = $request->store_name;
                $store->province     = $request->address['province'];
                $store->city         = $request->address['city'];
                $store->district     = $request->address['district'];
                $store->full_address = $request->address['full_address'];
                $store->latitude     = $request->address['map_variables']['latitude'];
                $store->longitude    = $request->address['map_variables']['longitude'];
                $store->images       = json_encode($request->images);
                $store->open_time    = $request->open_time;
                $store->close_time   = $request->close_time;
                $store->owner_id     = $request->user()->id;
                $store->contact      = $request->contact;
                $store->cashiers     = json_encode($request->cashiers);
                $store->catalogs     = json_encode($request->catalogs);

                if ($store->save()) {
                    return response()->json([
                        'status' => true,
                        'response_code' => 201,
                        'message' => 'Detail kedai berhasil diubah!',
                        'data' => new StoreResource($store)
                    ]);
                }
            }
            return response()->json([
                'status' => false,
                'response_code' => 401,
                'message' => 'Kedai ini bukan milik anda!'
            ]);
        }
        return response()->json([
            'status' => false,
            'response_code' => 500,
            'message' => 'Terjadi kesalahan!'
        ]);
    }

    public function getSeat($id): object
    {
        $seat = $this->store->find($id)->seat;
        return response()->json([
            'status' => true,
            'data' => unserialize($seat)
        ]);
    }

    public function emptySeat($id, $index): object
    {
        $store = $this->store->find($id);
        $seat = unserialize($store->seat);
        if($index <= count($seat)){
            $seat[$index-1] = false;
            $store->seat = serialize($seat);
            if($store->save()){
                return response()->json([
                    'status' => true,
                    'data' => $seat
                ], 200);
            }
        }
        return response()->json([
                'status' => false,
                'data' => 'Terjadi kesalahan'
            ], 400);
    }

    public function setSeat($id, Request $req): object
    {
        $store = $this->store->find($id);
        $seat =[];
        $count = $req->total_seat;
        for($i=0; $i < $count; $i++){
            array_push($seat, false);
        }
        $store->seat = serialize($seat);
        if($store->save()){
            return response()->json([
                'status' => true,
                'data' => $seat
            ], 200);
        }
        return response()->json([
                'status' => false,
                'data' => 'Terjadi kesalahan'
            ], 400);
    }
    public function emptyAllSeat($id): object
    {
        $store = $this->store->find($id);
        $seat = unserialize($store->seat);
        for($i=0; $i<count($seat); $i++){
            $seat[$i] = false;
        }
        $store->seat = serialize($seat);
        if($store->save()){
            return response()->json([
                'status' => true,
                'data' => $seat
            ], 200);
        }
        return response()->json([
                'status' => false,
                'data' => 'Terjadi kesalahan'
            ], 400);
    }

    public function updateCatalogOnStore($id, Request $request): object
    {
        $store = $this->store->find($id);
        $store->catalogs = json_encode($request->catalogs);
        if ($store->save()) {
            return response()->json([
                'status' => true,
                'response_code' => 200,
                'message' => 'Catalog kedai berhasil diupdate!',
                'data' => new StoreResource($store)
            ]);
        }

        return response()->json([
            'status' => false,
            'response_code' => 500,
            'message' => 'Terjadi kesalahan!'
        ]);
    }

    public function updateCashierOnStore($id, Request $request): object
    {
        $store = $this->store->find($id);
        $store->cashiers = json_encode($request->cashiers);
        if ($store->save()) {
            return response()->json([
                'status' => true,
                'response_code' => 200,
                'message' => 'Kasir kedai berhasil diupdate!',
                'data' => new StoreResource($store)
            ]);
        }

        return response()->json([
            'status' => false,
            'response_code' => 500,
            'message' => 'Terjadi kesalahan!'
        ]);
    }

    public function deleteStore($id, Request $request): object
    {
        $store = $this->store->find($id);
        if ($store) {
            if ($store->owner_id == $request->user()->id) {
                $store_name = $store->name;
                $store->delete();
    
                return response()->json([
                    'status' => true,
                    'response_code' => 200,
                    'message' => 'Kedai '.$store_name.' berhasil dihapus!'
                ]);
            }
            return response()->json([
                'status' => false,
                'response_code' => 401,
                'message' => 'Kedai ini bukan milik anda!'
            ]);
        }
        return response()->json([
            'status' => false,
            'response_code' => 404,
            'message' => 'Data kedai tidak ditemukan!'
        ]);
    }
}
