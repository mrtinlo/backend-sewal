<?php

namespace App\Http\Resources\User;

use App\Models\BookingDetail;
use App\Models\CourtPrice;
use App\Models\KeepDetail;
use App\Models\UnregisterUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Exception;

class CourtListResource extends JsonResource
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

        $available = 0;

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
                        'hour' => $hour,
                        'is_disabled' => true,
                        'price' => intval($price_list[$i]->price),
                        'time' => Carbon::parse($price_list[$i]->start_time)->format('H:i') . "-" . Carbon::parse($price_list[$i]->end_time)->format('H:i'),
                    ];
                    continue;
                }

                $no_schedule = true;

                if ($schedule_count >= 0 && $schedule_count >= $schedule_index) {
                    if (Carbon::parse($price_list[$i]->start_time)->format('H:i') == Carbon::parse($schedule[$schedule_index]->start_time)->format('H:i')) {

                        $slots[] = [
                            'hour' => intval($hour),
                            'is_disabled' => $is_disabled,
                            'price' => intval($schedule[$schedule_index]->price),
                            'time' => Carbon::parse($price_list[$i]->start_time)->format('H:i') . "-" . Carbon::parse($price_list[$i]->end_time)->format('H:i'),
                        ];

                        $schedule_index++;
                        $no_schedule = false;
                    }

                };

                if ($no_schedule && $keep_count >= 0 && $keep_count >= $keep_index) {
                    if (Carbon::parse($price_list[$i]->start_time)->format('H:i')== Carbon::parse($keep[$keep_index]->start_time)->format('H:i')) {

                        $slots[] = [
                            'hour' => intval($hour),
                            'is_disabled' => $is_disabled,
                            'price' => intval($price_list[$i]->price),
                            'time' => Carbon::parse($price_list[$i]->start_time)->format('H:i') . "-" . Carbon::parse($price_list[$i]->end_time)->format('H:i'),
                        ];

                        $keep_index++;
                        $no_schedule = false;
                    }
                }

                if($no_schedule){
                    $slots[] = [
                        'hour' => $hour,
                        'is_disabled' => $is_disabled,
                        'price' => intval($price_list[$i]->price),
                        'time' => Carbon::parse($price_list[$i]->start_time)->format('H:i') . "-" . Carbon::parse($price_list[$i]->end_time)->format('H:i'),
                    ];
                }

                if(!$is_disabled){
                    $available++;
                }

            }

        }else{
            $this->description = 'Tidak ada harga yang disimpan untuk lapangan ini';
        }

        $image_list = [];
        foreach($this->images as $image){

            try{
                $image_path = public_path('storage/'.$image->images);
                $image_extension = explode('.',$image->images)[1];
                $image = file_get_contents($image_path);
                $base64_image = base64_encode($image);
                $image_list[] = 'data:image/'.$image_extension.';base64,'.$base64_image;
            }catch (Exception $e){
            }

        }

        return [
            'id' => $this->id,
            'court_name' => $this->name,
            'description' => $this->description,
            'list_time' => $slots,
            'available' => $available,
            'image' => $image_list
        ];
    }
}
