<?php

namespace App\Http\Controllers\Partner;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Court;
use App\Models\CourtPartner;
use App\Models\CourtType;
use App\Models\Payment;
use App\Models\UnregisterUser;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function header_transaction(Request $request){
        try{

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $court_type_id = $request->query('court_type_id');
            $start_date = $request->query('start_date');
            $end_date = $request->query('end_date');
            $court_id = $request->query('court_id');

            $court_type = CourtType::find($court_type_id);
            if($court_type == null){
                return ResponseFormatter::error(null,'Court Type tidak ditemukan');
            }

            $date_start = $start_date ? Carbon::parse($start_date)->startOfDay()->format('Y-m-d H:i:s') : Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $date_end = $end_date ? Carbon::parse($end_date)->endOfDay()->format('Y-m-d H:i:s') : Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

            if($court_id != null){
                $court = Court::find($court_id);
                if($court == null || $court->court_partner_id != $court_partner->id){
                    return ResponseFormatter::error(null,'Court tidak ditemukan');
                }

                $total_payment = DB::select('select sum(payments.amount) as amount
                                                    from payments
                                                    where id in (SELECT payments.id
                                                                 FROM `payments`
                                                                     join bookings on payments.booking_id = bookings.id
                                                                     join booking_details on booking_details.booking_id = bookings.id
                                                                 where booking_details.court_id = ?
                                                                   and payments.created_at >= ?
                                                                   and payments.created_at <= ?)',[$court->id, $date_start, $date_end]);

                $total_payment = intval($total_payment[0]->amount);

                $total_unpaid_booking = Booking::join('booking_details','payment_details.booking_detail_id','=','booking_details.id')
                    ->where('booking_details.court_id',$court_id)
                    ->where('bookings.is_membership',0)
                    ->whereRaw('bookings.date <= ? and bookings.date >= ?',[$date_end,$date_start])
                    ->where('bookings.is_paid',0)->get();

                $total_unpayment = 0;
                $current_booking_id = null;
                foreach($total_unpaid_booking as $booking){

                    if($current_booking_id != $booking->id) {
                        if($booking->payment_type == 'no-payment'){
                            $total_unpayment += $booking->total_payment - $booking->total_discount;
                        }else if($booking->payment_type == 'down-payment'){
                            $payment = Payment::where('type','down-payment')->where('booking_id',$booking->id)->first();

                            $total_unpayment += $booking->total_payment - $booking->total_discount - $payment->amount;
                        }

                        $current_booking_id = $total_unpaid_booking[0]->id;
                    }
                }

                $total_reservation_hour =  BookingDetail::where('court_id',$court_id)
                    ->where('created_at','>=',$date_start)
                    ->where('created_at','<=',$date_end)->count();
            }else{

                $total_payment = DB::select('select sum(payments.amount) as amount
                                                    from payments
                                                    where id in (SELECT payments.id
                                                                 FROM `payments`
                                                                     join bookings on payments.booking_id = bookings.id
                                                                     join booking_details on booking_details.booking_id = bookings.id
                                                                     join courts on booking_details.court_id = courts.id
                                                                 where courts.court_type_id = ?
                                                                   and bookings.court_partner_id = ?
                                                                   and payments.created_at >= ?
                                                                   and payments.created_at <= ?)',[$court_type_id,$court_partner->id, $date_start, $date_end]);
                $total_payment = intval($total_payment[0]->amount);

                $total_unpaid_booking = Booking::join('booking_details','bookings.id','=','booking_details.booking_id')
                    ->join('courts','booking_details.court_id','=','courts.id')
                    ->where('courts.court_type_id',$court_type_id)
                    ->where('bookings.court_partner_id',$court_partner->id)
                    ->where('bookings.is_membership',0)
                    ->whereRaw('bookings.date <= ? and bookings.date >= ?',[$date_end,$date_start])
                    ->where('bookings.is_paid',0)->orderBy('bookings.id','asc')->get();
                $total_unpayment = 0;
                $current_booking_id = null;
                foreach($total_unpaid_booking as $booking){
                    if($current_booking_id != $booking->booking_id) {
                        if($booking->payment_type == 'no-payment'){
                            $total_unpayment += $booking->total_payment - $booking->total_discount;
                        }else if($booking->payment_type == 'down-payment'){
                            $total_unpayment += $booking->total_payment - $booking->total_discount - $booking->down_payment;
                        }

                        $current_booking_id = $total_unpaid_booking[0]->booking_id;
                    }
                }

                $total_reservation_hour =  BookingDetail::join('courts','booking_details.court_id','=','courts.id')
                    ->where('courts.court_type_id',$court_type_id)
                    ->where('courts.court_partner_id',$court_partner->id)
                    ->where('booking_details.created_at','>=',$date_start)
                    ->where('booking_details.created_at','<=',$date_end)->count();
            }

            return ResponseFormatter::success([
                'total_paid' => $total_payment,
                'total_unpaid' => $total_unpayment,
                'total_reservation_hour' => $total_reservation_hour
            ],'Berhasil mendapatkan Header Transaction');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function data_transaction(Request $request){
        try{

            $court_type_id = $request->query('court_type_id');
            $start_date = $request->query('start_date');
            $end_date = $request->query('end_date');
            $court_id = $request->query('court_id');

            $un_paid = boolval($request->query('un_paid'));

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $court_type = CourtType::find($court_type_id);
            if($court_type == null){
                return ResponseFormatter::error(null,'Court Type tidak ditemukan');
            }

            $date_start = $start_date ? Carbon::parse($start_date)->startOfDay()->format('Y-m-d H:i:s') : Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $date_end = $end_date ? Carbon::parse($end_date)->endOfDay()->format('Y-m-d H:i:s') : Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

            $transaction_list = [];
            if($court_id != null){

                if($un_paid){

                    $list = Booking::with('payment')->whereRaw('
                        bookings.id in (SELECT payments.booking_id
                         FROM `payments`
                             join bookings on payments.booking_id = bookings.id
                             join booking_details on booking_details.booking_id = bookings.id
                         where booking_details.court_id = ?
                           and bookings.is_paid = 0
                           and payments.created_at >= ?
                           and payments.created_at <= ?)',[$court_id, $date_start, $date_end])->get();

                    foreach($list as $booking){

                        if($booking->user->name == null){
                            $user = UnregisterUser::select('name')->where('user_id',$booking->user_id)->where('court_partner_id',$court_partner->id)->first();
                        }

                        $total_amount = 0;
                        foreach($booking->payment as $payment){
                            $total_amount += $payment->amount;
                        }

                        $transaction_list[] = [
                            'booking_id' => $booking->id,
                            'user_name' => $booking->user->name != null ? $booking->user->name : $user->name,
                            'paid_amount' => $total_amount,
                            'booking_amount' => $booking->total_payment,
                            'date' => $booking->date,
                            'payment_type' => $booking->payment_type,
                            'is_paid' => boolval($booking->is_paid)
                        ];
                    }

                }else{

                    $list = Booking::with('payment')->whereRaw('
                        bookings.id in (SELECT payments.booking_id
                         FROM `payments`
                             join bookings on payments.booking_id = bookings.id
                             join booking_details on booking_details.booking_id = bookings.id
                         where booking_details.court_id = ?
                           and payments.created_at >= ?
                           and payments.created_at <= ?)',[$court_id, $date_start, $date_end])->get();

                    foreach($list as $booking){

                        if($booking->user->name == null){
                            $user = UnregisterUser::select('name')->where('user_id',$booking->user_id)->where('court_partner_id',$court_partner->id)->first();
                        }

                        $total_amount = 0;
                        foreach($booking->payment as $payment){
                            $total_amount += $payment->amount;
                        }

                        $transaction_list[] = [
                            'booking_id' => $booking->id,
                            'user_name' => $booking->user->name != null ? $booking->user->name : $user->name,
                            'paid_amount' => $total_amount,
                            'booking_amount' => $booking->total_payment,
                            'date' => $booking->date,
                            'payment_type' => $booking->payment_type,
                            'is_paid' => boolval($booking->is_paid)
                        ];
                    }
                }

            }else{

                if($un_paid){

                    $list = Booking::with('payment')->whereRaw('
                        bookings.id in (SELECT payments.booking_id
                         FROM `payments`
                             join bookings on payments.booking_id = bookings.id
                             join booking_details on booking_details.booking_id = bookings.id
                             join courts on booking_details.court_id = courts.id
                         where courts.court_type_id = ?
                           and bookings.is_paid = 0
                           and bookings.court_partner_id = ?
                           and payments.created_at >= ?
                           and payments.created_at <= ?)',[$court_type_id,$court_partner->id, $date_start, $date_end])->get();

                    foreach($list as $booking){

                        if($booking->user->name == null){
                            $user = UnregisterUser::select('name')->where('user_id',$booking->user_id)->where('court_partner_id',$court_partner->id)->first();
                        }

                        $total_amount = 0;
                        foreach($booking->payment as $payment){
                            $total_amount += $payment->amount;
                        }

                        $transaction_list[] = [
                            'booking_id' => $booking->id,
                            'user_name' => $booking->user->name != null ? $booking->user->name : $user->name,
                            'paid_amount' => $total_amount,
                            'booking_amount' => $booking->total_payment,
                            'date' => $booking->date,
                            'payment_type' => $booking->payment_type,
                            'is_paid' => boolval($booking->is_paid)
                        ];
                    }

                }else{

                    $list = Booking::with('payment')->whereRaw('
                        bookings.id in (SELECT payments.booking_id
                         FROM `payments`
                             join bookings on payments.booking_id = bookings.id
                             join booking_details on booking_details.booking_id = bookings.id
                             join courts on booking_details.court_id = courts.id
                         where courts.court_type_id = ?
                           and bookings.court_partner_id = ?
                           and payments.created_at >= ?
                           and payments.created_at <= ?)',[$court_type_id,$court_partner->id, $date_start, $date_end])->get();

                    foreach($list as $booking){

                        if($booking->user->name == null){
                            $user = UnregisterUser::select('name')->where('user_id',$booking->user_id)->where('court_partner_id',$court_partner->id)->first();
                        }

                        $total_amount = 0;
                        foreach($booking->payment as $payment){
                            $total_amount += $payment->amount;
                        }

                        $transaction_list[] = [
                            'booking_id' => $booking->id,
                            'user_name' => $booking->user->name != null ? $booking->user->name : $user->name,
                            'paid_amount' => $total_amount,
                            'booking_amount' => $booking->total_payment,
                            'date' => $booking->date,
                            'payment_type' => $booking->payment_type,
                            'is_paid' => boolval($booking->is_paid)
                        ];
                    }
                }
            }

            return ResponseFormatter::success($transaction_list, 'Berhasil Mendapatkan data Transaction');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function transaction_detail($booking_id){
        try{

            $booking = Booking::find($booking_id);

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            if($booking->court_partner_id != $court_partner->id){
                return ResponseFormatter::error(null, 'Booking ini bukan milik anda');
            }

            if($booking->user->name == null){
                $user = UnregisterUser::select('name')->where('user_id',$booking->user_id)->where('court_partner_id',$court_partner->id)->first();
            }

            $booking_detail_list = BookingDetail::where('booking_id',$booking->id)->orderBy('is_membership','asc')->orderBy('court_id','asc')->orderBy('start_time','asc')->get();

            $payment = Payment::where('booking_id',$booking->id)->select('type','payment_method','amount','created_at')->get();

            $payment_list = [];

            foreach($payment as $each_payment){
                $payment_list[] = [
                    'payment_type' => $each_payment->type == 'schedule' ? 'Melunasi' : 'Down Payment',
                    'payment_method' => $each_payment->payment_method,
                    'amount' => $each_payment->amount,
                    'created_at' => Carbon::parse($each_payment->created_at)->format('H:i:s d-m-Y')
                ];
            }

            $booking_detail = [];

            $current_court_id = null;
            $current_court_name = null;
            $time_list = [];
            $current_date = null;
            $total_price = 0;

            $transaction_detail = [];

            if($booking->is_membership){
                foreach($booking_detail_list as $detail){
                    if($current_date == null){
                        $current_date = Carbon::parse($detail->date)->format('d-m-Y');

                        $current_court_id = $detail->court_id;
                        $current_court_name = $detail->court->name;
                    }else if($current_date !=  Carbon::parse($detail->date)->format('d-m-Y')){
                        $booking_detail[] = [
                            'court_name' => $current_court_name,
                            'time_list' => $time_list,
                            'price' => $total_price
                        ];

                        $data = [
                            'detail' => $booking_detail,
                            'date' => $current_date
                        ];

                        array_push($transaction_detail, $data);

                        $total_price = 0;
                        $time_list = [];
                        $booking_detail = [];

                        $current_court_id = $detail->court_id;
                        $current_court_name = $detail->court->name;
                        $current_date = Carbon::parse($detail->date)->format('d-m-Y');
                    }else if($current_court_id != $detail->court_id){
                        $booking_detail[] = [
                            'court_name' => $current_court_name,
                            'time_list' => $time_list,
                            'price' => $total_price
                        ];

                        $total_price = 0;
                        $time_list = [];

                        $current_court_id = $detail->court_id;
                        $current_court_name = $detail->court->name;
                    }

                    array_push($time_list, $detail->start_time.'-'.$detail->end_time);
                    $total_price += $detail->price;
                }

                $booking_detail[] = [
                    'court_name' => $current_court_name,
                    'time_list' => $time_list,
                    'price' => $total_price
                ];

                $data = [
                    'detail' => $booking_detail,
                    'date' => $current_date
                ];

                array_push($transaction_detail, $data);
            }else{
                foreach($booking_detail_list as $detail){
                    if($current_court_id == null && $current_court_id != $detail->court_id){
                        $current_court_id = $detail->court_id;
                        $current_court_name = $detail->court->name;

                        $current_date = Carbon::parse($detail->date)->format('d-m-Y');

                    }else if($current_court_id != $detail->court_id){
                        $booking_detail[] = [
                            'court_name' => $current_court_name,
                            'time_list' => $time_list,
                            'price' => $total_price
                        ];

                        $total_price = 0;
                        $time_list = [];

                        $current_court_id = $detail->court_id;
                        $current_court_name = $detail->court->name;

                    }

                    array_push($time_list, $detail->start_time.'-'.$detail->end_time);
                    $total_price += $detail->price;
                }

                $booking_detail[] = [
                    'court_name' => $current_court_name,
                    'time_list' => $time_list,
                    'price' => $total_price
                ];

                $data = [
                    'detail' => $booking_detail,
                    'date' => $current_date
                ];

                array_push($transaction_detail, $data);
            }

            return ResponseFormatter::success([
                'booking_id' => $booking->id,
                'user_name' => $booking->user->name != null ? $booking->user->name : $user->name,
                'user_phone' => $booking->user->phone,
                'is_paid' => boolval($booking->is_paid),
                'payment_list' => $payment_list,
                'booking_detail' => $transaction_detail,
                'total_payment' => $booking->total_payment
            ],'Berhasil Mendapatkan Transaction Detail');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }
}
