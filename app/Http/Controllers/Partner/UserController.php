<?php

namespace App\Http\Controllers\Partner;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\CourtPartner;
use App\Models\UnregisterUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class UserController extends Controller
{
    public function get_booking_user_list(){
        try{
            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $user = User::select('name','phone')->where('email','!=',null)->withoutRole(['Admin','Partner']);

            $unregister_user = UnregisterUser::select('unregister_users.name as name','users.phone as phone')->join('users','user_id','users.id')->where('court_partner_id',$court_partner->id);

            $union_query = $user->union($unregister_user);

            $sort_query = DB::table(DB::raw("({$union_query->toSql()}) as combined"))->mergeBindings($union_query->getQuery())->orderBy('name','asc')->get();

            return ResponseFormatter::success($sort_query,'Berhasil Mendapatkan data Customer');

        }catch (Exception $e){
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );

        }
    }
}
