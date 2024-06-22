<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Payment;
use App\Models\PaymentDetail;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class IpaymuController extends Controller
{
    public function callback(Request $request){
        try{

            $reference_id = explode('/',$request->reference_id);

            $booking_id = $reference_id[0];
            $payment_id = $reference_id[1];

            $booking = Booking::find($booking_id);

            $payment = Payment::find($payment_id);

            DB::beginTransaction();

            if($request->status_code == 1){
                if($payment->type == 'down-payment'){
                    $booking->status = 'DP Lunas';
                    $booking->save();
                }else if($payment->type == 'schedule'){
                    $booking->status = 'Lunas';
                    $booking->is_paid = true;
                    $booking->save();

                    $booking_detail = BookingDetail::where('booking_id',$booking->id)->get();

                    foreach($booking_detail as $detail){
                        $detail->is_paid = true;
                        $detail->save();
                    }
                }

            }else if($request->status_code != 0){
                $payment_detail = PaymentDetail::where('payment_id',$payment->id)->get();

                foreach($payment_detail as $detail){
                    $detail->delete();
                }

                $payment->delete();

                $booking_detail = BookingDetail::where('booking_id',$booking->id)->get();

                foreach($booking_detail as $detail){
                    $detail->delete();
                }

                $booking->delete();
            }

            DB::commit();

            return ResponseFormatter::success(null,'Berhasil Mendapatkan Response Ipaymu');

        }catch(Exception $e){
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }
}
