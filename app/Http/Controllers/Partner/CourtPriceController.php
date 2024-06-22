<?php

namespace App\Http\Controllers\Partner;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\CourtPrice\AddCourtPriceRequest;
use App\Http\Requests\Partner\CourtPrice\DeleteCourtPriceRequest;
use App\Http\Requests\Partner\CourtPrice\GetCourtPriceRequest;
use App\Models\CourtPrice;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class CourtPriceController extends Controller
{
    public function get_court_price($court_id, $day = null){
        try{

            if($day != null){

                $data = CourtPrice::where('court_id',$court_id)->select('id','price','start_time','end_time')->where('day',$day)->orderBy('id','asc')->get();

                $court_price = [
                    'day' => $day,
                    'list' => $data
                ];

            }else{
                $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

                $court_price = [];

                for($i = 0;$i<7;$i++){
                    $data = CourtPrice::where('court_id',$court_id)->select('id','price','start_time','end_time')->where('day',$days[$i])->orderBy('id','asc')->get();

                    $data = [
                        'day' => $days[$i],
                        'list' => $data
                    ];

                    array_push($court_price, $data);
                }
            }

            return ResponseFormatter::success([
                'court_price' => $court_price
            ]);

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function add_court_price(AddCourtPriceRequest $request){
        try{

            DB::beginTransaction();

            $time_count = count($request->court_price_id);
            $court_list = $request->court_price_id;
            for($i=0;$i<$time_count;$i++){
                $court_price = CourtPrice::find($court_list[$i]);
                if($court_price == null){
                    return ResponseFormatter::error(
                        null,
                        'General Error',
                        500
                    );
                }

                $court_price->price = $request->price;
                $court_price->save();
            }

            DB::commit();

            return ResponseFormatter::success(null, 'Berhasil Melakukan Perubahan Pada Harga');

        }catch(Exception $e){

            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function clear_court_price(DeleteCourtPriceRequest $request){
        try{
            DB::beginTransaction();
            $time_count = count($request->court_price_id);
            $court_list = $request->court_price_id;
            for($i=0;$i<$time_count;$i++){
                $court_price = CourtPrice::find($court_list[$i]);
                if($court_price == null){
                    return ResponseFormatter::error(
                        null,
                        'General Error',
                        500
                    );
                }

                $court_price->price = null;
                $court_price->save();
            }
            DB::commit();

            return ResponseFormatter::success(null,'Berhasil Menghapus Harga Lapangan Olahraga');

        }catch(Exception $e){
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }
}
