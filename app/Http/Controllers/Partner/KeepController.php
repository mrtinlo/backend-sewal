<?php

namespace App\Http\Controllers\Partner;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\Keep\CheckKeepRequest;
use App\Http\Requests\Partner\Keep\CreateKeepRequest;
use App\Http\Requests\Partner\Keep\TransferKeepToRequest;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Court;
use App\Models\CourtPartner;
use App\Models\CourtPrice;
use App\Models\Keep;
use App\Models\KeepDetail;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\UnregisterUser;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Str;

class KeepController extends Controller
{
    public function check_keep(CheckKeepRequest $request){
        try{

            $date = Carbon::parse($request->date)->format('Y-m-d');
            $date_day = Carbon::parse($request->date)->format('l');

            $temp_cart = [];

            $total_cart = count($request->cart);

            for($i=0;$i<$total_cart;$i++){

                foreach($request->cart[$i]['time'] as $time_cart){
                    $time = explode('-',$time_cart);

                    $start_time = Carbon::parse($time[0])->format('H:i');
                    $end_time = Carbon::parse($time[1])->format('H:i');

                    $court = Court::find($request->cart[$i]['court_id'])->select('name')->first();
                    $data = [
                        'court_id' => $request->cart[$i]['court_id'],
                        'court_name' => $court->name,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                    ];

                    array_push($temp_cart,$data);
                }
            }

            $day = 0;
            $date_list = [];
            $cart = [];

            $data = [
                'date' => Carbon::parse($date)->format('Y-m-d'),
                'detail' => $temp_cart
            ];

            array_push($cart,$data);

//            if($request->is_membership){
//
//                if($request->quantity <= 0 || $request->quantity == null){
//                    return ResponseFormatter::error('Quantity tidak ditemukan');
//                }
//
//                $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();
//                if($court_partner->membership_type == 'byfour'){
//                    $current_date = $date;
//                    for($i=1;$i<=$request->quantity;$i++){
//                        for($j=0;$j<4;$j++){
//                            $data = [
//                                'date' => Carbon::parse($current_date)->format('Y-m-d'),
//                                'detail' => $temp_cart
//                            ];
//
//                            array_push($cart,$data);
//                            $day++;
//                            $current_date = Carbon::parse($current_date)->addDays(7);
//                        }
//                    }
//                }else{
//
//                    $current_date = $date;
//                    $day=0;
//                    for($i=1;$i<=$request->quantity;$i++){
//                        $current_month = Carbon::parse($current_date)->format('m');
//                        while(Carbon::parse($current_date)->format('m') == $current_month){
//
//                            $data = [
//                                'date' => Carbon::parse($current_date)->format('Y-m-d'),
//                                'detail' => $temp_cart
//                            ];
//
//                            array_push($cart,$data);
//                            $day++;
//                            $current_date = Carbon::parse($current_date)->addDays(7);
//                        }
//                    }
//                }
//
//            }else{
//
//                $data = [
//                    'date' => Carbon::parse($date)->format('Y-m-d'),
//                    'detail' => $temp_cart
//                ];
//
//                array_push($cart,$data);
//            }

            return ResponseFormatter::success([
                'cart' => $cart,
            ],'Berhasil Mendapatkan Kalkulasi Keep');

        }catch (Exception $e){
            DB::rollBack();
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function save_keep(CreateKeepRequest $request){
        try{

            DB::beginTransaction();
            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $user = User::where('phone',$request->phone)->first();
            $cart = $request->cart;

            if($user == null){
                $red = rand(0, 255);
                $green = rand(0, 255);
                $blue = rand(0, 255);

                $complementaryRed = 255 - $red;
                $complementaryGreen = 255 - $green;
                $complementaryBlue = 255 - $blue;

                $complementaryColor = sprintf("#%02x%02x%02x", $complementaryRed, $complementaryGreen, $complementaryBlue);

                $db_user = User::where('color', $complementaryColor)->get();

                if(count($db_user) > 0){

                    while(count($db_user) > 0){
                        $red = rand(0, 255);
                        $green = rand(0, 255);
                        $blue = rand(0, 255);

                        $complementaryRed = 255 - $red;
                        $complementaryGreen = 255 - $green;
                        $complementaryBlue = 255 - $blue;

                        $complementaryColor = sprintf("#%02x%02x%02x", $complementaryRed, $complementaryGreen, $complementaryBlue);

                        $db_user = User::where('color', $complementaryColor)->get();
                    }

                }

                $user = User::Create([
                    'phone' => $request->phone,
                    'color' => $complementaryColor
                ]);

            }

            if($user->name == null){

                $unregistered_user = UnregisterUser::where('user_id',$user->id)->where('court_partner_id',$court_partner->id)->first();

                if($unregistered_user == null){
                    $unregistered_user = UnregisterUser::Create([
                        'court_partner_id' => $court_partner->id,
                        'name' => $request->name,
                        'user_id' => $user->id
                    ]);
                }
            }

            $keep_id = 'Keep-'.Carbon::now()->format('YmdHis');

            $keep = Keep::Create([
                'user_id' => $user->id,
                'court_partner_id' => $court_partner->id,
                'date' => $cart[0]['date'],
                'is_membership' => 0,
                'keep_id' => $keep_id
            ]);

            foreach($cart[0]['detail'] as $each_cart){
                $check_booking_detail = BookingDetail::where('court_id',$each_cart['court_id'])->where('start_time',Carbon::parse($each_cart['start_time'])->format('H:i'))->where('date',Carbon::parse($cart[0]['date'])->format('Y-m-d'))->first();

                if($check_booking_detail != null){
                    return ResponseFormatter::error(null, $each_cart['court_name'].' pada tanggal '.Carbon::parse($cart[0]['date'])->format('d-m-Y').' jam '.Carbon::parse($each_cart['start_time'])->format('H:i'). ' sudah dibooking');
                }

                $check_keep_detail = KeepDetail::where('court_id',$each_cart['court_id'])->where('start_time',Carbon::parse($each_cart['start_time'])->format('H:i'))->where('date',Carbon::parse($cart[0]['date'])->format('Y-m-d'))->first();

                if($check_keep_detail != null){
                    return ResponseFormatter::error(null, $each_cart['court_name'].' pada tanggal '.Carbon::parse($cart[0]['date'])->format('d-m-Y').' jam '.Carbon::parse($each_cart['start_time'])->format('H:i'). ' sudah dikeep');
                }

                $keep_detail = KeepDetail::Create([
                    'start_time' => Carbon::parse($each_cart['start_time'])->format('H:i'),
                    'date' => $cart[0]['date'],
                    'end_time' => Carbon::parse($each_cart['end_time'])->format('H:i'),
                    'court_id' => $each_cart['court_id'],
                    'keep_id' => $keep->id,
                    'is_membership' => 0
                ]);

            }

            DB::commit();

            return ResponseFormatter::success(null, 'Berhasil Membuat Keep Baru');

        }catch (Exception $e){
            DB::rollBack();
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function transfer_keep_to_book(TransferKeepToRequest $request){
        try{

            DB::beginTransaction();

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $keep = Keep::find($request->keep_id);

            if($keep->court_partner_id != $court_partner->id){
                return ResponseFormatter::error(null,'Keep ini bukan milik anda');
            }

            $user = User::where('phone',$request->phone)->first();

            if($user == null){
                $user = User::Create([
                    'phone' => $request->phone
                ]);

            }

            if($user->name == null){

                $unregistered_user = UnregisterUser::where('user_id',$user->id)->where('court_partner_id',$court_partner->id)->first();

                if($unregistered_user == null){
                    $unregistered_user = UnregisterUser::Create([
                        'court_partner_id' => $court_partner->id,
                        'name' => $request->name,
                        'user_id' => $user->id
                    ]);
                }
            }

            $booking_id = 'Booking-'.Carbon::now()->format('YmdHis');

            if(($request->payment_type == 'full-payment' || $request->payment_type == 'down-payment') && $request->payment_method == null){
                return ResponseFormatter::error(null, 'Metode Pembayaran harus diisi');
            }

            $booking = Booking::Create([
                'user_id' => $user->id,
                'court_partner_id' => $court_partner->id,
                'total_payment' => 0,
                'date' => $keep->date,
                'payment_type' => $request->payment_type,
                'is_membership' => 0,
                'total_discount' => $request->discount ? $request->discount : 0,
                'is_paid' => false,
                'booking_id' => $booking_id
            ]);


            $keep_detail = KeepDetail::where('keep_id',$keep->id)->get();

            $each_discount = 0;

            if($booking->total_discount > 0){
                $total_keep_detail = count($keep_detail);

                $each_discount = intval($booking->total_discount/$total_keep_detail);
            }

            $booking_detail_list = [];

            foreach($keep_detail as $detail){

                $check_booking_detail = BookingDetail::where('court_id',$detail['court_id'])->where('start_time',Carbon::parse($detail['start_time'])->format('H:i'))->where('date',Carbon::parse($detail['date'])->format('Y-m-d'))->first();

                if($check_booking_detail != null){
                    return ResponseFormatter::error(null,$detail->court->name.' pada tanggal '.Carbon::parse($detail['date'])->format('d-m-Y').' jam '.Carbon::parse($detail['start_time'])->format('H:i'). ' sudah dibooking');
                }

                $court_price = CourtPrice::where('court_id',$detail->court_id)->where('day', Carbon::parse($detail->date)->format('l'))->where('start_time',$detail->start_time)->first();

                $booking_detail = BookingDetail::create([
                    'start_time' => Carbon::parse($detail['start_time'])->format('H:i'),
                    'date' => $detail['date'],
                    'end_time' => Carbon::parse($detail['end_time'])->format('H:i'),
                    'court_id' => $detail->court_id,
                    'price' => $court_price->price,
                    'discount' => $each_discount,
                    'booking_id' => $booking->id,
                    'is_membership' => 0
                ]);

                array_push($booking_detail_list,$booking_detail);

                $detail->delete();
            };

            $keep->delete();

            $payment_id = 'Payment-'.Carbon::now()->format('YmdHis');

            if($request->payment_type == 'full-payment'){

                $payment = Payment::Create([
                    'booking_id' => $booking->id,
                    'amount' => $request->total_price - $request->discount,
                    'type' => 'schedule',
                    'payment_method' => $request->payment_method,
                    'payment_id' => $payment_id
                ]);

                foreach($booking_detail_list as $detail){
                    $payment_detail = PaymentDetail::Create([
                        'payment_id' => $payment->id,
                        'booking_detail_id' => $detail->id,
                        'amount' => $detail->price - $detail->discount,
                    ]);

                    $booking_detail_to_is_paid = BookingDetail::find($detail->id);
                    $booking_detail_to_is_paid->is_paid = true;
                    $booking_detail_to_is_paid->save();
                }

                $booking->is_paid = true;
                $booking->save();

            }else if($request->payment_type == 'down-payment'){

                if($request->down_payment_amount <= 0){
                    return ResponseFormatter::error(null,'Jumlah DP harus lebih besar dari 0');
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

            return ResponseFormatter::success(null, 'Berhasil Mengubah Keep Menjadi Booking');

        }catch (Exception $e){
            DB::rollBack();
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function cancel_keep($id){
        try{

            DB::beginTransaction();

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $keep = Keep::find($id);

            if($keep->court_partner_id != $court_partner->id){
                return ResponseFormatter::error(null,'Keep ini bukan milik anda');
            }

            $keep_detail = KeepDetail::where('keep_id',$keep->id)->get();

            foreach($keep_detail as $detail){
                $detail->delete();
            }

            $keep->delete();

            DB::commit();

            return ResponseFormatter::success(null, 'Berhasil Menghapus Keep');

        }catch (Exception $e){
            DB::rollBack();
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }
}
