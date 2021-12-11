<?php

namespace App\Http\Controllers;

use DB;
use App\Order;
use App\Store;
use App\Order_detail;
use App\Catalog_store;
use Carbon\Carbon;
use stdClass;
use Datetime;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\PayUService\Exception;
use App\Http\Resources\Order\OrderResource;


class StatsController extends Controller
{

    public function week($id)
    {
        $week = [];
        $date = new DateTime(date("Y-m-d"));
        $date->modify("-7 days");
        for($i=0;$i<7;$i++){
                $date->modify("+1 days");
            $value= Order::where('store_id',$id)->whereRaw('total = paid')
                ->whereDate('updated_at',$date->format('Y-m-d') )
                ->count();
            $day=array(
                "temperature" => $value,
                "created_at"  => $date->format('Y-m-d')
            );
                array_push($week,$day);
        }   
        return response()->json([
                    'status' => true,
                    'data' => $week,
                ], 200);

    }

    public function weekAll(Request $request)
    {   
        $idOwner  = $request->user()->id;
        $stores = Store::where('owner_id',$idOwner)->pluck('id');
        $week = [];
        $date = new DateTime(date("Y-m-d"));
        $date->modify("-7 days");
        for($i=0;$i<7;$i++){
                $date->modify("+1 days");
            $value=0;

            foreach ($stores as $key) {
                $value+= Order::where('store_id',$key)
                            ->whereRaw('total = paid')
                            ->whereDate('updated_at',$date->format('Y-m-d') )
                            ->count();   
            }
            $day=array(
                "temperature" => $value,
                "created_at"  => $date->format('Y-m-d')
            );
                array_push($week,$day);
        }   
        return response()->json([
                    'status' => true,
                    'data' => $week,
                ], 200);

    }
    public function todayAll(Request $request)
    {   
        $idOwner  = $request->user()->id;
        $stores = Store::where('owner_id',$idOwner)->pluck('id');
        $dateNow = date("Y-m-d");
        $start = date('H:i:s',mktime(0,0,0));
        $end = date('H:i:s',mktime(23,59,59));
        $today =[];
        $i=0;
        while($i <24)
        {
            $now = date($dateNow.' ' .$start);
            $value = 0;
            foreach ($stores as $key) {
                $value += Order::where('store_id',$key)
                                ->whereRaw('total = paid')
                                ->where('updated_at',$now)->count();    
            }
            
            $hour=array(
                "temperature" => $value,
                "created_at"  => $start
            );
                array_push($today,$hour);
                $i++;
                $start = date ("H:i:s", strtotime("+1 hour", strtotime($start)));
        }
        return response()->json([
            'status' => true,
            'data' => $today,
        ], 200);
    }
    public function today($id)
    {
        $dateNow = date("Y-m-d");
        $timeNow =date("H:i:s");
        $open = Store::where('id',$id)->pluck('open_time');
        $open= date('H:i:s', strtotime($open[0]));
        $close = Store::where('id',$id)->pluck('close_time');
        $today =[];
        while(strtotime($open) <= strtotime($close[0]))
        {
            $now = date($dateNow.' ' .$open);
            $value = Order::where('store_id',$id)     
                            ->whereRaw('total = paid')
                            ->where('updated_at',$now)->count();
            $hour=array(
                "temperature" => $value,
                "created_at"  => $open
            );
                array_push($today,$hour);
                $open = date ("H:i:s", strtotime("+1 hour", strtotime($open)));
        }
        return response()->json([
            'status' => true,
            'data' => $today,
        ], 200);
    }

    public function month($id)
    {
        $month =[];
        $d=idate("d");
        $date = new DateTime(date("Y-m-d"));
        $date->modify("-".strval($d)." days");
        for($i=1;$i<=$d;$i++){
            $date->modify("+1 days");
            $curret =$date->format('Y-m-d');
            $value= Order::where('store_id',$id)->whereRaw('total = paid')
            ->whereDate('updated_at',$curret)
            ->count();
        $day=array(
            "temperature" => $value,
            "created_at"  => $curret
        );
            array_push($month,$day);
    }
        return response()->json([
            'status' => true,
            'data' => $month,
        ], 200);
    }

    public function monthAll(Request $request)
    {   
        $idOwner  = $request->user()->id;
        $stores = Store::where('owner_id',$idOwner)->pluck('id');
        $month =[];
        $d=idate("d");
        $date = new DateTime(date("Y-m-d"));
        $date->modify("-".strval($d)." days");
        for($i=1;$i<=$d;$i++){
            $date->modify("+1 days");
            $curret =$date->format('Y-m-d');
            $value = 0;
                foreach ($stores as $key) {
                    $value+= Order::where('store_id',$key)
                            ->whereRaw('total = paid')
                            ->whereDate('updated_at',$curret)
                            ->count();
                }
        $day=array(
            "temperature" => $value,
            "created_at"  => $curret
        );
            array_push($month,$day);
    }
        return response()->json([
            'status' => true,
            'data' => $month,
        ], 200);
    }

    public function year($id)
    {   
        $start = Order::where('store_id',$id)->whereRaw('total = paid')
                        ->orderBy('updated_at','ASC')->pluck('updated_at')->first();
        $startMonth = date("n",strtotime($start));
        $nowMonth = date("n");
        $startYear = date("Y",strtotime($start));
        $nowYear = date("Y");
        $data = [];
        while ((int)$startYear <= (int)$nowYear) {
            $year =[];
            $endmonth = 12;
            if((int)$startYear == (int)$nowYear)
            $endmonth=$nowMonth;
                while ((int)$startMonth <= $endmonth) {
                    $value = Order:: where('store_id',$id)->whereRaw('total = paid')
                                ->whereYear('updated_at','=',$startYear)
                                ->whereMonth('updated_at','=',$startMonth)
                                ->count();
                
                    $month = array(
                        "temperature" => $value,
                        "month"       => strval( $startMonth)
                    );
                    array_push($year,$month);
                    (int)$startMonth++;
                }

            $years=array(
                    "year" => $startYear,
                    "data"  => $year
                );
            array_push($data,$years);
            (int)$startMonth=1;
            (int)$startYear++;
        }

        return response()->json([
            'status' => true,
            'data' => $data ,
        ], 200);

    }


    public function yearAll(Request $request)
    {   
        $idOwner  = $request->user()->id;
        $stores = Store::where('owner_id',$idOwner)->pluck('id');
        $id = $stores[0];
        $start = Order::where('store_id',$id)->whereRaw('total = paid')
                    ->orderBy('updated_at','ASC')->pluck('updated_at')->first();
        $startMonth = date("n",strtotime($start));
        $nowMonth = date("n");
        $startYear = date("Y",strtotime($start));
        $nowYear = date("Y");
        $data = [];
        while ((int)$startYear <= (int)$nowYear) {
            $year =[];
            $endmonth = 12;
            if((int)$startYear == (int)$nowYear)
            $endmonth=$nowMonth;
                while ((int)$startMonth <= $endmonth) {
                    $value = 0;
                    foreach ($stores as $key) {
                        $value+= Order:: where('store_id',$key)->whereRaw('total = paid')
                                ->whereYear('updated_at','=',$startYear)
                                ->whereMonth('updated_at','=',$startMonth)
                                ->count();
                        }
                    $month = array(
                        "temperature" => $value,
                        "month"       => strval( $startMonth)
                    );
                    array_push($year,$month);
                    (int)$startMonth++;
                }

            $years=array(
                    "year" => $startYear,
                    "data"  => $year
                );
            array_push($data,$years);
            (int)$startMonth=1;
            (int)$startYear++;
        }

        return response()->json([
            'status' => true,
            'data' => $data ,
        ], 200);

    }

   
    

}
