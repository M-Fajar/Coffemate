<?php

namespace App\Http\Controllers;

use DB;
use App\Order;
use App\Store;
use App\Order_detail;
use App\Catalog_store;
use Carbon\Carbon;
use stdClass;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\PayUService\Exception;
use App\Http\Resources\Order\OrderResource;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createOrder(Request $req)
    {
        $req->validate([
            'store_id'      => 'required|integer',
            'cashier_id'       => 'required|integer',
            'total'         => 'required|numeric',
            'paid'          => 'integer',
            'delivered'     => 'boolean',
            'data'          => 'required|array',
            'data.catalog_id' => ['required|integer'],
            'data.quantity' => ['required|integer'],
            'data.price' => ['required|float'],
            'data.discount' => ['integer'],
            'data.done' => ['boolean'],
        ]);

        $record = Order::latest()->first()->id??1;
        $status = true;

        $order = new Order;
        $order->store_id    = $req->store_id;
        $order->invoice     = 'CM'.$req->store_id.date('ym').'-'.$record;
        $order->cashier_id  = $req->cashier_id;
        $order->total       = $req->total;
        $order->name        = $req->name;
        $order->seat        = $req->seat;
        $order->phone       = $req->phone;
        $order->description = $req->description;
        $order->paid        = $req->paid;
        $order->delivered   = $req->delivered;
        if($order->save()){
            $datas = $req['data'];
            try{
                foreach ($datas as $data) {
                    $detail = new Order_detail;
                    $catalog = Catalog_store::
                        where('store_id', $req->store_id)
                        ->where('catalog_id', $data['catalog_id'])
                        ->get();
                    $detail->order_id       = $order->id;
                    $detail->catalog_id     = $data['catalog_id'];
                    $detail->quantity       = $data['quantity'];
                    $detail->price          = $data['price'];
                    $detail->discount       = $data['discount'];
                    $detail->done           = $data['done'];
                    $detail->save();
                    
                    $catalog[0]->stock     -= $data['quantity'];
                    $catalog[0]->save();
                    
                }
                if($req->seat){
                    $this->fillSeat($req->store_id, $req->seat, $status);
                }
            }catch(\Exception $ex){
                $order = $order->find($order->id)->delete();
                return response()->json([
                    'status'    => false,
                    'message' => $ex->getMessage()
                ], 400);
            }
        
            return response()->json([
                'status'    => true,
                'seat'      => unserialize(Store::find($req->store_id)->seat),
                'order'     => $this->orderFilter($req->store_id),
                'payment'   => $this->getPaymentOrder($req->store_id)
            ], 201);
        }
        return response()->json([
            'status'    => false,
            'message'   => 'Terjadi kesalahan'
        ], 400);
    } 

    public function getOrderIn($id)
    {
        $order = Order::where('store_id', '=', $id)
        ->where('delivered', '=', false)
        ->get();
        return OrderResource::collection($order);
    }

    public function getOrderHistory($id)
    {
        $order = Order::where('store_id', '=', $id)
        ->where('delivered', '=', true)
        ->where('created_at', '>', Carbon::now()->subHours(12)->toDateTimeString())
        ->get();
        return OrderResource::collection($order);
    }

    public function getPaymentOrder($id)
    {
        $order = Order::where('store_id', '=', $id)
        ->whereColumn('total', '>', 'paid')
        ->get();
        return OrderResource::collection($order);
    }

    public function paymentOrder($id, Request $req)
    {
        $order = Order::find($id);
        $status = false;
        if($order){
            $order->paid   += $req->paid;
            if($order->save()){
                $sisa = $order->total - $order->paid;
                if($order->paid >= $order->total){
                    Order_detail::where('order_id', $id)->update(['paid'=>true]);
                    if($order->seat){
                        $this->fillSeat($order->store_id, $order->seat, $status);
                    }
                    return response()->json([
                    'status' => true,
                    'complete' => true,
                    'message' => 'Pembayaran selesai',
                    'seat'    => unserialize(Store::find($order->store_id)->seat),
                    'payment' => $this->getPaymentOrder($order->store_id)
                    ], 201);
                }else{
                    $split = $req['split'];
                    Order_detail::whereIn('id', $split)->update(['paid'=>true]);
                    return response()->json([
                        'status' => true,
                        'complete' => false,
                        'message' => 'Sisa yang belum dibayar adalah '.$sisa.'',
                        'payment' => $this->getPaymentOrder($order->store_id)
                    ], 201);
                }
            }
        }
        return response()->json([
                    'status' => false,
                    'message' => 'Something wrong!'
        ], 500);
    }

    public function orderDelivered($id)
    {
        $order = Order::find($id);
        if($order){
            $order->delivered   = true;
            if($order->save()){
                return response()->json([
                    'status' => true,
                    'seat' => unserialize(Store::find($order->store_id)->seat),
                    'order' => $this->orderFilter($order->store_id)
                ], 201);
            }
            
        }
        return response()->json([
                    'status' => false,
                    'message' => 'Something wrong!'
        ], 500);
    }

    public function orderDone($id, Request $req)
    {
        $order = Order_detail::find($id);
        if($order){
            $order->done   = $req->done;
            if($order->save()){
                return response()->json([
                    'status' => true,
                    'message' => 'Order done'
                ], 201);
            }
            
        }
        return response()->json([
                    'status' => false,
                    'message' => 'Something wrong!'
        ], 500);
    }

    private function fillSeat($id, $index, $status)
    {
        $store = Store::find($id);
        $seat = unserialize($store->seat);
        if($index <= count($seat)){
            $seat[$index-1] = $status;
            $store->seat = serialize($seat);
            if($store->save()){
                return true;
            }
        }
        return false;
    }

    public function orderFilter($store_id)
    {
        $order = Order::where('store_id', '=', $store_id)
        ->where('delivered', '=', false)
        ->get();
        return OrderResource::collection($order);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
