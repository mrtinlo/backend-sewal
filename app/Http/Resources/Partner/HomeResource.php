<?php

namespace App\Http\Resources\Partner;

use App\Models\CourtPrice;
use App\Models\BookingDetail;
use App\Models\KeepDetail;
use App\Models\UnregisterUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $courtPartner = $this->courtPartner;
        $day_name_by_date = Carbon::parse($this->date)->format('l');

        $price_list = CourtPrice::query()->where('day','like','%'.$day_name_by_date.'%')->where('court_id',$this->id)->orderBy('start_time','asc')->get();

        $start_time = $courtPartner->start_at;
        $end_time = $courtPartner->end_at;

        $hour = $this->hour ? $this->hour : 1;
        $start = strtotime($start_time);
        $end = strtotime($end_time);
        $price_list_count = count($price_list);

        $schedule = BookingDetail::query()->where('court_id',$this->id)
            ->where('date',$this->date)
            ->orderBy('start_time','asc')
            ->get();

        $keep = KeepDetail::query()->where('court_id',$this->id)
            ->where('date',$this->date)
            ->orderBy('start_time','ASC')
            ->get();

        if($price_list_count == 0){
            $rate_price = 0;
        }


        $slots = [];

        $schedule_index = 0;
        $price_list_index = 0;
        $keep_index = 0;

        $schedule_count = count($schedule);
        $keep_count = count($keep);

        $schedule_count -= 1;
        $keep_count -= 1;

        $current_date = Carbon::now()->format('Y-m-d');
        $schedule_date = Carbon::parse($this->date)->format('Y-m-d');

        if($price_list_count !== 0){

            for($i=0;$i<$price_list_count;$i++){

                $is_disabled = false;
                if ($schedule_date < $current_date) {
                    $is_disabled = true;
                } else if ($schedule_date == $current_date && Carbon::parse($price_list[$i]->start_time)->format('H:i:s') < Carbon::now()->subHours(1)->format('H:i:s')) {
                    $is_disabled = true;
                }

                if($price_list[$i]->price == null){
                    $slots[] = [
                        'id' => null,
                        'hour' => $hour,
                        'status' => null,
                        'is_disabled' => true,
                        'is_membership' => 0,
                        'is_paid' => false,
                        'payment_type' => null,
                        'price' => intval($price_list[$i]->price),
                        'time' => Carbon::parse($price_list[$i]->start_time)->format('H:i') . "-" . Carbon::parse($price_list[$i]->end_time)->format('H:i'),
                        'user' => null,
                        'remaining_payment' => 0
                    ];
                    continue;
                }

                $no_schedule = true;

                if ($schedule_count >= 0 && $schedule_count >= $schedule_index) {
                    if (Carbon::parse($price_list[$i]->start_time)->format('H:i') == Carbon::parse($schedule[$schedule_index]->start_time)->format('H:i')) {

                        if($schedule[$schedule_index]->booking->user->name == null){
                            $user = UnregisterUser::select('name')->where('user_id',$schedule[$schedule_index]->booking->user_id)->where('court_partner_id',$courtPartner->id)->first();
                        }
                        $slots[] = [
                            'id' => intval($schedule[$schedule_index]->booking_id),
                            'hour' => intval($hour),
                            'status' => 'book',
                            'is_disabled' => $is_disabled,
                            'is_membership' => $schedule[$schedule_index]->is_membership,
                            'is_paid' => boolval($schedule[$schedule_index]->is_paid),
                            'payment_type' => $schedule[$schedule_index]->booking->payment_type,
                            'price' => intval($schedule[$schedule_index]->price),
                            'time' => Carbon::parse($price_list[$i]->start_time)->format('H:i') . "-" . Carbon::parse($price_list[$i]->end_time)->format('H:i'),
                            'user' => $schedule[$schedule_index]->booking->user->name !== null ? $schedule[$schedule_index]->booking->user->name : $user->name,
                            'remaining_payment' => $schedule[$schedule_index]->is_paid ? 0 : intval($schedule[$schedule_index]->price -$schedule[$schedule_index]->discount)
                        ];

                        $schedule_index++;
                        $no_schedule = false;
                    }

                };

                if ($no_schedule && $keep_count >= 0 && $keep_count >= $keep_index) {
                    if (Carbon::parse($price_list[$i]->start_time)->format('H:i')== Carbon::parse($keep[$keep_index]->start_time)->format('H:i')) {

                        if($keep[$keep_index]->keep->user->name == null){
                            $user = UnregisterUser::select('name')->where('user_id',$keep[$keep_index]->keep->user_id)->where('court_partner_id',$courtPartner->id)->first();
                        }

                        $slots[] = [
                            'id' => intval($keep[$keep_index]->keep_id),
                            'hour' => intval($hour),
                            'status' => 'keep',
                            'is_disabled' => $is_disabled,
                            'is_membership' => $keep[$keep_index]->is_membership,
                            'is_paid' => false,
                            'payment_type' => null,
                            'price' => intval($price_list[$i]->price),
                            'time' => Carbon::parse($price_list[$i]->start_time)->format('H:i') . "-" . Carbon::parse($price_list[$i]->end_time)->format('H:i'),
                            'user' => $keep[$keep_index]->keep->user->name !== null ? $keep[$keep_index]->keep->user->name : $user->name,
                            'phone' => $keep[$keep_index]->keep->user->phone,
                            'remaining_payment' => 0
                        ];

                        $keep_index++;
                        $no_schedule = false;
                    }
                }

                if($no_schedule){
                    $slots[] = [
                        'id' => null,
                        'hour' => $hour,
                        'status' => null,
                        'is_disabled' => $is_disabled,
                        'is_membership' => 0,
                        'is_paid' => false,
                        'payment_type' => null,
                        'price' => intval($price_list[$i]->price),
                        'time' => Carbon::parse($price_list[$i]->start_time)->format('H:i') . "-" . Carbon::parse($price_list[$i]->end_time)->format('H:i'),
                        'user' => null,
                        'remaining_payment' => 0
                    ];
                }

            }

        }else{
            $this->description = 'Tidak ada harga yang disimpan untuk lapangan ini';
        }

        return [
            'id' => $this->id,
            'court_name' => $this->name,
            'description' => $this->description,
//            'rate_price' => $rate_price,
            'court_partner_id' => $this->court_partner_id,
            'court_partner_name' => $this->courtPartner->name,
            'list_time' => $slots,
        ];
    }
}
