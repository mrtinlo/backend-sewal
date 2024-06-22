<?php

namespace App\Http\Controllers\User;

use App\Helpers\ImageService;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use App\Models\Court;
use App\Models\CourtPartner;
use App\Models\CourtPrice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class HomeController extends Controller
{

    private $image_service;

    public function __construct(ImageService $image_service){
        $this->image_service =  $image_service;
    }

    public function get_court_partner_list($court_type_id, $court_type,$search,$current_page){
        try{

            if($search == null){
                $court_partner_list = CourtPartner::with('court_type')->whereHas('court_type', function ($query) use ($court_type_id){
                $query->where('id',$court_type_id); })->get();
            }else{
                    $court_partner_list = CourtPartner::with('court_type')->whereHas('court_type', function ($query) use ($court_type_id){
                    $query->where('id',$court_type_id);
                })->where('name','LIKE',$search)->get();
            }

            $court_partner_data = [];

            foreach($court_partner_list as $partner){


                if(count($partner->images) > 0){
                    $index =0;
                    foreach($partner->images as $image){
                        $image_path = public_path('storage/'.$image->image);
                        $image_extension = explode('.',$image->image)[1];
                        $image = file_get_contents($image_path);
                        $base64_image = base64_encode($image);
                        $partner->images[$index] = 'data:image/'.$image_extension.';base64,'.$base64_image;
                        $index++;
                    }
                }

                $court_partner_data[] = [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'description' => $partner->description,
                    'total_court' => $partner->total_court,
                    'profile' => $partner->images,
                    'address' => $partner->address,
                    'operational_hour' => $partner->start_time.'-'.$partner->end_time
			    ];
		    }

            return ResponseFormatter::success($court_partner_data,'Berhasil Mendapatkan List Partner');

        }catch(Exception $e){
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function get_court_list($court_partner_id, $court_type_id){
        try{

            $court_partner = CourtPartner::find($court_partner_id);

            $court_list = Court::where('court_partner',$court_partner->id)->where('court_type_id',$court_type_id)->select('id','name','description')->get();

            return ResponseFormatter::success($court_list,'Berhasil Mendapatkan Data List Lapangan');
        }catch(Exception $e){
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }


    public function get_court_available_time($court_id, $date){
        try{

            $date = Carbon::parse($date)->format('Y-m-d');
            $day = Carbon::parse($date)->format('l');

            $booking_detail = BookingDetail::where('date',$date)->orderBy('start_time')->get();

            $court_price = CourtPrice::where('court_id',$court_id)->where('day',$day)->orderBy('start_time')->get();

            $available_time=[];

            $booking_detail_index = 0;
            $total_booking_detail = count($booking_detail);

            foreach($court_price as $price){
                if($total_booking_detail> 0 && $booking_detail_index < $total_booking_detail && date('H:i',$booking_detail[$booking_detail_index]->start_time) == date('H:i',$price->start_time)){
                    $booking_detail_index++;
                }else{
                    $available_time[] = [
                        'time' => $court_price->start_time.'-'.$court_price->end_time,
					    'price'=> intval($court_price->price)
				    ];
			    }

            }

            return ResponseFormatter::success($available_time, 'Berhasil Mendapatkan Jam Tersedia');

        }catch(Exception $e){}
    }

}
