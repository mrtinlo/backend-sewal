<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ImageService;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterPartnerRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Resources\Auth\GetCourtPartnerResource;
use App\Http\Resources\Auth\GetUserResource;
use App\Models\CourtPartner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\Concerns\Has;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private $image_service;

    public function __construct(ImageService $image_service){
        $this->image_service =  $image_service;
    }

    public function login(LoginRequest $request){
        try{
            DB::beginTransaction();

            $credentials = $request->only('email', 'password');

            $user = User::where('email',$request->email)->first();

            if(!$user){
                return response('Email tidak terdaftar',401);
            }

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $token = $user->createToken('authToken')->plainTextToken;

                DB::commit();
                return response($token);
            }

            return response('Password Salah',401);

        }catch (Exception $e){
            DB::rollBack();
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function register_partner(RegisterPartnerRequest $request){
        try{
            DB::beginTransaction();
            $role = Role::where('name', 'partner')->first();

            $red = rand(0, 255);
            $green = rand(0, 255);
            $blue = rand(0, 255);

            $complementaryRed = 255 - $red;
            $complementaryGreen = 255 - $green;
            $complementaryBlue = 255 - $blue;

            $complementaryColor = sprintf("#%02x%02x%02x", $complementaryRed, $complementaryGreen, $complementaryBlue);

            $db_user = User::where('color', $complementaryColor)->get();

            if(count($db_user) > 0){

                while(count($db_user) > 0){
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
                'name' => $request->name,
                'email' => $request->email,
                'color' => $complementaryColor,
                'password' => Hash::make($request->password),
                'phone' => $request->phone
            ]);

            $file_name = null;

            if($request->image){
                //$file_name = 'court_partner/profile/'.$this->image_service->storeImage("court_partner/profile/",$request->image);

                $extension = $request->image->getClientOriginalExtension();
                $file_name = (uniqid().time()).'.'.$extension;
                $content = file_get_contents($request->image->getRealPath());
                Storage::put('public/'.'court_partner/profile/'.$file_name, $content);

                $file_name = 'court_partner/profile/'.$file_name;
            }

            $partner = CourtPartner::Create([
                'end_at' => Carbon::parse($request->end_at)->format('H:i:s'),
                'start_at' => Carbon::parse($request->start_at)->format('H:i:s'),
                'pin' => Hash::make($request->pin),
                'is_down_payment' => boolval($request->is_down_payment),
                'membership_type' => $request->membership_type,
                'city_id' => $request->city_id,
                'name' => $request->partner_name,
                'user_id' => $user->id,
                'bank_account_name' => $request->bank_account_name,
                'bank_account_number' => $request->bank_account_number,
                'down_payment_percentage' => $request->down_payment_percentage,
                'down_payment_amount' => $request->down_payment_amount ? $request->down_payment_amount : 0,
                'down_payment_type' => $request->down_payment_type,
                'whatsapp_notification' => boolval($request->whatsapp_notification),
                'is_keep' => boolval($request->is_keep),
                'profile' => $file_name,
                'address' => $request->address ? $request->address : null,
                'google_map' => $request->google_map ? $request->google_map : null
            ]);

            $user->assignRole($role);
            DB::commit();
            return ResponseFormatter::success(null, 'Berhasil Membuat Akun Partner Baru');

        }catch (Exception $e){
            DB::rollBack();
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );

        }
    }

    public function register_user(RegisterUserRequest $request){
        try{

            $role = Role::where('name', 'user')->first();

            $red = rand(0, 255);
            $green = rand(0, 255);
            $blue = rand(0, 255);

            $complementaryRed = 255 - $red;
            $complementaryGreen = 255 - $green;
            $complementaryBlue = 255 - $blue;

            $complementaryColor = sprintf("#%02x%02x%02x", $complementaryRed, $complementaryGreen, $complementaryBlue);

            $db_user = User::where('color', $complementaryColor)->get();

            if(count($db_user) > 0){

                while(count($db_user) > 0){
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
            $partner = User::Create([
                'name' => $request->name,
                'email' => $request->email,
                'color' => $complementaryColor,
                'password' => Hash::make($request->password),
                'phone' => $request->phone
            ]);

            $partner->assignRole($role);

            return ResponseFormatter::success(null, 'Berhasil Membuat Akun Baru');

        }catch (Exception $e){
            DB::rollBack();
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );

        }
    }

    public function change_password(ChangePasswordRequest $request){
        try{
            DB::beginTransaction();

            if (!Hash::check($request->old_password, Auth::user()->password)) {
                return ResponseFormatter::error(
                    null, 'Password Lama tidak sesuai', 401
                );
            }

            User::find(Auth::user()->id)->update([
                'password' => Hash::make($request->new_password)
            ]);

            DB::commit();

            return ResponseFormatter::success(
                null,'Password berhasil diubah'
            );

        }catch (Exception $e){
            DB::rollBack();
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );

        }
    }

    public function logout(Request $request)
    {
        try{
            $request->user()->currentAccessToken()->delete();

            return ResponseFormatter::success(null,'Successfully logged out');
        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function get_user(){
        try{
            $user = User::find(Auth::user()->id);
            if($user->roles[0]->name == 'User'){
                $data = new GetUserResource($user);
            }else if($user->roles[0]->name == 'Partner'){
                $data = new GetCourtPartnerResource($user);
            }else{
                $data = new GetUserResource($user);
            }

            return ResponseFormatter::success(
                $data, "User Data"
            );

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }
}
