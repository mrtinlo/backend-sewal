<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Payment;
use App\Models\PaymentDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class MidtransController extends Controller
{
    public function callback(Request $req){
        try{

            $request = json_decode($req->getContent(), true);

            $reference_id = explode('/',$request['order_id']);

            $booking_id = $reference_id[0];
            $payment_type = $reference_id[1] == 'schedule' ? true : false;

            $booking = Booking::where('booking_id',$booking_id)->first();

            DB::beginTransaction();

            $status_code = $request['status_code'];

            if($status_code == '200'){

                $payment_id = 'Payment-' . Carbon::now()->format('YmdHis');

                if($booking->status != 'Lunas'){
                    if($payment_type){
                        $booking->is_paid = true;
                        $booking->status = 'Lunas';
                        $booking->save();

                        $payment = Payment::Create([
                            'booking_id' => $booking->id,
                            'amount' => intval($request['gross_amount']),
                            'type' => 'schedule',
                            'payment_link' => $request['transaction_id'],
                            'payment_method' => 'qris',
                            'payment_id' => $payment_id
                        ]);

                        $booking_detail = BookingDetail::where('booking_id',$booking->id)->get();

                        foreach($booking_detail as $detail){

                            $payment_detail = PaymentDetail::Create([
                                'payment_id' => $payment->id,
                                'booking_detail_id' => $detail->id,
                                'amount' => $detail->price - $detail->discount,
                            ]);

                            $booking_detail_to_is_paid = BookingDetail::find($detail->id);
                            $booking_detail_to_is_paid->is_paid = true;
                            $booking_detail_to_is_paid->save();

                            $detail->is_paid = true;
                            $detail->save();
                        }
                    }else{
                        $booking->status = 'DP Lunas';
                        $booking->save();

                        $payment = Payment::Create([
                            'booking_id' => $booking->id,
                            'amount' => intval($request['gross_amount']),
                            'type' => 'down-payment',
                            'payment_link' => $request['transaction_id'],
                            'payment_method' => 'qris',
                            'payment_id' => $payment_id
                        ]);
                    }
                }

            }else if($status_code != '201'){

                if($booking->status == 'Menunggu Pembayaran'){
                    $booking_detail = BookingDetail::where('booking_id',$booking->id)->get();

                    foreach($booking_detail as $detail){
                        $detail->delete();
                    }
                    $booking->status = 'Cancel';
                    $booking->delete();
                }
            }

            DB::commit();

            return ResponseFormatter::success(null,'Berhasil Mendapatkan Response Midtrans');

        }catch(Exception $e){
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }
}
