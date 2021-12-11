<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Cashier;

class CashierController extends Controller
{
    public function __construct()
    {
        $this->cashier = new Cashier;
        $this->user = new User;
    }

    public function getAllCashier(Request $request): object
    {
        $result = [];
        $ownerStores = json_decode($request->user()->stores);
        $cashiers = $this->cashier->role('cashier')->get();
        foreach ($cashiers as $cashier) {
            $stores = json_decode($cashier->stores);
            foreach ($stores as $store) {
                if (!in_array($cashier, $result)) {
                    if (in_array($store, $ownerStores)) {
                        array_push($result, $cashier);
                    }
                }
            }
        }

        return response()->json([
            'data' => $result
        ]);
    }

    // public function createCashier(Request $request)
    // {
    //     $this->cashier->name     = $request->name;
    //     $this->cashier->email    = $request->email;
    //     $this->cashier->password = bcrypt($request->password);
    //     $this->cashier->stores   = json_encode($request->stores);

    //     if ($this->cashier->save()) {
    //         $this->cashier->assignRole(2);
    //         return response()->json([
    //             'status' => true,
    //             'response_code' => 201,
    //             'message' => 'Kasir berhasil ditambahkan!'
    //         ]);
    //     }
    //     return response()->json([
    //         'status' => false,
    //         'response_code' => 500,
    //         'message' => 'Terjadi kesalahan!'
    //     ]);
    // }

    public function findCashierByOwner($id): object
    {
        $cashier =  Cashier::where('owner_id', $id)->get();
        return response()->json([
            'status' => true,
            'data' => $cashier
        ], 200);
    }

    public function findCashierByStore($id): object
    {
        $cashier =  Cashier::join('users', 'users.id', 'cashiers.user_id')
        ->where('store_id', '=', $id)
        ->select('cashiers.id as id', 'name', 'email', 'store_id')
        ->get();
        return response()->json([
            'status' => true,
            'data' => $cashier
        ], 200);
    }

    public function findCashierById($id): object
    {
        $cashier =  $this->cashier->find($id)
        ->join('users', 'users.id', 'cashiers.user_id')
        ->select('cashiers.id as id', 'name', 'email', 'store_id')
        ->get();
        return response()->json([
            'status' => true,
            'data' => $cashier
            //     'id' => $cashier->id,
            //     'name' => $cashier->name,
            //     'email' => $cashier->email,
            //     'store_id' => $cashier->store_id,
            // ]
        ], 200);
    }

    public function updateCashier($id, Request $request): object
    {
        $cashier = $this->cashier->find($id);
        if ($cashier) {
            $cashier->name        = $request->name;
            $cashier->store_id   = $request->store_id;

            if ($cashier->save()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Detail kasir berhasil diubah!'
            ], 201);
            }
        }
        return response()->json([
            'status' => false,
            'message' => 'Terjadi kesalahan!'
    ], 500);
    }

    public function deleteCashier($id, Request $request): object
    {
        $cashier = Cashier::find($id);
        if ($cashier) {
            $user = User::find($cashier->user_id);
            $cashier_name = $cashier->name;
            $user->delete();

            return response()->json([
                'status' => true,
                'response_code' => 200,
                'message' => $cashier_name.' berhasil dihapus!'
            ]);
        }
        return response()->json([
            'status' => false,
            'response_code' => 404,
            'message' => 'Kasir tidak ditemukan!'
        ]);
    }
}
