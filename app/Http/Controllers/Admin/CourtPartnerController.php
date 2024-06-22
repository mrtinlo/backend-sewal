<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageService;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourtPartner\AddCourtTypeRequest;
use App\Http\Requests\Admin\CourtPartner\DeleteCourtTypeRequest;
use App\Http\Requests\Admin\CourtPartner\EditCourtPartnerRequest;
use App\Models\CourtPartner;
use App\Models\CourtType;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CourtPartnerController extends Controller
{

    private $image_service;

    public function __construct(ImageService $image_service){
        $this->image_service =  $image_service;
    }

    public function add_court_type(AddCourtTypeRequest $request){
        try{
            $court_partner = CourtPartner::find($request->court_partner_id);

            if($court_partner == null){
                return ResponseFormatter::error(
                    null,
                    'Court Partner Tidak Ditemukan',
                    500
                );
            }

            $court_partner->court_types()->detach();

            $court_partner->save();

            foreach($request->court_type_list as $court_type_id){
                $court_type = CourtType::find($court_type_id);

                if($court_type == null){
                    return ResponseFormatter::error(null,'Court Type tidak ditemukan');
                }
            }

            $court_partner->court_types()->attach($request->court_type_list);

            $court_partner->save();

            return ResponseFormatter::success(
                null, "Court Type Baru Berhasil diperbarui"
            );

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function delete_court_type(DeleteCourtTypeRequest $request){
        try{
            $court_partner = CourtPartner::find($request->court_partner_id);

            if($court_partner == null){
                return ResponseFormatter::error(
                    null,
                    'Court Partner Tidak Ditemukan',
                    500
                );
            }

            $court_type = CourtType::find($request->court_type_id);

            if($court_type == null){
                return ResponseFormatter::error(
                    null,
                    'Court Type Tidak Ditemukan',
                    500
                );
            }

            $court_partner->court_types()->detach($court_type->id);

            $court_partner->save();

            return ResponseFormatter::success(
                null, "Court Type Baru Berhasil ditambahkan"
            );
        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function get_court_partner_list(Request $request){
        try{

            $search = $request->query('search');

            if($search == null){
                $court_partner_list = CourtPartner::all();
            }else{
                    $court_partner_list = CourtPartner::where('name','LIKE','%'.$search.'%')->get();
            }

            $court_partner_data = [];

            foreach($court_partner_list as $partner){

                $court_partner_data[] = [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'total_court' => $partner->total_court(),
                    'court_type' => $partner->court_types_without_pivot,
                    'start_time' => Carbon::parse($partner->start_at)->format('H:i'),
                    'end_time' => Carbon::parse($partner->end_at)->format('H:i')
                ];
            }

            return ResponseFormatter::success($court_partner_data,'Berhasil Mendapatkan List Partner');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function delete_court_partner($id){
        try{

            $court_partner = CourtPartner::find($id);
            if($court_partner == null){
                return ResponseFormatter::error(null,'Partner Tidak Ditemukan');
            }

            if($court_partner->profile){
                Storage::disk('public')->delete($court_partner->profile);
            }

            $court_partner->delete();

            return ResponseFormatter::success(null,'Berhasil Menghapus Partner');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function get_court_partner_data($id){
        try{

            $court_partner = CourtPartner::find($id);

            if($court_partner == null){
                return ResponseFormatter::error(null,'Partner Tidak Ditemukan');
            }

            if($court_partner->profile){
                try{
                    $image_path = public_path('storage/'.$court_partner->profile);
                    $image_extension = explode('.',$court_partner->profile)[1];
                    $image = file_get_contents($image_path);
                    $base64_image = base64_encode($image);
                    $court_partner->profile = 'data:image/'.$image_extension.';base64,'.$base64_image;
                }catch(Exception $e){
                    $court_partner->profile = null;
                }
            }

            $court_type_list = [];

            foreach($court_partner->court_types as $court_type){

                $image_string = null;

//                if($court_type->icon) {
//                    try {
//                        $image_path = public_path('storage/'.$court_type->icon);
//                        $image_extension = explode('.',$court_type->icon)[1];
//                        $image = file_get_contents($image_path);
//                        $base64_image = base64_encode($image);
//                        $image_string='data:image/'.$image_extension.';base64,'.$base64_image;
//                    }catch(\Exception $e){
//
//                    }
//
//                }

                $data = [
                    'id' => $court_type->id,
                    'name' => $court_type->name,
//                    'image' => $image_string
                ];
                array_push($court_type_list,$data);
            }

            return ResponseFormatter::success([
                'id' => $court_partner->id,
                'name' => $court_partner->name,
                'start_at' => Carbon::parse($court_partner->start_at)->format('H:i'),
                'end_at' => Carbon::parse($court_partner->end_at)->format('H:i'),
                'is_down_payment' => boolval($court_partner->is_down_payment),
                'membership_type' => $court_partner->membership_type,
                'bank_account_name' => $court_partner->bank_account_name,
                'bank_account_number' => $court_partner->bank_account_number,
                'down_payment_percentage' => intval($court_partner->down_payment_percentage),
                'down_payment_amount' => intval($court_partner->down_payment_amount),
                'down_payment_type' => $court_partner->down_payment_type,
                'whatsapp_notification' => boolval($court_partner->whatsapp_notification),
                'is_keep' => boolval($court_partner->is_keep),
                'profile' => $court_partner->profile,
                'city' => [
                    'id' => $court_partner->city_id,
                    'name' =>$court_partner->city->name
                ],
                'court_type' => $court_type_list
            ], 'Berhasi Mendapatkan Data Court Partner');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function edit_court_partner(EditCourtPartnerRequest $request){
        try{
            DB::beginTransaction();
            $court_partner = CourtPartner::find($request->id);

            if($court_partner == null){
                return ResponseFormatter::error(null,'Partner Tidak Ditemukan');
            }

            $court_partner->name = $request->partner_name;
            $court_partner->start_at = Carbon::parse($request->start_at)->format('H:i');
            $court_partner->end_at = Carbon::parse($request->end_at)->format('H:i');
            $court_partner->is_down_payment = boolval($request->is_down_payment);
            $court_partner->membership_type = $request->membership_type;
            $court_partner->bank_account_name = $request->bank_account_name;
            $court_partner->bank_account_number = $request->bank_account_number;
            $court_partner->down_payment_percentage = $request->down_payment_percentage;
            $court_partner->down_payment_amount = $request->down_payment_amount;
            $court_partner->down_payment_type = $request->down_payment_type;
            $court_partner->whatsapp_notification = boolval($request->whatsapp_notification);
            $court_partner->is_keep = boolval($request->is_keep);
            $court_partner->city_id = $request->city_id;

            $user = User::find($court_partner->user_id);

            if($request->password){
                $user->password = Hash::make($request->password);
                $user->save();
            }

            if($request->email){
                $user->email = $request->email;
                $user->save();
            }

            if($request->pin){
                $court_partner->pin = Hash::make($request->pin);
            }

            if($request->image != null){
                //$file_name = 'court_partner/profile/'.$this->image_service->storeImage("court_partner/profile/",$request->image);

                $extension = $request->image->getClientOriginalExtension();
                $file_name = (uniqid().time()).'.'.$extension;
                $content = file_get_contents($request->image->getRealPath());
                Storage::put('public/'.'court_partner/profile/'.$file_name, $content);
                if($court_partner->profile){
                    try{
                        Storage::disk('public')->delete($court_partner->profile);
                    }catch(Exception $e){

                    }

                }
                $court_partner->profile = 'court_partner/profile/'.$file_name;
            }

            $court_partner->save();

            DB::commit();

            return ResponseFormatter::success(null,'Berhasil Merubah Court Partner');

        }catch(Exception $e){
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

}
