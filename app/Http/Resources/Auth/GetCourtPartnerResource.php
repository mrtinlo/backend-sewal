<?php

namespace App\Http\Resources\Auth;

use App\Models\CourtPartner;
use App\Models\CourtType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetCourtPartnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $court_partner = CourtPartner::where('user_id',$this->id)->with(['court_types' => function($query){
            $query->select('court_types.id','court_types.name');
        }])->first();

        $profile = null;

        if($court_partner->profile){
            $image_path = public_path('storage/'.$court_partner->profile);
            $image_extension = explode('.',$court_partner->profile)[1];
            $image = file_get_contents($image_path);
            $base64_image = base64_encode($image);
            $profile = 'data:image/'.$image_extension.';base64,'.$base64_image;
        }

        $facility_list = [];
        foreach($court_partner->facilities as $facility){
            $base64_string = null;

            if($facility->icon){
                $image_path = public_path('storage/'.$facility->icon);
                $image_extension = explode('.',$facility->icon)[1];
                $image = file_get_contents($image_path);
                $base64_image = base64_encode($image);
                $base64_string = 'data:image/'.$image_extension.';base64,'.$base64_image;
            }

            $facility_list[] = [
                'id' => $facility->id,
                'image' => $base64_string,
                'name' => $facility->name
            ];
        }

        $court_types= [];

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
            array_push($court_types,$data);
        }

        return [
            'name'=>$this->name,
            'email'=>$this->email,
            'role_id'=>$this->roles[0]->id,
            'role_name'=>$this->roles[0]->name,
            'phone'=>$this->phone,
            'down_payment' => boolval($court_partner->is_down_payment),
            'down_payment_percentage' => intval($court_partner->down_payment_percentage),
            'down_payment_type'=>$court_partner->down_payment_type,
            'down_payment_amount'=>$court_partner->down_payment_amount,
            'court_type'=>$court_types,
            'membership_type'=>$court_partner->membership_type,
            'court_partner_id'=>$court_partner->id,
            'is_keep'=> boolval($court_partner->is_keep),
            'city' => $court_partner->city->name,
            'profile' => $profile,
            'facility' => $facility_list,
            'google_map' => $court_partner->google_map,
            'address' => $court_partner->address
        ];
    }
}
