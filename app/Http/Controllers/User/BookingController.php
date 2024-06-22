<?php

namespace App\Http\Controllers\User;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Booking\CheckBookingRequest;
use App\Http\Requests\User\Booking\CreateBookingRequest;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\CourtPartner;
use App\Models\CourtPrice;
use App\Models\Payment;
use App\Models\PaymentDetail;
use Carbon\Carbon;
use App\Models\User;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use iPaymu\iPaymu;

class BookingController extends Controller
{
    public function save_booking(CreateBookingRequest $request)
    {
        try {
            DB::beginTransaction();
            $court_partner = CourtPartner::find($request->court_partner_id);

            $user = User::find(Auth::user()->id);
            $cart = $request->cart;

            if($request->payment_type != 'no-payment' && $request->payment_method == null){
                return ResponseFormatter::error(null,'Payment Method wajib diisi');
            }


            $booking_id = 'Booking-' . Carbon::now()->format('YmdHis');

            $booking = Booking::Create([
                'user_id' => $user->id,
                'court_partner_id' => $court_partner->id,
                'total_payment' => $request->total_price,
                'date' => $cart[0]['date'],
                'payment_type' => $request->payment_type,
                'is_membership' => 0,
                'total_discount' => $request->discount > 0 ? $request->discount : 0,
                'booking_id' => $booking_id,
                'status' => 'Lunas',
                'is_paid' => true,
            ]);

            $booking_detail_list = [];
            foreach ($cart[0]['detail'] as $each_cart) {
                $check_booking_detail = BookingDetail::where('court_id', $each_cart['court_id'])->where('start_time', Carbon::parse($each_cart['start_time'])->format('H:i'))->where('date', Carbon::parse($cart[0]['date'])->format('Y-m-d'))->first();

                if ($check_booking_detail != null) {
                    return ResponseFormatter::error(null, $each_cart['court_name'] . ' pada tanggal ' . Carbon::parse($cart[0]['date'])->format('d-m-Y') . ' jam ' . Carbon::parse($each_cart['start_time'])->format('H:i') . ' sudah dibooking');
                }

                $booking_detail = BookingDetail::Create([
                    'start_time' => Carbon::parse($each_cart['start_time'])->format('H:i'),
                    'date' => $cart[0]['date'],
                    'end_time' => Carbon::parse($each_cart['end_time'])->format('H:i'),
                    'court_id' => $each_cart['court_id'],
                    'price' => $each_cart['price'],
                    'discount' => $each_cart['discount'] ? $each_cart['discount'] : 0,
                    'booking_id' => $booking->id,
                    'is_membership' => 0
                ]);

                $temp = $booking_detail;
                $temp['court_name'] = $each_cart['court_name'];

                array_push($booking_detail_list, $temp);
            }


            $payment_id = 'Payment-' . Carbon::now()->format('YmdHis');

            if ($request->payment_type == 'full-payment') {
                $payment = Payment::Create([
                    'booking_id' => $booking->id,
                    'amount' => $request->total_price - $request->discount,
                    'type' => 'schedule',
                    'payment_method' => $request->payment_method,
                    'payment_id' => $payment_id
                ]);

                foreach ($booking_detail_list as $detail) {
                    $payment_detail = PaymentDetail::Create([
                        'payment_id' => $payment->id,
                        'booking_detail_id' => $detail->id,
                        'amount' => $detail->price - $detail->discount,
                    ]);
                }

            } else if ($request->payment_type == 'down-payment') {

                if ($request->down_payment_amount <= 0) {
                    return ResponseFormatter::error(null, 'Jumlah DP harus lebih besar dari 0');
                }

                $payment = Payment::Create([
                    'booking_id' => $booking->id,
                    'amount' => $request->down_payment_amount,
                    'type' => 'down-payment',
                    'payment_method' => $request->payment_method,
                    'payment_id' => $payment_id
                ]);
            }

            DB::commit();
            return ResponseFormatter::success(null, 'Berhasil Membuat Booking Baru');

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function cancel_booking($id){
        try{
            $booking = Booking::find($id);

            DB::beginTransaction();

            if($booking->user_id != Auth::user()->id){
                return ResponseFormatter::error(null, 'Booking ini bukan milik anda');
            }
            $payments = Payment::where('booking_id',$booking->id)->get();

            foreach ($payments as $payment){

                if($payment->type == 'schedule'){
                    $payment_details = PaymentDetail::where('payment_id',$payment->id)->get();

                    foreach ($payment_details as $payment_detail){
                        $payment_detail->delete();
                    }
                }

                $payment->delete();
            }

            $booking_detail = BookingDetail::where('booking_id',$booking->id)->get();

            foreach($booking_detail as $detail){
                $detail->delete();
            }

            $booking->delete();
            DB::commit();

            return ResponseFormatter::success(null,'Berhasil Menghapus Booking');

        }catch(Exception $e){
            DB::rollBack();
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function booking_history(Request $request){
        try{
            $booking = Booking::where('user_id',Auth::user()->id)->orderBy('created_at','desc')->get();

            $booking_list =[];


            foreach($booking as $each_booking){

                $booking_detail = BookingDetail::where('booking_id',$each_booking->id)->orderBy('date','asc')->orderBy('court_id','asc')->orderBy('is_membership','asc')->orderBy('start_time','asc')->get();
                $current_date = Carbon::parse($each_booking->date)->format('Y-m-d');

                $current_court_id = null;
                $detail_per_date = null;
                $price_each_court = 0;
                $list_detail = [];
                $detail_temp = [];
                if($each_booking->is_membership > 0){
                    $detail_index=-1;
                    $total_detail = count($booking_detail);
                    $time_array =[];
                    $temp_start_time = null;
                    $temp_end_time = null;
                    foreach($booking_detail as $detail){
                        if(Carbon::parse($detail->date)->format('Y-m-d') == $current_date){
                            if($current_court_id == null || $current_court_id == $detail->court_id){

                                if($temp_start_time == null){
                                    $temp_start_time = Carbon::parse($detail->start_time)->format('H:i');
                                    $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');

                                }else{

                                    if(Carbon::parse($detail->start_time)->format('H:i') == $temp_end_time) {
                                        $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');
                                    }else{
                                        $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');
                                        $time_array[] = $temp_start_time . '-' . $temp_end_time;
                                    }
                                }
                            }else{
                                $time_array[] = $temp_start_time . '-' . $temp_end_time;
                                $data = [
                                    'court_id' => $booking_detail[$detail_index]->court_id,
                                    'court_name' => $booking_detail[$detail_index]->court->name,
                                    'time' => $time_array,
                                    'price' => $price_each_court
                                ];


                                $price_each_court = 0;
                                $time_array =null;

                                $list_detail[] = $data;

                                $temp_start_time = Carbon::parse($detail->start_time)->format('H:i');
                                $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');
                            }
                        }else{
                            $current_date = Carbon::parse($detail->date)->format('Y-m-d');

                            $current_court_id = $detail->court_id;

                            $time_array[] = $temp_start_time . '-' . $temp_end_time;

                            $data = [
                                'court_id' => $booking_detail[$detail_index]->court_id,
							    'court_name' => $booking_detail[$detail_index]->court->name,
							    'time' => $time_array,
							    'price' => $price_each_court
						    ];

						    $price_each_court = 0;
					    	$time_array =null;

    						$list_detail[] = $data;

	    					$data = [
                                'date' => Carbon::parse($booking_detail[$detail_index]->date)->format('Y-m-d'),
							    'list' => $list_detail
						    ];

                            $list_detail = [];

		    				$detail_temp[] = $data;

			    			$temp_start_time = Carbon::parse($detail->start_time)->format('H:i');
				    		$temp_end_time = Carbon::parse($detail->end_time)->format('H:i');
				    	}
                        $current_court_id = $detail->court_id;
                        $price_each_court += $detail->price - $detail->discount;
                        $detail_index++;
                    }

                }else{
                    $detail_index=-1;
                    $total_detail = count($booking_detail);
                    $time_array =[];
                    $temp_start_time = null;
                    $temp_end_time = null;
                    foreach($booking_detail as $detail){
                        if($current_court_id == null || $current_court_id == $detail->court_id){

                            if($temp_start_time == null){
                                $temp_start_time = Carbon::parse($detail->start_time)->format('H:i');
                                $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');

                            }else{

                                if(Carbon::parse($detail->start_time)->format('H:i') == $temp_end_time) {
                                    $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');
                                }else{
                                    $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');
                                    array_push($time_array, $temp_start_time . '-' . $temp_end_time);
                                }
                            }
                        }else{
                            array_push($time_array, $temp_start_time . '-' . $temp_end_time);
                            $data = [
                                'court_id' => $booking_detail[$detail_index]->court_id,
							    'court_name' => $booking_detail[$detail_index]->court->name,
							    'time' => $time_array,
							    'price' => $price_each_court
						    ];


    						$price_each_court = 0;
	    					$time_array =null;

		    				array_push($list_detail,$data);

	    					$temp_start_time = Carbon::parse($detail->start_time)->format('H:i');
		    				$temp_end_time = Carbon::parse($detail->end_time)->format('H:i');
			    		}
                        $current_court_id = $detail->court_id;
                        $price_each_court += $detail->price - $detail->discount;
                        $detail_index++;
                    }

                }
                $time_array[] = $temp_start_time . '-' . $temp_end_time;
                $data = [
                    'court_id' => $booking_detail[$detail_index]->court_id,
                    'court_name' => $booking_detail[$detail_index]->court->name,
                    'time' => $time_array,
                    'price' => $price_each_court
                ];
                $price_each_court = 0;
                $time_array =null;
                $list_detail[] = $data;
                $data = [
                    'date' => Carbon::parse($booking_detail[$detail_index]->date)->format('Y-m-d'),
                    'list' => $list_detail
                ];

                $detail_temp[] = $data;
                $booking_data = [
                    'court_partner' => [
                        'id' => $each_booking->court_partner_id,
                        'name' => $each_booking->court_partner->name
                    ],
                    'created_at' => Carbon::parse($each_booking->created_at)->format('Y-m-d'),
                    'booking_code' => $each_booking->booking_id,
                    'total_price' => $each_booking->total_payment - $each_booking->total_discount,
                    'is_paid' => boolval($each_booking->is_paid),
                    'status' => $each_booking->status,
                    'payment_type' => $each_booking->payment_type,
                    'detail' => $detail_temp
                ];

                $booking_list[] = $booking_data;
            }

		    return ResponseFormatter::success($booking_list,'Berhasil Mendapatkan Data Booking');


	    }catch(Exception $e){
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function check_booking(CheckBookingRequest $request)
    {
        try {

            $date = Carbon::parse($request->date)->format('Y-m-d');
            $date_day = Carbon::parse($request->date)->format('l');

            $temp_cart = [];

            $total_cart = count($request->cart);
            $total_booking_price = 0;

            for ($i = 0; $i < $total_cart; $i++) {

                foreach ($request->cart[$i]['time'] as $time_cart) {
                    $time = explode('-', $time_cart);

                    $start_time = Carbon::parse($time[0])->format('H:i');
                    $end_time = Carbon::parse($time[1])->format('H:i');
                    $court_price = CourtPrice::where('court_id', $request->cart[$i]['court_id'])->where('day', $date_day)->where('start_time', $start_time)->first();

                    $data = [
                        'court_id' => $request->cart[$i]['court_id'],
                        'court_name' => $court_price->court->name,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'price' => $court_price->price,
                        'discount' => 0
                    ];

                    $total_booking_price += $court_price->price;

                    array_push($temp_cart, $data);
                }
            }

            $day = 0;
            $date_list = [];
            $cart = [];
            if ($request->is_membership) {

                if ($request->quantity <= 0 || $request->quantity == null) {
                    return ResponseFormatter::error(null, 'Quantity tidak ditemukan');
                }

                $court_partner = CourtPartner::find($request->court_partner_id);
                if ($court_partner->membership_type == 'byfour') {
                    $current_date = $date;
                    for ($i = 1; $i <= $request->quantity; $i++) {
                        for ($j = 0; $j < 4; $j++) {
                            $data = [
                                'date' => Carbon::parse($current_date)->format('Y-m-d'),
                                'detail' => $temp_cart
                            ];

                            array_push($cart, $data);
                            $day++;
                            $current_date = Carbon::parse($current_date)->addDays(7);
                        }
                    }
                } else {

                    $current_date = $date;
                    $day = 0;
                    for ($i = 1; $i <= $request->quantity; $i++) {
                        $current_month = Carbon::parse($current_date)->format('m');
                        while (Carbon::parse($current_date)->format('m') == $current_month) {

                            $data = [
                                'date' => Carbon::parse($current_date)->format('Y-m-d'),
                                'detail' => $temp_cart
                            ];

                            array_push($cart, $data);
                            $day++;
                            $current_date = Carbon::parse($current_date)->addDays(7);
                        }
                    }
                }

                $total_booking_price = $total_booking_price * $day;
            } else {

                $data = [
                    'date' => Carbon::parse($date)->format('Y-m-d'),
                    'detail' => $temp_cart
                ];

                array_push($cart, $data);
            }

            if ($request->discount > 0) {
                if ($request->is_membership) {
                    $discount_per_day = $request->discount / $day;
                    $discount_per_detail = intval($discount_per_day / $total_cart);

                    foreach ($cart as $each_membership) {
                        foreach ($each_membership['detail'] as $detail) {
                            $detail['discount'] = $discount_per_detail;
                            $detail['discount_price'] = $detail['price'] - $discount_per_detail;
                        }
                    }
                } else {
                    $discount_per_detail = intval($request->discount / $total_cart);
                    $index_cart = 0;
                    foreach ($cart as $each_booking) {
                        $index_detail = 0;
                        foreach ($each_booking['detail'] as $detail) {
                            $cart[$index_cart]['detail'][$index_detail]['discount'] = $discount_per_detail;
                            $cart[$index_cart]['detail'][$index_detail]['discount_price'] = $detail['price'] - $discount_per_detail;
                            $index_detail++;
                        }
                        $index_cart++;
                    }
                }
            }

            $down_payment_price = 0;

            if ($request->payment_type == 'down-payment') {
                $court_partner = CourtPartner::where('user_id', Auth::user()->id)->first();
                if ($court_partner->down_payment_type == 'amount') {
                    if ($court_partner->down_payment_amount <= 0) {
                        return ResponseFormatter::error(null, 'Anda Belum Mengisi Jumlah Down Payment Tetap');
                    }

                    $down_payment_price = $court_partner->down_payment_amount;
                } else {
                    $down_payment_price = $total_booking_price * $court_partner->down_payment_percentage;
                    $down_payment_price = intval($down_payment_price / 100);
                }
            }


            return ResponseFormatter::success([
                'total_price' => $total_booking_price,
                'discount' => $request->discount,
                'is_membership' => $request->is_membership,
                'total_day' => $day,
                'cart' => $cart,
                'payment_type' => $request->payment_type,
                'down_payment_amount' => $down_payment_price,
            ], 'Berhasil Mendapatkan Kalkulasi Booking');

        } catch (Exception $e) {
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function incoming_schedule(Request $request){
        try{

            $date = $request->query('date') != null ? Carbon::parse($request->query('date'))->format('Y-m-d') : Carbon::now()->addDays(7)->format('Y-m-d');

            $booking_detail = BookingDetail::query()->whereHas('booking', function ($query){
                $query->where('user_id',Auth::user()->id);
            })->where('end_time','>',Carbon::now()->format('H:i'))->where('date','<=',$date)->where('date','>=',Carbon::now()->format('Y-m-d'))
                ->orderBy('date','asc')
                ->orderBy('booking_id','asc')
                ->orderBy('court_id','asc')
                ->orderBy('start_time','asc')->get();

            $schedule_list = [];
            $temp_start_time = null;
            $temp_end_time = null;
            $detail_index = -1;
            $current_booking_id = 0;
            $current_court_id = 0;
            $time_array =[];
            $current_date = null;
            if(count($booking_detail) > 0){
                $current_booking_id = $booking_detail[0]->booking_id;
                $current_court_id = $booking_detail[0]->court_id;
                $temp_start_time = Carbon::parse($booking_detail[0]->start_time)->format('H:i');
                $temp_end_time = Carbon::parse($booking_detail[0]->end_time)->format('H:i');
                $current_date = Carbon::parse($booking_detail[0]->date)->format('Y-m-d');
            }

            $list_detail = [];
            $detail_temp = [];

            $five_schedule = 0;

            $done = false;

            foreach($booking_detail as $detail){

                $done = false;

                if($current_booking_id == $detail->booking_id ){

                    if($current_court_id == $detail->court_id){

                        if($temp_start_time == null){
                            $temp_start_time = Carbon::parse($detail->start_time)->format('H:i');
                            $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');

                        }else{

                            if(Carbon::parse($detail->start_time)->format('H:i') == $temp_end_time) {
                                $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');
                            }else{
                                $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');
                                $time_array[] = $temp_start_time . '-' . $temp_end_time;
                            }
                        }
                    }else{
                        $time_array[] = $temp_start_time . '-' . $temp_end_time;
                        $data = [
                            'court_id' => $booking_detail[$detail_index]->court_id,
                            'court_name' => $booking_detail[$detail_index]->court->name,
                            'time' => $time_array,
                        ];

                        $time_array =null;

                        $list_detail[] = $data;

                        $temp_start_time = Carbon::parse($detail->start_time)->format('H:i');
                        $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');

                    }

                }else if($current_booking_id != $detail->booking_id){

                    $time_array[] = $temp_start_time . '-' . $temp_end_time;
                    $data = [
                        'court_id' => $booking_detail[$detail_index]->court_id,
                        'court_name' => $booking_detail[$detail_index]->court->name,
                        'time' => $time_array,
                    ];

                    $time_array =null;
                    $list_detail[] = $data;
                    $data = [
                        'date' => Carbon::parse($booking_detail[$detail_index]->date)->format('Y-m-d'),
                        'list' => $list_detail
                    ];

                    $list_detail = [];

                    $detail_temp[] = $data;
                    $booking_data = [
                        'court_partner' => [
                            'id' => $booking_detail[$detail_index]->booking->court_partner_id,
                            'name' => $booking_detail[$detail_index]->booking->court_partner->name
                        ],
                        'booking_code' => $booking_detail[$detail_index]->booking->booking_id,
                        'is_paid' => boolval($booking_detail[$detail_index]->booking->is_paid),
                        'detail' => $detail_temp
                    ];
                    $done = true;
                    $detail_temp= [];
                    $schedule_list[] = $booking_data;

                    $temp_start_time = Carbon::parse($detail->start_time)->format('H:i');
                    $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');

                    $five_schedule++;

                    if($five_schedule >= 5){
                        break;
                    }

                }else if($current_court_id != $detail->court_id){

                    $current_court_id = $detail->court_id;

                    $time_array[] = $temp_start_time . '-' . $temp_end_time;

                    $data = [
                        'court_id' => $booking_detail[$detail_index]->court_id,
                        'court_name' => $booking_detail[$detail_index]->court->name,
                        'time' => $time_array,
                    ];

                    $time_array =null;

                    $list_detail[] = $data;

                    $data = [
                        'date' => Carbon::parse($booking_detail[$detail_index]->date)->format('Y-m-d'),
                        'list' => $list_detail
                    ];

                    $list_detail = [];

                    $detail_temp[] = $data;

                    $temp_start_time = Carbon::parse($detail->start_time)->format('H:i');
                    $temp_end_time = Carbon::parse($detail->end_time)->format('H:i');
                }

                $current_booking_id = $detail->booking_id;
                $current_court_id = $detail->court_id;
                $current_date = Carbon::parse($booking_detail[0]->date)->format('Y-m-d');
                $detail_index++;
            }

            if(!$done && count($booking_detail) > 0 && $five_schedule< 5){
                $detail_index--;
                $time_array[] = $temp_start_time . '-' . $temp_end_time;
                $data = [
                    'court_id' => $booking_detail[$detail_index]->court_id,
                    'court_name' => $booking_detail[$detail_index]->court->name,
                    'time' => $time_array,
                ];
                $time_array =null;
                $list_detail[] = $data;
                $data = [
                    'date' => Carbon::parse($booking_detail[$detail_index]->date)->format('Y-m-d'),
                    'list' => $list_detail
                ];

                $detail_temp[] = $data;
                $booking_data = [
                    'court_partner' => [
                        'id' => $booking_detail[$detail_index]->booking->court_partner_id,
                        'name' => $booking_detail[$detail_index]->booking->court_partner->name
                    ],
                    'booking_code' => $booking_detail[$detail_index]->booking->booking_id,
                    'is_paid' => boolval($booking_detail[$detail_index]->booking->is_paid),
                    'detail' => $detail_temp
                ];

                $schedule_list[] = $booking_data;
            }

            return ResponseFormatter::success($schedule_list,'Berhasil Mendapatkan Incoming Schedule');
        } catch (Exception $e) {
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

}
