<?php

namespace App\Http\Controllers;
use App\Services\PayUService\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;
use App\Owner;
use App\Cashier;
use App\Store;

class AuthController extends Controller
{
    // create user 

    public function register(Request $request)
    {
        $request->validate([
        'email' => 'required|string|email|unique:users',
        'password' => 'required|string|confirmed',
        'name' => 'required|string',
        'phone' => 'required|string',
        'address' => 'required|string',
        'province' => 'required|integer',
        'city' => 'required|integer',
        'district' => 'required|integer',
        'ktp' => 'string|min:16',
        ]);

        // save to users table
        $user = new User([
            'role' => 1,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // save to owner/cashier table
        if($user->save()){
            try{
                $owner = new Owner([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'province' => $request->province,
                    'city' => $request->city,
                    'district' => $request->district,
                    'ktp' => $request->ktp,
                ]);
                $owner->save();
                return response()->json([
                    'status' => true,
                    'message' => 'Successfully created owner!'
                ], 201);
            }catch (\Exception $ex) {
                $user = $user->find($user->id)->delete();
                return response()->json([
                    'message' => $ex->getMessage()
                ], 400);
            }
        }
        return response()->json([
            'status' => false,
            'message' => 'Failed when create user!'
        ], 400);
    }


    public function registerCashier(Request $request)
    {
        $request->validate([
        'email' => 'required|string|email|unique:users',
        'password' => 'required|string|confirmed',
        'store_id' => 'integer',
        ]);

        // save to users table
        $user = new User([
            'role' => 2,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // save cashier table
        if($user->save()){
            try{
                $owner = Owner::where('user_id', $request->user()->id)->get();
                $cashier = new Cashier([
                    'user_id' => $user->id,
                    'owner_id' => $owner[0]->id,
                    'store_id' => $request->store_id,
                    'name' => $request->name,
                    'store_id' => $request->store_id,
                ]);
                $cashier->save();
                return response()->json([
                    'status' => true,
                    'message' => 'Successfully created cashier!'
                ], 201);
            }catch(\Exception $ex){
                $user = $user->find($user->id)->delete();
                return response()->json([
                    'status' => false,
                    'message' => $ex->getMessage()
                ], 400);
            }
        }
        return response()->json([
            'status' => false,
            'message' => 'Failed when create user!'
        ], 400);
    }

    // login user 

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean',
        ]);
        $credentials = request(['email', 'password']);
        if(!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();

        if($request->user()->role==1){
            $owner = Owner::where('user_id', $user->id)->get();
            $store = Store::where(
                'owner_id', $owner[0]->id)
                ->select('id', 'store_name')
                ->get();
            return response()->json([
                'access_token'  => $tokenResult->accessToken,
                'role'          => $request->user()->role,
                'data'          => $owner[0],
                'store'       => $store,
        ], 200);
        }else{
            $cashier = Cashier::where(
                'user_id', $request->user()->id
            )->get();
            return response()->json([
                'access_token' => $tokenResult->accessToken,
                'role'=> $request->user()->role,
                'data'=> $cashier[0],
            ], 200);
        }
    }

    // logout user 

    public function logout(Request $request)
    {
        $request->user()->token()->delete(); ;
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    // get user data
    public function user(Request $request)
    {
        $owner = Owner::where('user_id', $request->user()->id)->get();
        return response()->json([
                'status' => true,
                'data' => count($owner)!=0? $owner : Cashier::where('user_id', $request->user()->id)->get()
            ], 200);
    }
}
