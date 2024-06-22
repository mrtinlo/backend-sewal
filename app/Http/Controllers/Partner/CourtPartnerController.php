<?php

namespace App\Http\Controllers\Partner;

use App\Helpers\ImageService;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\CourtPartner\AddFacilityRequest;
use App\Http\Requests\Partner\CourtPartner\AddImageRequest;
use App\Http\Requests\Partner\CourtPartner\UpdateCityRequest;
use App\Http\Requests\Partner\CourtPartner\UpdateDownPaymentAmountRequest;
use App\Http\Requests\Partner\CourtPartner\UpdateDownPaymentPercentage;
use App\Http\Requests\Partner\CourtPartner\UpdateDownPaymentTypeRequest;
use App\Http\Requests\Partner\CourtPartner\UpdateLocationRequest;
use App\Http\Requests\Partner\CourtPartner\UpdateTimeRequest;
use App\Models\City;
use App\Models\Court;
use App\Models\CourtPartner;
use App\Models\CourtPrice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Storage;

class CourtPartnerController extends Controller
{

    private $image_service;

    public function __construct(ImageService $image_service){
        $this->image_service =  $image_service;
    }

    public function update_time(UpdateTimeRequest $request){
        try{
            $start_time = Carbon::parse($request->start_time)->format('H:i');
            $end_time = Carbon::parse($request->end_time)->format('H:i');

            $court_partner = CourtPartner::where('user_id',Auth::user()->id())->first();

            $court_list = Court::where('court_partner_id',$court_partner->id)->orderBy('start_time','asc')->get();

            $total_court = count($court_list);

            DB::beginTransaction();

            $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

            for($i=0;$i<$total_court;$i++){

                for($j = 0;$j<7;$j++){
                    $price_list = CourtPrice::where('court_id',$court_list[$i])->where('day',$days[$j])->orderBy('start_time','asc')->get();
                    $start_index = 0;
                    $end_index = count($price_list) - 1;
                    $total_price_list = count($price_list) - 1;
                    $start_time = Carbon::parse($price_list[$start_index]->start_time)->format('H:i');
                    $end_time = Carbon::parse($price_list[$end_index]->end_time)->format('H:i');

                    if(Carbon::parse($price_list[$start_index]->start_time)->format('H:i') < $start_time){
                        while($start_index < $total_price_list && Carbon::parse($price_list[$start_index]->start_time)->format('H:i') < $start_time ){
                            $delete_price = CourtPrice::find($price_list[$start_index]);
                            $delete_price->delete();
                            $start_index++;
                        }
                    }else if(Carbon::parse($price_list[$start_index]->start_time)->format('H:i') > $start_time){
                        while($start_index < $total_price_list && Carbon::parse($price_list[$start_index]->start_time)->format('H:i') > $start_time ){

                        }
                    }

                    if(Carbon::parse($price_list[$end_index]->end_time)->format('H:i') > $end_time){
                        while($end_index >= 0 && Carbon::parse($price_list[$end_index]->end_time)->format('H:i') > $end_time ){
                            $delete_price = CourtPrice::find($price_list[$end_index]);
                            $delete_price->delete();
                            $end_index--;
                        }
                    }else if(Carbon::parse($price_list[$end_index]->end_time)->format('H:i') < $end_time){
                        while($start_index < $total_price_list && Carbon::parse($price_list[$end_index]->end_time)->format('H:i') < $end_time ){

                        }
                    }
                }
            }

            $court_partner->start_time = $start_time;
            $court_partner->end_time = $end_time;
            $court_partner->save();

            DB::commit();

            return ResponseFormatter::success(null,'Jam Gedung telah diperbarui');

        }catch(Exception $e){
            DB::rollback();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function update_down_payment_percentage(UpdateDownPaymentPercentage $request){

        try{

            if($request->percentage < 20){
                return ResponseFormatter::error(null,'Persentase Down Payment tidak bisa lebih rendah dari 20%');
            }else if($request->percentage > 60){
                return ResponseFormatter::error(null, 'Persentase Down Payment tidak bisa lebih besar dari 60%');
            }

            $data = CourtPartner::where('user_id',Auth::user()->id)->first();

            $data->down_payment_percentage = $request->percentage;
            $data->save();

            return ResponseFormatter::success(null,'Berhasil Merubah Persentase Down Payment');
        }catch(Exception $e) {
            return ResponseFormatter::error(
                [
                    'message' => $e->getMessage(),
                    'details' => $e->getTrace(),
                ]
            );
        }

    }

    public function update_membership_type(){
        try{
            $court_partner = CourtPartner::where('user_id', Auth::user()->id)->first();

            if($court_partner->membership_type == 'byfour'){
                $court_partner->membership_type = 'monthly';
            }else{
                $court_partner->membership_type = 'byfour';
            }

            $court_partner->save();

            return ResponseFormatter::success(null, 'Berhasil Mengubah Tipe Membership');

        }catch(Exception $e) {
            return ResponseFormatter::error(
                [
                    'message' => $e->getMessage(),
                    'details' => $e->getTrace(),
                ]
            );
        }
    }

    public function update_down_payment_type(UpdateDownPaymentTypeRequest $request){
        try{

            if($request->down_payment_type != 'percentage' && $request->down_payment_type != 'amount'){
                return ResponseFormatter::error(null,'Hanya bisa memilih antara \'percentage\' dan \'amount\'');
            }


            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $court_partner->down_payment_type = $request->down_payment_type;
            $court_partner->save();
            return ResponseFormatter::success(null, 'Berhasil Menguhah Tipe Down Payment');

        }catch(Exception $e) {
            return ResponseFormatter::error(
                [
                    'message' => $e->getMessage(),
                    'details' => $e->getTrace(),
                ]
            );
        }
    }

    public function update_down_payment_amount(UpdateDownPaymentAmountRequest $request){

        try{

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $court_partner->down_payment_amount = $request->down_payment_amount;
            $court_partner->save();

            return ResponseFormatter::success(null,'Berhasil merubah nominal down payment tetap');
        }catch(Exception $e) {
            return ResponseFormatter::error(
                [
                    'message' => $e->getMessage(),
                    'details' => $e->getTrace(),
                ]
            );
        }

    }

    public function update_is_keep(){
        try {

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            if($court_partner->is_keep == 1){
                $court_partner->is_keep = 0;
                $message = 'Keep dinonaktifkan';
            }else{
                $court_partner->is_keep = 1;
                $message = 'Keep diaktifkan';
            }
            $court_partner->save();

            return ResponseFormatter::success(null, $message);

        }catch (Exception $e){
            return ResponseFormatter::error(
                [
                    'message' => $e->getMessage(),
                    'details' => $e->getTrace(),
                ]
            );
        }
    }

    public function set_facility(AddFacilityRequest $request){
        try{

            DB::beginTransaction();

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            if(count($court_partner->facilities) >= 1){
                $court_partner->facilities()->detach();
            }

            if(count($request->facility_id) > 1){

                $court_partner->facilities()->attach($request->facility_id);
            }

            DB::commit();

            return ResponseFormatter::success(null,'Berhasil Memperbaharui Fasilitas');

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function add_image(AddImageRequest $request){
        try{

            DB::beginTransaction();

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $extension = $request->image->getClientOriginalExtension();
            $file_name = (uniqid().time()).'.'.$extension;
            $content = file_get_contents($request->image->getRealPath());
            Storage::put('public/'.'court_partner/profile/'.$file_name, $content);
            //$file_name = 'court_partner/profile/'.$this->image_service->storeImage("court_partner/profile/",$request->image);

            if($court_partner->profile != null){
                try{
                    Storage::disk('public')->delete($court_partner->profile);
                }catch(Exception $e){

                }

            }

            $court_partner->profile = 'court_partner/profile/'.$file_name;

            $court_partner->save();

            DB::commit();

            return ResponseFormatter::success(null, 'Berhasil Menambahkan Profile');

        }catch (Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function delete_image(){
        try{

            DB::beginTransaction();

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            Storage::disk('public')->delete($court_partner->profile);

            $court_partner->profile = null;

            $court_partner->save();

            DB::commit();

            return ResponseFormatter::success(null,'Berhasil Menghapus Profile');

        }catch (Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function update_location(UpdateLocationRequest $request){
        try{
            DB::beginTransaction();

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $court_partner->google_map = $request->google_map;
            $court_partner->address = $request->address;

            $court_partner->save();

            DB::commit();

            return ResponseFormatter::success(null,'Berhasil Memperbaharui Lokasi Partner');

        }catch (Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function update_city(UpdateCityRequest $request){
        try{

            $city = City::find($request->city_id);

            if(!$city){
                return ResponseFormatter::error(null,'City tidak ditemukan');
            }

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $court_partner->city_id = $city->id;

            $court_partner->save();

            return ResponseFormatter::success(null,'Berhasil Merubah Kota');
        }catch (Exception $e) {
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }
}
