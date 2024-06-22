<?php

namespace App\Http\Controllers\Partner;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\Booking\BookingPaymentRequest;
use App\Http\Requests\Partner\Booking\CheckBookingRequest;
use App\Http\Requests\Partner\Booking\CreateBookingRequest;
use App\Http\Requests\Partner\Booking\CreateMembershipRequest;
use App\Http\Requests\Partner\Booking\HomeRequest;
use App\Http\Requests\Partner\Booking\ScheduleRequest;
use App\Http\Resources\Partner\HomeResource;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Court;
use App\Models\CourtPartner;
use App\Models\CourtPrice;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\UnregisterUser;
use App\Models\User;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{

    public function home($court_type_id, $date = null)
    {
        try {

            $court_partner = CourtPartner::where('user_id', Auth::user()->id)->first();
            if ($court_partner == null) {
                return ResponseFormatter::error(
                    null,
                    'General Error',
                    500
                );
            }

            $date = $date ? Carbon::parse($date)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
            $data = Court::where('court_partner_id', $court_partner->id)->where('status',true)->where('court_type_id', $court_type_id)->get()->map(function ($query) use ($date) {
                $query['date'] = $date;
                return $query;
            });

            $data = HomeResource::collection($data);

            return ResponseFormatter::success($data, 'Berhasil Mendapatkan Data Home');

        } catch (Exception $e) {
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
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

                $court_partner = CourtPartner::where('user_id', Auth::user()->id)->first();

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

    public function save_booking(CreateBookingRequest $request)
    {
        try {
            DB::beginTransaction();
            $court_partner = CourtPartner::where('user_id', Auth::user()->id)->first();

            $user = User::where('phone', $request->phone)->first();
            $cart = $request->cart;

            if($request->payment_type != 'no-payment' && $request->payment_method == null){
                return ResponseFormatter::error(null,'Payment Method wajib diisi');
            }

            if ($user == null) {
                $red = rand(0, 255);
                $green = rand(0, 255);
                $blue = rand(0, 255);

                $complementaryRed = 255 - $red;
                $complementaryGreen = 255 - $green;
                $complementaryBlue = 255 - $blue;

                $complementaryColor = sprintf("#%02x%02x%02x", $complementaryRed, $complementaryGreen, $complementaryBlue);

                $db_user = User::where('color', $complementaryColor)->get();

                if (count($db_user) > 0) {

                    while (count($db_user) > 0) {
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

            if ($user->name == null) {

                $unregistered_user = UnregisterUser::where('user_id', $user->id)->where('court_partner_id', $court_partner->id)->first();

                if ($unregistered_user == null) {
                    $unregistered_user = UnregisterUser::Create([
                        'court_partner_id' => $court_partner->id,
                        'name' => $request->name,
                        'user_id' => $user->id
                    ]);
                }
            }

            $booking_id = 'Booking-' . Carbon::now()->format('YmdHis');

            $booking = Booking::Create([
                'user_id' => $user->id,
                'court_partner_id' => $court_partner->id,
                'total_payment' => $request->total_price,
                'date' => $cart[0]['date'],
                'payment_type' => $request->payment_type,
                'is_membership' => 0,
                'total_discount' => $request->discount,
                'booking_id' => $booking_id,
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

                array_push($booking_detail_list, $booking_detail);
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

                    $booking_detail_to_is_paid = BookingDetail::find($detail->id);
                    $booking_detail_to_is_paid->is_paid = true;
                    $booking_detail_to_is_paid->save();
                }

                $booking->is_paid = true;
                $booking->status = 'Lunas';
                $booking->save();

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

                $booking->status = 'DP Lunas';
                $booking->save();

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

    public function cancel_booking($id)
    {
        try {

            DB::beginTransaction();

            $court_partner = CourtPartner::where('user_id', Auth::user()->id)->first();

            $booking = Booking::find($id);

            if ($booking->court_partner_id != $court_partner->id) {
                return ResponseFormatter::error(null, 'Booking ini bukan milik anda');
            }

            $payments = Payment::where('booking_id', $booking->id)->get();

            foreach ($payments as $payment) {

                if ($payment->type == 'schedule') {
                    $payment_details = PaymentDetail::where('payment_id', $payment->id)->get();

                    foreach ($payment_details as $payment_detail) {
                        $payment_detail->delete();
                    }
                }

                $payment->delete();
            }

            $booking_detail = BookingDetail::where('booking_id', $booking->id)->get();

            foreach ($booking_detail as $detail) {
                $detail->delete();
            }

            $booking->delete();

            DB::commit();

            return ResponseFormatter::success(null, 'Berhasil Menghapus Booking');

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function save_membership(CreateMembershipRequest $request)
    {
        try {
            DB::beginTransaction();
            $court_partner = CourtPartner::where('user_id', Auth::user()->id)->first();
            $cart = $request->cart;
            $user = User::where('phone', $request->phone)->first();

            if ($request->payment_type != 'full-payment') {
                return ResponseFormatter::error(null, 'Tipe Pembayaran Harus Full Payment');
            }

            if ($user == null) {
                $user = User::Create([
                    'phone' => $request->phone
                ]);

            }

            if ($user->name == null) {

                $unregistered_user = UnregisterUser::where('user_id', $user->id)->where('court_partner_id', $court_partner->id)->first();

                if ($unregistered_user == null) {
                    $unregistered_user = UnregisterUser::Create([
                        'court_partner_id' => $court_partner->id,
                        'name' => $request->name,
                        'user_id' => $user->id
                    ]);
                }
            }

            $discount_per_membership = $request->discount / $request->quantity;
            $total_price_per_membership = $request->total_price / $request->quantity;

            $booking = null;
            $is_membership = 0;
            $monthly_membership = 1;

            $booking_id = 'Booking-' . Carbon::now()->format('YmdHis');
            $payment_id = 'Payment-' . Carbon::now()->format('YmdHis');
            if ($court_partner->membership_type == 'byfour') {
                $cart_index = 0;
                foreach ($cart as $each_cart) {

                    if ($cart_index == 0 || $cart_index % 4 == 0) {
                        $booking = Booking::Create([
                            'user_id' => $user->id,
                            'court_partner_id' => $court_partner->id,
                            'total_payment' => $total_price_per_membership,
                            'date' => $each_cart['date'],
                            'payment_type' => $request->payment_type,
                            'is_membership' => $monthly_membership,
                            'total_discount' => $discount_per_membership,
                            'is_paid' => true,
                            'booking_id' => $booking_id,
                            'status' => 'Lunas'
                        ]);
                        $monthly_membership++;

                        $payment = Payment::Create([
                            'booking_id' => $booking->id,
                            'amount' => $total_price_per_membership - $discount_per_membership,
                            'type' => 'schedule',
                            'payment_method' => $request->payment_method,
                            'payment_id' => $payment_id
                        ]);
                    }
                    $is_membership++;
                    $cart_index++;
                    foreach ($each_cart['detail'] as $detail_cart) {

                        $check_booking_detail = BookingDetail::where('court_id', $detail_cart['court_id'])
                            ->where('start_time', Carbon::parse($detail_cart['start_time'])->format('H:i'))
                            ->where('date', Carbon::parse($each_cart['date'])->format('Y-m-d'))->first();

                        if ($check_booking_detail != null) {
                            return ResponseFormatter::error(null, $detail_cart['court_name'] . ' pada tanggal ' . Carbon::parse($each_cart['date'])->format('d-m-Y') . ' jam ' . Carbon::parse($detail_cart['start_time'])->format('H:i') . ' sudah dibooking');
                        }

                        $booking_detail = BookingDetail::Create([
                            'start_time' => Carbon::parse($detail_cart['start_time'])->format('H:i'),
                            'end_time' => Carbon::parse($detail_cart['end_time'])->format('H:i'),
                            'date' => Carbon::parse($each_cart['date'])->format('Y-m-d'),
                            'court_id' => $detail_cart['court_id'],
                            'price' => $detail_cart['price'],
                            'discount' => $detail_cart['discount'],
                            'booking_id' => $booking->id,
                            'is_membership' => $is_membership,
                            'is_paid' => true,
                        ]);

                        $payment_detail = PaymentDetail::Create([
                            'payment_id' => $payment->id,
                            'booking_detail_id' => $booking_detail->id,
                            'amount' => $booking_detail->price - $booking_detail->discount,
                        ]);
                    }

                }
            } else {
                $current_month = null;
                $total_price = 0;
                $total_discount = 0;
                $payment = null;
                foreach ($cart as $each_cart) {
                    $month = Carbon::parse($each_cart['date'])->format('m');
                    if ($current_month != $month) {

                        if ($monthly_membership > 1) {
                            $booking->total_payment = $total_price;
                            $booking->total_discount = $total_discount;
                            $booking->save();

                            $payment->amount = $total_price - $total_discount;
                            $payment->save();
                        }

                        $booking = Booking::Create([
                            'user_id' => $user->id,
                            'court_partner_id' => $court_partner->id,
                            'total_payment' => 0,
                            'date' => $each_cart['date'],
                            'payment_type' => $request->payment_type,
                            'is_membership' => $monthly_membership,
                            'total_discount' => 0,
                            'is_paid' => false,
                            'booking_id' => $booking_id,
                            'status' => 'Lunas'
                        ]);

                        $monthly_membership++;
                        $current_month = $month;
                        $total_price = 0;
                        $total_discount = 0;

                        $payment = Payment::create([
                            'booking_id' => $booking->id,
                            'amount' => 0,
                            'type' => 'schedule',
                            'payment_method' => $request->payment_method,
                            'payment_id' => $payment_id
                        ]);
                    }

                    $is_membership++;

                    foreach ($each_cart['detail'] as $detail_cart) {

                        $check_booking_detail = BookingDetail::where('court_id', $detail_cart['court_id'])
                            ->where('start_time', Carbon::parse($detail_cart['start_time'])->format('H:i'))
                            ->where('date', Carbon::parse($each_cart['date'])->format('Y-m-d'))->first();

                        if ($check_booking_detail != null) {
                            return ResponseFormatter::error(null, $detail_cart['court_name'] . ' pada tanggal ' . Carbon::parse($each_cart['date'])->format('d-m-Y') . ' jam ' . Carbon::parse($detail_cart['start_time'])->format('H:i') . ' sudah dibooking');
                        }

                        $booking_detail = BookingDetail::Create([
                            'start_time' => Carbon::parse($detail_cart['start_time'])->format('H:i'),
                            'end_time' => Carbon::parse($detail_cart['end_time'])->format('H:i'),
                            'date' => Carbon::parse($each_cart['date'])->format('Y-m-d'),
                            'court_id' => $detail_cart['court_id'],
                            'price' => $detail_cart['price'],
                            'discount' => $detail_cart['discount'],
                            'booking_id' => $booking->id,
                            'is_membership' => $is_membership,
                            'is_paid' => true,
                        ]);

                        $payment_detail = PaymentDetail::Create([
                            'payment_id' => $payment->id,
                            'booking_detail_id' => $booking_detail->id,
                            'amount' => $booking_detail->price - $booking_detail->discount,
                        ]);

                        $total_price += $detail_cart['price'];
                        $total_discount += $detail_cart['discount'];
                    }
                }

                $booking->total_payment = $total_price;
                $booking->total_discount = $total_discount;
                $booking->save();

                $payment->amount = $total_price - $total_discount;
                $payment->save();

            }

            DB::commit();

            return ResponseFormatter::success(null, 'Berhasil Membuat Membership Baru');

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function save_membership_new(CreateMembershipRequest $request){
        try{
            DB::beginTransaction();
            $court_partner = CourtPartner::where('user_id', Auth::user()->id)->first();

            $user = User::where('phone', $request->phone)->first();
            $cart = $request->cart;

            if($request->payment_type != 'no-payment' && $request->payment_method == null){
                return ResponseFormatter::error(null,'Payment Method wajib diisi');
            }

            if ($user == null) {
                $red = rand(0, 255);
                $green = rand(0, 255);
                $blue = rand(0, 255);

                $complementaryRed = 255 - $red;
                $complementaryGreen = 255 - $green;
                $complementaryBlue = 255 - $blue;

                $complementaryColor = sprintf("#%02x%02x%02x", $complementaryRed, $complementaryGreen, $complementaryBlue);

                $db_user = User::where('color', $complementaryColor)->get();

                if (count($db_user) > 0) {

                    while (count($db_user) > 0) {
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

            if ($user->name == null) {

                $unregistered_user = UnregisterUser::where('user_id', $user->id)->where('court_partner_id', $court_partner->id)->first();

                if ($unregistered_user == null) {
                    $unregistered_user = UnregisterUser::Create([
                        'court_partner_id' => $court_partner->id,
                        'name' => $request->name,
                        'user_id' => $user->id
                    ]);
                }
            }

            $booking_id = 'Booking-' . Carbon::now()->format('YmdHis');

            $booking = Booking::Create([
                'user_id' => $user->id,
                'court_partner_id' => $court_partner->id,
                'total_payment' => $request->total_price,
                'date' => $cart[0]['date'],
                'payment_type' => $request->payment_type,
                'is_membership' => 0,
                'total_discount' => $request->discount,
                'booking_id' => $booking_id,
                'is_paid' => true,
                'status' => 'Lunas'
            ]);

            $booking_detail_list = [];

            foreach($cart as $cart_temp){
                foreach ($cart_temp['detail'] as $each_cart) {
                    $check_booking_detail = BookingDetail::where('court_id', $each_cart['court_id'])->where('start_time', Carbon::parse($each_cart['start_time'])->format('H:i'))->where('date', Carbon::parse($cart_temp['date'])->format('Y-m-d'))->first();

                    if ($check_booking_detail != null) {
                        return ResponseFormatter::error(null, $each_cart['court_name'] . ' pada tanggal ' . Carbon::parse($cart_temp['date'])->format('d-m-Y') . ' jam ' . Carbon::parse($each_cart['start_time'])->format('H:i') . ' sudah dibooking');
                    }

                    $booking_detail = BookingDetail::Create([
                        'start_time' => Carbon::parse($each_cart['start_time'])->format('H:i'),
                        'date' => $cart_temp['date'],
                        'end_time' => Carbon::parse($each_cart['end_time'])->format('H:i'),
                        'court_id' => $each_cart['court_id'],
                        'price' => $each_cart['price'],
                        'discount' => $each_cart['discount'] ? $each_cart['discount'] : 0,
                        'booking_id' => $booking->id,
                        'is_membership' => 0
                    ]);

                    array_push($booking_detail_list, $booking_detail);
                }
            }

            $payment_id = 'Payment-' . Carbon::now()->format('YmdHis');

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

                $booking_detail_to_is_paid = BookingDetail::find($detail->id);
                $booking_detail_to_is_paid->is_paid = true;
                $booking_detail_to_is_paid->save();
            }

            DB::commit();

            return ResponseFormatter::success(null, 'Berhasil Membuat Booking Baru');

        }catch (Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function booking_payment(BookingPaymentRequest $request)
    {
        try {

            DB::beginTransaction();

            $booking = Booking::find($request->booking_id);

            $court_partner = CourtPartner::where('user_id', Auth::user()->id)->first();

            if ($booking->court_partner_id != $court_partner->id) {
                return ResponseFormatter::error(null, 'Booking ini bukan milik anda');
            }

            if ($booking->is_paid) {
                return ResponseFormatter::error(null, 'Booking telah lunas');
            }

            $remain_amount = 0;
            if ($booking->payment_type == 'down-payment') {
                $payment = Payment::where('booking_id',$booking->id)->sum('amount');

                $remain_amount = $booking->total_payment - $booking->total_discount - $payment;
            } else if ($booking->payment_type == 'no-payment') {
                $remain_amount = $booking->total_payment - $booking->total_discount;
            } else {
                return ResponseFormatter::error(null, 'Booking telah lunas');
            }

            $booking_detail_list = BookingDetail::where('booking_id', $booking->id)->get();
            $payment_id = 'Payment-' . Carbon::now()->format('YmdHis');
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'amount' => $remain_amount,
                'type' => 'schedule',
                'payment_method' => $request->payment_method,
                'payment_id' => $payment_id
            ]);

            foreach ($booking_detail_list as $detail) {
                $payment_detail = PaymentDetail::create([
                    'payment_id' => $payment->id,
                    'booking_detail_id' => $detail->id,
                    'amount' => $detail->price - $detail->discount,
                ]);

                $detail->is_paid = true;
                $detail->save();
            }
            $booking->is_paid = true;
            $booking->status = 'Lunas';
            $booking->save();

            DB::commit();

            return ResponseFormatter::success(null, 'Booking telah dilunasi');

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function calculate_payment($id){
        try{

            $booking = Booking::find($id);

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            if($booking->court_partner_id != $court_partner->id){
                return ResponseFormatter::error(null,'Booking ini bukan milik anda');
            }

            if($booking->is_paid){
                return ResponseFormatter::error(null, 'Booking ini telah lunas');
            }

            $total_payment = 0;

            $booking_detail = BookingDetail::where('booking_id',$booking->id)->get();

            $detail_list = [];

            foreach($booking_detail as $detail){
                $detail_list[] = [
                    'time' => $detail->start_time.'-'.$detail->end_time,
                    'price' => $detail->price,
                    'discount' => $detail->discount,
                    'court_name' => $detail->court->name
                ];
            }

            $payment = Payment::where('booking_id',$booking->id)->sum('amount');

            if($booking->payment_type == 'no-payment'){
                $total_payment = $booking->total_payment - $booking->total_discount;
            }else if($booking->payment_type == 'down-payment'){
                $total_payment = $booking->total_payment - $booking->total_discount - $payment;
            }

            $payment = Payment::where('booking_id',$booking->id)->sum('amount');

            if($booking->user->name == null){
                $unregister_user = UnregisterUser::where('user_id',$booking->user_id)->where('court_partner_id',$court_partner->id)->select('name')->first();
            }

            return ResponseFormatter::success([
                'user_name' => $booking->user->name != null ? $booking->user->name : $unregister_user->name,
                'booking_id' => $booking->id,
                'detail' => $detail_list,
                'date' => Carbon::parse($booking->date)->format('d-m-Y'),
                'total_payment' => $booking->total_payment,
                'total_discount' => $booking->total_discount,
                'paid' => $payment
            ], 'Berhasil mendapatkan Sisa Biaya Booking');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

}
