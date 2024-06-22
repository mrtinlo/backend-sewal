<?php

namespace App\Http\Controllers\Partner;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\Pin\SubmitPinRequest;
use App\Http\Requests\Partner\Pin\UpdatePinRequest;
use App\Models\CourtPartner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Exception;

class  PinController extends Controller
{
    public function pin(SubmitPinRequest $request){

        try{

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            if (!Hash::check($request['pin'], $court_partner->pin, [])) {
                return ResponseFormatter::error(null,'Pin Salah', 401);
            }

            return ResponseFormatter::success(null,'Pin Benar');

        } catch (Exception $e) {
            return ResponseFormatter::error(
                [
                    'message' => $e->getMessage(),
                    'details' => $e->getTrace(),
                ]
            );
        }
    }

    public function edit(UpdatePinRequest $request){

        try{

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            if(!Hash::check($request->old_pin,$court_partner->pin,[])){
                return ResponseFormatter::error(null,'Pin Lama Tidak Sesuai');
            }

            $court_partner->pin =Hash::make($request->new_pin);
            $court_partner->save();

            return ResponseFormatter::success(null, 'Berhasil Mengubah Pin');
        } catch (Exception $e) {
            return ResponseFormatter::error(
                [
                    'message' => $e->getMessage(),
                    'details' => $e->getTrace(),
                ]
            );
        }

    }
}
