<?php

namespace App\Http\Controllers\Owner;

use DB;
use stdClass;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Owner;
use App\Catalog;
use App\Store;
use App\Catalog_store;
use App\Http\Resources\Store\CatalogResource;
use App\Services\PayUService\Exception;

class CatalogController extends Controller
{
    public function __construct()
    {
        $this->catalog = new Catalog;
    }

    public function getAllCatalog(): object
    {
        $catalog = Catalog::join('categorys', 'categorys.id', 'catalogs.category_id')
        ->select('categorys.name', 'catalogs.*')
        // ->join('catalog_store', 'catalogs.id', 'catalog_store.catalog_id')
        ->get();
        return CatalogResource::collection($catalog);
    }

    public function getAllCatalogWithFilter(Request $request): object
    {
        $data = Catalog::join('categorys','categorys.id','=','catalogs.category_id')
        ->where(
            $request->get('key'),
            $request->get('comparator') ?? '=',
            $request->get('value')
        )
        ->select('categorys.name', 'catalogs.*')
        ->get();
        return CatalogResource::collection($data);
    }

    public function getCatalogStock($id): object
    {
        $data = [];
        $categories = DB::table('categorys')->get();
        if($categories){
            foreach ($categories as $key => $category) {
                $catalog = Catalog_store::join('catalogs','catalogs.id','=','catalog_store.catalog_id')
                ->join('categorys','categorys.id','=','catalogs.category_id')
                ->select('catalogs.menu_name', 'catalogs.images', 'catalog_store.id', 'catalog_store.stock', 'catalog_store.discount', 'catalog_store.id')
                ->where('store_id', '=', $id)
                ->where('category_id', '=', $category->id)
                ->get();
                if(count($catalog)!=0){
                    $catalogs = new stdClass;
                    $catalogs->list     = $catalog;
                    $datas = new stdClass;
                    $datas->category    = $category->name;
                    $datas->data    = [$catalogs];
                    array_push($data, $datas);
                    }
            }
        }
        return response()->json([
            'status' => 200,
            'data' => $data
        ], 200);
    }

    public function updateCatalogStock($id, Request $req): object
    {
        foreach ($req->get('data') as $data) {
            $catalog = Catalog_store::find($data['id']);
            $catalog->stock = $data['stock'];
            $catalog->discount = $data['discount'];
            try{
                $catalog->save();
            }catch(\Exception $ex){
                return response()->json([
                    'status'    => false,
                    'message' => $ex
                ], 400);
            }
        }
        $catalogs = $this->getCatalogStock($id);
        return response()->json([
            'status' => true,
            'data' => $catalogs->original['data']
        ], 200);
    }

    public function getCatalogByOwner($id): object
    {
        $catalog = Catalog::join('categorys', 'catalogs.category_id', 'categorys.id')
        ->select('categorys.name', 'catalogs.*')
        ->where('owner_id', $id)->get();
        return CatalogResource::collection($catalog);
    }

    public function getCatalogWithCategory($id): object
    {
        $data = [];
        $categories = DB::table('categorys')->get();
        if($categories){
            foreach ($categories as $key => $category) {
                $catalog = DB::table('catalog_store')
                ->join('catalogs','catalogs.id','=','catalog_store.catalog_id')
                ->join('categorys','categorys.id','=','catalogs.category_id')
                ->select('catalogs.id', 'menu_name', 'description', 'price', 'stock', 'discount', 'images')
                ->where('category_id', '=', $category->id)
                ->where('store_id', '=', $id)
                ->where('stock', '>', 0)
                ->get();
                if(count($catalog)!=0){
                    $catalogs = new stdClass;
                    $catalogs->list     = $catalog;
                    $datas = new stdClass;
                    $datas->category    = $category->name;
                    $datas->data    = [$catalogs];
                    array_push($data, $datas);
                    }
            }
        }
        return response()->json([
            'status' => 200,
            'data' => $data
        ], 200);
    }

    public function createCatalog(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer',
            'menu_name' => 'required|string',
            'description' => 'required|string',
            'images' => 'required',
            'price' => 'required|integer',
            'discount' => 'numeric',
        ]);
        $owner = Owner::where('user_id', $request->user()->id)->get();
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
        
        $this->catalog->category_id = $request->category_id;
        $this->catalog->owner_id    = $owner[0]->id;
        $this->catalog->menu_name   = $request->menu_name;
        $this->catalog->description = $request->description;
        $this->catalog->images      = json_encode($images);
        $this->catalog->price       = $request->price;

        if ($this->catalog->save()) {
            try{
                $stores = Store::where('owner_id', $owner[0]->id)->get();
                foreach($stores as $store){
                    $catalog_store = new Catalog_store([
                        "store_id"      => $store->id,
                        "catalog_id"    => $this->catalog->id
                    ]);
                    $catalog_store->save();
                    }
                    return response()->json([
                        'status' => true,
                        'message' => 'Success created catalog'
                    ], 201);
                }catch(\Exception $ex){
                    $this->catalog = $this->catalog->find($this->catalog->id)->delete();
                    return response()->json([
                        'status'    => false,
                        'message' => $ex
                    ], 400);
                }
            }
        return response()->json([
            'status' => false,
            'message' => 'Terjadi kesalahan!'
        ], 500);
    }

    public function findCatalogById($id): object
    {
        $catalog = Catalog::join('categorys', 'categorys.id', 'catalogs.category_id')
        ->join('catalog_store', 'catalog_store.catalog_id', 'catalogs.id')
        ->where('catalogs.id', $id)
        ->select('categorys.name', 'catalogs.*', 'catalog_store.stock', 'catalog_store.discount')
        ->get();
        if(count($catalog)>0){
            return new CatalogResource($catalog[0]);
        }
        return response()->json([
            'status' => false,
            'message' => 'Menu tidak ditemukan!'
        ], 404);
    }

    public function updateCatalog($id, Request $request): object
    {
        $catalog = $this->catalog->find($id);
        if ($catalog) {
            $catalog->menu_name   = $request->menu_name;
            $catalog->description = $request->description;
            $catalog->images      = json_encode($request->images);
            $catalog->price       = $request->price;
            $catalog->discount    = $request->discount;

            if ($catalog->save()) {
                return response()->json([
                    'status' => true,
                    'response_code' => 201,
                    'message' => 'Detail menu berhasil diubah!'
                ]);
            }
        }
        return response()->json([
            'status' => false,
            'response_code' => 500,
            'message' => 'Terjadi kesalahan!'
        ]);
    }

    public function deleteCatalog($id, Request $request): object
    {
        $catalog = $this->catalog->find($id);
        if ($catalog) {
            $catalog_name = $catalog->name;
            $catalog->delete();

            return response()->json([
                'status' => true,
                'response_code' => 200,
                'message' => 'Menu '.$catalog_name.' berhasil dihapus!'
            ]);
        }
        return response()->json([
            'status' => false,
            'response_code' => 404,
            'message' => 'Menu tidak ditemukan!'
        ]);
    }
}
