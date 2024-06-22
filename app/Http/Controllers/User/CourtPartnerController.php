<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\CourtListResource;
use App\Models\BookingDetail;
use App\Models\Court;
use App\Models\CourtPartner;
use App\Models\CourtPrice;
use App\Models\CourtType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class CourtPartnerController extends Controller
{
    public function get_court_partner_list(Request $request){
        try{
            $court_type_id = $request->query('court_type_id');
            $search = $request->query('search');
            $current_page = $request->query('current_page');
            $city_id = $request->query('city_id');

            $court_partner_list = CourtPartner::query();

            if($search){
                $court_partner_list = $court_partner_list->where('name','LIKE','%'.$search.'%');
            }

            if($court_type_id){
                $court_partner_list = $court_partner_list->join('court_partner_court_types','court_partner_court_types.court_partner_id','court_partners.id')->where('court_partner_court_types.court_type_id',$court_type_id);
            }

            if($city_id != null){
                $court_partner_list = $court_partner_list->where('city_id',$city_id);
            }

            $total_data = $court_partner_list->count();

            if($current_page){
                $offset = ($request->current_page - 1)*5;

                $court_partner_list = $court_partner_list->skip($offset)->take(5)->get();
            }else{
                $court_partner_list = $court_partner_list->get();
            }

            $court_partner_data = [];

            foreach($court_partner_list as $partner){

                //ambil gambar dari storage

                $court_type_list = [];

                foreach($partner->court_types as $court_type){

                    if($court_type->icon){
                        try{
                            $image_path = public_path('storage/'.$court_type->icon);
                            $image_extension = explode('.',$court_type->icon)[1];
                            $image = file_get_contents($image_path);
                            $base64_image = base64_encode($image);
                            $image_base64 = 'data:image/'.$image_extension.';base64,'.$base64_image;
                        }catch(Exception $e){
                            $image_base64 = null;
                        }


                    }else{
                        $image_base64 = null;
                    }
                    $court_type_list[] = [
                        'id' => $court_type->id,
                        'name' => $court_type->name,
                        'image' => $image_base64
                    ];
                }

                if($partner->profile){
                    try{
                        $image_path = public_path('storage/'.$partner->profile);
                        $image_extension = explode('.',$partner->profile)[1];
                        $image = file_get_contents($image_path);
                        $base64_image = base64_encode($image);
                        $profile = 'data:image/'.$image_extension.';base64,'.$base64_image;
                    }catch(Exception $e){
                        $profile = null;
                    }

                }else{
                    $profile = null;
                }

                if(!$court_type_id){
                    $court_price = CourtPrice::query()->whereHas('court', function($query) use ($partner){
                        $query->where('court_partner_id',$partner->id);
                    })->min('price');
                }else{
                    $court_price = CourtPrice::query()->whereHas('court', function($query) use ($court_type_id,$partner){
                        $query->where('court_type_id',$court_type_id)->where('court_partner_id',$partner->id);
                    })->min('price');
                }

                $court_partner_data[] = [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'description' => $partner->description,
                    'total_court' => $partner->court->count(),
                    'address' => $partner->address,
                    'profile' => $profile,
                    'google_map' => $partner->google_map,
                    'court_type_list' => $court_type_list,
                    'city' => $partner->city->name,
                    'operational_hour' => Carbon::parse($partner->start_at)->format('H:i').'-'.Carbon::parse($partner->end_at)->format('H:i'),
			        'minimum_price' => intval($court_price)
                ];
		    }

            return ResponseFormatter::success([
                'total_data' => $total_data,
                'data' =>$court_partner_data
            ],'Berhasil Mendapatkan List Partner');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function get_court_partner_data($id){
        try{

            $court_partner = CourtPartner::find($id);

            if($court_partner == null){
                return ResponseFormatter::error(null,'Partner Tidak Ditemukan');
            }

            $court_type_list = [];

            foreach($court_partner->court_types as $court_type){

                $image_string = null;

                if($court_type->icon) {
                    try {
                        $image_path = public_path('storage/'.$court_type->icon);
                        $image_extension = explode('.',$court_type->icon)[1];
                        $image = file_get_contents($image_path);
                        $base64_image = base64_encode($image);
                        $image_string='data:image/'.$image_extension.';base64,'.$base64_image;
                    }catch(\Exception $e){

                    }

                }

                $data = [
                    'id' => $court_type->id,
                    'name' => $court_type->name,
                    'image' => $image_string
                ];
                array_push($court_type_list,$data);
            }

            $min_price = CourtPrice::query()->whereHas('court', function($query) use ($court_partner){
                $query->where('court_partner_id',$court_partner->id);})->min('price');

            $max_price = CourtPrice::query()->whereHas('court', function($query) use ($court_partner){
                $query->where('court_partner_id',$court_partner->id);})->max('price');

            if($court_partner->profile){
                try{
                    $image_path = public_path('storage/'.$court_partner->profile);
                    $image_extension = explode('.',$court_partner->profile)[1];
                    $image = file_get_contents($image_path);
                    $base64_image = base64_encode($image);
                    $court_partner->profile = 'data:image/'.$image_extension.';base64,'.$base64_image;
                }catch(Exception $e){
                    $court_partner->profile = null;
                }
            }

            $facility_list = [];

            foreach($court_partner->facilities as $facility){
                $base64_string = null;

                if($facility->icon){

                    try{
                        $image_path = public_path('storage/'.$facility->icon);
                        $image_extension = explode('.',$facility->icon)[1];
                        $image = file_get_contents($image_path);
                        $base64_image = base64_encode($image);
                        $base64_string = 'data:image/'.$image_extension.';base64,'.$base64_image;
                    }catch(Exception $e){
                    }
                }

                $facility_list[] = [
                    'id' => $facility->id,
                    'image' => $base64_string,
                    'name' => $facility->name
                ];
            }

            return ResponseFormatter::success([
                'id' => $court_partner->id,
                'name' => $court_partner->name,
                'start_at' => Carbon::parse($court_partner->start_at)->format('H:i'),
                'end_at' => Carbon::parse($court_partner->end_at)->format('H:i'),
                'is_down_payment' => boolval($court_partner->is_down_payment),
                'down_payment_percentage' => intval($court_partner->down_payment_percentage),
                'down_payment_amount' => intval($court_partner->down_payment_amount),
                'down_payment_type' => $court_partner->down_payment_type,
                'city' => [
                    'id' => $court_partner->city_id,
                    'name' =>$court_partner->city->name
                ],
                'description' => $court_partner->description,
                'total_court' => $court_partner->court->count(),
                'profile' => $court_partner->profile,
                'court_type_list' => $court_type_list,
                'address' => $court_partner->address,
                'google_map' => $court_partner->google_map,
                'price_range_min' => $min_price,
                'price_range_max' => $max_price,
                'facility' => $facility_list

            ], 'Berhasi Mendapatkan Data Court Partner');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function get_court_list(Request $request){
        try{
            $court_partner_id = $request->query('court_partner_id');
            $court_type_id = $request->query('court_type_id');
            $date = $request->query('date') ? Carbon::parse($request->query('date'))->format('Y-m-d') : Carbon::now()->format('Y-m-d');

            $start_time = $request->query('start_time');
            $end_time = $request->query('end_time');

            $court_partner = CourtPartner::find($court_partner_id);

            $court_data = [];

            foreach($court_partner->court_types as $court_type){

                $court_list = Court::where('court_partner_id',$court_partner->id)->where('court_type_id',$court_type->id)->orderBy('id','asc')->get()->map(function ($query) use ($date) {
                    $query['date'] = $date;
                    return $query;
                });;

                $data = CourtListResource::collection($court_list);

                $court_type_temp = [
                    'id' => $court_type->id,
                    'name' => $court_type->name
                ];

                $court_data [] =[
                    'court_type' => $court_type_temp,
                    'date' => $date,
                    'court_list' =>  $data
                ];
            }

            return ResponseFormatter::success($court_data,'Berhasil Mendapatkan Data List Lapangan');
        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function get_court_list_old(Request $request){
        try{
            $court_partner_id = $request->query('court_partner_id');
            $court_type_id = $request->query('court_type_id');
            $date = $request->query('date') ? Carbon::parse($request->query('date'))->format('Y-m-d') : Carbon::now()->format('Y-m-d');

            $start_time = $request->query('start_time');
            $end_time = $request->query('end_time');

            $court_partner = CourtPartner::find($court_partner_id);

            $court_query = Court::query()->with('court_type')->where('court_partner_id',$court_partner->id)->orderBy('court_type_id','asc')->orderBy('id','asc')->select('id','name','description','court_type_id');

            if($court_type_id != null){
                $court_query = $court_query->where('court_type_id',$court_type_id);
            }

            $court_list = $court_query->get();

            $court_data = [];

            $court_type_temp = null;
            $court_list_temp = [];
            $available_count = 0;

            foreach($court_list as $court){

                if($court_type_temp != null){
                    if($court->court_type_id != $court_type_temp['id']){
                        $court_data [] =[
                            'court_type' => $court_type_temp,
                            'available' => $available_count,
                            'court_data' => $court_list_temp
                        ];

                        $court_type_temp = null;
                        $available_count = 0;
                        $court_list_temp = [];
                    }
                }

                $court_type_temp = [
                    'id' => $court->court_type_id,
                    'name' => $court->court_type->name
                ];

                $court_id = $court->id;

                $date = Carbon::parse($date)->format('Y-m-d');
                $day = Carbon::parse($date)->format('l');

                $booking_details = BookingDetail::where('date', $date)->where('court_id',$court_id)->orderBy('start_time')->get();

                $query_price = CourtPrice::query()->where('court_id', $court_id)->where('day','like','%'. $day.'%')->orderBy('start_time','asc');

                if($start_time){
                    $query_price = $query_price->where('start_time','>=',$start_time);
                }

                if($end_time){
                    $query_price = $query_price->where('end_time','<=',$end_time);
                }

                $court_price = $query_price->get();

                $available_time = [];

                $booking_detail_index = 0;
                $total_booking_detail = count($booking_details);

                foreach ($court_price as $price) {
                    if ($total_booking_detail > 0 && $booking_detail_index < $total_booking_detail && Carbon::parse($booking_details[$booking_detail_index]->start_time)->format('H:i') == Carbon::parse($price->start_time)->format('H:i')) {
                        $booking_detail_index++;
                    } else {
                        $available_time[] = [
                            'time' => Carbon::parse($price->start_time)->format('H:i') . '-' .Carbon::parse($price->end_time)->format('H:i'),
                            'price' => intval($price->price)
                        ];
                        $available_count++;
                    }
                }

                $image_list = [];
                foreach($court->images as $image){
                    try{
                        $image_path = public_path('storage/'.$image->images);
                        $image_extension = explode('.',$image->images)[1];
                        $image = file_get_contents($image_path);
                        $base64_image = base64_encode($image);
                        $image_list[] = 'data:image/'.$image_extension.';base64,'.$base64_image;
                    }catch(Exception $e){

                    }

                }

                $court_list_temp[] = [
                    'court_id' => $court->id,
                    'court_name' => $court->name,
                    'available_time_list' => $available_time,
                    'image' => $image_list
                ];

            }

            $court_data [] =[
                'court_type' => $court_type_temp,
                'available' => $available_count,
                'court_data' => $court_list_temp,
                'date' => $date
            ];

            return ResponseFormatter::success($court_data,'Berhasil Mendapatkan Data List Lapangan');
        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function get_court_available_time(Request $request){
        try {

            $court_id = $request->query('court_id');
            $date = $request->query('date');

            $court = Court::find($court_id);
            $date = Carbon::parse($date)->format('Y-m-d');
            $day = Carbon::parse($date)->format('l');

            $booking_details = BookingDetail::where('date', $date)->orderBy('start_time')->get();

            $court_price = CourtPrice::query()->where('court_id', $court_id)->where('day','like','%'. $day.'%')->orderBy('start_time','asc')->get();

            $available_time = [];

            $booking_detail_index = 0;
            $total_booking_detail = count($booking_details);

            foreach ($court_price as $price) {
                if ($total_booking_detail > 0 && $booking_detail_index < $total_booking_detail && Carbon::parse($booking_details[$booking_detail_index]->start_time)->format('H:i') == Carbon::parse($price->start_time)->format('H:i')) {
                    $booking_detail_index++;
                } else {
                    $available_time[] = [
                        'time' => Carbon::parse($price->start_time)->format('H:i') . '-' .Carbon::parse($price->end_time)->format('H:i'),
                        'price' => intval($price->price)
                    ];
                }

            }

            return ResponseFormatter::success($available_time, 'Berhasil Mendapatkan Jam Tersedia');

        } catch (Exception $e) {
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

}
