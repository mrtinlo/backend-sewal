<?php

namespace App\Http\Controllers\Partner;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\Booking\ScheduleRequest;
use App\Models\BookingDetail;
use App\Models\Court;
use App\Models\CourtPartner;
use App\Models\KeepDetail;
use App\Models\UnregisterUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function schedule($court_type_id, $date = null){
        try{
            $date = $date ? Carbon::parse($date)->format('Y-m-d')  : Carbon::now()->format('Y-m-d');

            $courtPartner = CourtPartner::where('user_id', Auth::user()->id)->first();
            if($courtPartner == null){
                return ResponseFormatter::error(
                    [
                        'message' => 'Court Partner Not Found, please try again or make a new one',
                        'trace' => null,
                        'response' => 400
                    ]
                );
            }

            $court_list = Court::where('court_partner_id',$courtPartner->id)->where('court_type_id',$court_type_id)->orderBy('id','ASC')->get();
            $court_total = count($court_list);
            $court_name = [];

            $schedule = [];
            $index = [];
            $index_max = [];
            $taken = [];

            $keep = [];
            $keep_index = [];
            $keep_index_max = [];

            foreach ($court_list as $court){
                $schedule_per_court = BookingDetail::where('court_id',$court->id)->where('date',$date)->orderBy('start_time','asc')->get();

                $schedule_per_court_total = count($schedule_per_court);
                array_push($court_name, $court->name);
                array_push($schedule, $schedule_per_court);
                array_push($index_max, $schedule_per_court_total);
                array_push($index,0);
                array_push($taken, 0);

                $keep_per_court = KeepDetail::where('court_id',$court->id)->where('date',$date)->orderBy('start_time','ASC')->get();

                $keep_per_court_total = count($keep_per_court);
                array_push($keep, $keep_per_court);
                array_push($keep_index_max, $keep_per_court_total);
                array_push($keep_index, 0);
            }

            $start = strtotime($courtPartner->start_at);
            $end = strtotime($courtPartner->end_at);

            $hour = 1;

            $row_data = [];

            for ($i = $start; $i<$end;$i+= ($hour*3600)){
                $court_schedule_data = [];
                for($j = 0; $j < $court_total; $j++){
                    $no_schedule = true;
                    $check_is_null = true;
                    if($index[$j] < $index_max[$j] && $taken[$j] == 0){
                        $current_start_time = $i;
                        $current_time = $i;
                        $current_booking = $schedule[$j][$index[$j]]->booking_id;
                        if(Carbon::parse($schedule[$j][$index[$j]]->start_time)->format('H:i') == Carbon::parse(date('H:i',$current_time))->format('H:i')){
                            $total_hour = 0;

                            if($schedule[$j][$index[$j]]->booking->user->name == null){
                                $unregister_user = UnregisterUser::select('name')->where('user_id',$schedule[$j][$index[$j]]->booking->user_id)->where('court_partner_id',$courtPartner->id)->first();
                                $user_name = $unregister_user->name;
                            }else {
                                $user_name = $schedule[$j][$index[$j]]->booking->user->name;
                            }
                            $user_color =  $schedule[$j][$index[$j]]->booking->user->color;
                            $payment_method = $schedule[$j][$index[$j]]->booking->payment_method;
                            $second_payment_method = $schedule[$j][$index[$j]]->booking->second_payment_method;
                            $is_paid = $schedule[$j][$index[$j]]->booking->is_paid;
                            $payment_type = $schedule[$j][$index[$j]]->booking->payment_type;
                            $check_is_null = false;
                            $membership_reminder = $schedule[$j][$index[$j]]->is_membership;

                            if($membership_reminder > 0){

                                $remaining_memberhsip = BookingDetail::query()->where('booking_id', $schedule[$j][$index[$j]]->booking_id)->where('is_membership', '>', $schedule[$j][$index[$j]]->is_membership)->distinct('is_membership')->count();

                                $last_membership = BookingDetail::withTrashed()->where('booking_id',$schedule[$j][$index[$j]]->booking_id)->orderBy('is_membership','desc')->first();

                                $last_date = Carbon::parse($last_membership->date)->addDays(7)->format('Y-m-d');
                                $next_first_month_membership = BookingDetail::withTrashed()->where('date',$last_date)->where('start_time',Carbon::parse(date('H:i',$current_time)))->first();

                                $remaining_membership_next_month = 0;
                                if($next_first_month_membership != null){
                                    while($last_membership->booking->user_id == $next_first_month_membership->booking->user_id){
                                        $count_membership_next_month = BookingDetail::query()->where('booking_id', $next_first_month_membership->booking_id)->distinct('is_membership')->count();
                                        $remaining_membership_next_month += $count_membership_next_month;

                                        $last_membership = BookingDetail::withTrashed()->where('booking_id',$next_first_month_membership->booking_id)->orderBy('is_membership','desc')->first();

                                        $last_date = Carbon::parse($last_membership->date)->addDays(7)->format('Y-m-d');
                                        $next_first_month_membership = BookingDetail::withTrashed()->where('date',$last_date)->where('start_time',Carbon::parse(date('H:i',$current_time)))->first();

                                        if($next_first_month_membership == null){
                                            break;
                                        }
                                    }
                                }
                                $membership_reminder = $remaining_memberhsip+1+$remaining_membership_next_month;
                            }
                        };

                        $start_scheule = $schedule[$j][$index[$j]];
                        $remaining_payment = 0;
                        while(Carbon::parse($schedule[$j][$index[$j]]->start_time)->format('H:i') == Carbon::parse(date('H:i',$current_time))->format('H:i')
                            && $schedule[$j][$index[$j]]->booking_id == $current_booking){
                            $remaining_payment += $schedule[$j][$index[$j]]->price_booked;
                            $current_time+=3600;
                            $total_hour++;
                            $index[$j]++;

                            if($total_hour > 1){
                                $taken[$j]++;
                            }

                            if($index[$j] >= $index_max[$j]){
                                break;
                            }
                        }

                        if(!$check_is_null){
                            $court_data = [
                                'user_name' => $user_name,
                                'color' => $membership_reminder> 0? $user_color : '#000000',
                                'book_duration' => date('H:i',$current_start_time).' - '.date('H:i',$current_time),
                                'total_hour' => $total_hour,
                                'payment_method' => $payment_method,
                                'second_payment_method' => $second_payment_method,
                                'payment_type' => $payment_type,
                                'membership_reminder' => $membership_reminder,
                                'is_paid' => boolval($start_scheule->is_paid),
                                'remaining_payment' => $start_scheule->is_paid ? 0 : $remaining_payment
                            ];

                            $no_schedule = false;
                        }
                    }

                    if($no_schedule && $taken[$j] > 0){
                        $court_data = 'taken';
                        $taken[$j]--;
                        $no_schedule = false;
                    }

                    if($no_schedule && $keep_index[$j] < $keep_index_max[$j]){
                        $current_start_time = $i;
                        $current_time = $i;
                        $current_keep = $keep[$j][$keep_index[$j]]->keep_id;

                        if(Carbon::parse($keep[$j][$keep_index[$j]]->start_time)->format('H:i') == Carbon::parse(date('H:i',$current_time))->format('H:i')){
                            $total_hour = 0;

                            if($keep[$j][$keep_index[$j]]->keep->user->name == null){
                                $unregister_user = UnregisterUser::select('name')->where('user_id',$keep[$j][$keep_index[$j]]->keep->user_id)->where('court_partner_id',$courtPartner->id)->first();
                                $user_name = $unregister_user->name;
                            }else {
                                $user_name = $keep[$j][$keep_index[$j]]->keep->user->name;
                            }

                            $check_is_null = false;
                        }

                        while(Carbon::parse($keep[$j][$keep_index[$j]]->start_time)->format('H:i') == Carbon::parse(date('H:i',$current_time))->format('H:i')
                            && $keep[$j][$keep_index[$j]]->keep_id == $current_keep){

                            $current_time+=3600;
                            $total_hour++;
                            $keep_index[$j]++;

                            if($total_hour > 1){
                                $taken[$j]++;
                            }

                            if($keep_index[$j] >= $keep_index_max[$j]){
                                break;
                            }
                        }

                        if(!$check_is_null){
                            $court_data = [
                                'user_name' => $user_name,
                                'book_duration' => date('H:i',$current_start_time).' - '.date('H:i',$current_time),
                                'total_hour' => $total_hour,
                                'is_keep' => true
                            ];

                            $no_schedule = false;
                        }
                    }

                    if($no_schedule){
                        $court_data = null;
                    }

                    array_push($court_schedule_data, $court_data);
                }

                array_push($row_data,[
                    'time' =>  date('H:i',$i),
                    'court_schedule_data' => $court_schedule_data
                ]);
            }

            return ResponseFormatter::success([
                'court_names' => $court_name,
                'row_data' => $row_data
            ],'Berhasil Mendapatkan Schedule');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }
}
