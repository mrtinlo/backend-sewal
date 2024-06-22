<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\CourtPartnerController as AdminCourtPartnerController;
use App\Http\Controllers\Admin\CourtTypeController as AdminCourtTypeController;
use App\Http\Controllers\Partner\CourtController as PartnerCourtController;
use App\Http\Controllers\Partner\CourtPriceController as PartnerCourtPriceController;
use App\Http\Controllers\Partner\BookingController as PartnerBookingController;
use App\Http\Controllers\Partner\ScheduleController as PartnerScheduleController;
use App\Http\Controllers\Partner\CourtPartnerController as PartnerCourtPartnerController;
use App\Http\Controllers\Partner\CourtTypeController as PartnerCourtTypeController;
use App\Http\Controllers\Partner\UserController as PartnerUserController;
use App\Http\Controllers\Partner\PinController as PartnerPinController;
use App\Http\Controllers\Partner\KeepController as PartnerKeepController;
use App\Http\Controllers\Partner\TransactionController as PartnerTransactionController;
use App\Http\Controllers\User\CourtPartnerController as UserCourtPartnerController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\User\BookingController as UserBookingController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('login',[AuthController::class,'login']);
Route::post("register/user",[AuthController::class,'register_user']);

Route::middleware('auth:sanctum')->group(function(){
    Route::get('user',[AuthController::class,'get_user']);
    Route::post('logout',[AuthController::class,'logout']);
    Route::put('change-password',[AuthController::class,'change_password']);

    //admin
    Route::post('court-partner/add-court-type',[AdminCourtPartnerController::class,'add_court_type'])->middleware(['role:Admin']);
    Route::delete('court-partner/delete-court-type',[AdminCourtPartnerController::class,'delete_court_type'])->middleware(['role:Admin']);

    Route::get('court-partner/get-court-partner-list',[AdminCourtPartnerController::class,'get_court_partner_list'])->middleware(['role:Admin']);
    Route::get('court-partner/get-court-partner/{id}',[AdminCourtPartnerController::class,'get_court_partner_data'])->middleware(['role:Admin']);
    Route::delete('court-partner/delete/{id}',[AdminCourtPartnerController::class,'delete_court_partner'])->middleware(['role:Admin']);
    Route::post('court-partner/edit-court-partner',[AdminCourtPartnerController::class,'edit_court_partner'])->middleware(['role:Admin']);

    Route::post('register/partner',[AuthController::class,'register_partner'])->middleware(['role:Admin']);

    Route::get('court-type',[AdminCourtTypeController::class,'get_court_type'])->middleware(['role:Admin']);
    Route::post('add-court-type',[AdminCourtTypeController::class,'add_court_type'])->middleware(['role:Admin']);
    Route::post('update-court-type',[AdminCourtTypeController::class,'update_court_type'])->middleware(['role:Admin']);
    Route::delete('delete-court-type/{id}',[AdminCourtTypeController::class,'delete_court_type'])->middleware(['role:Admin']);

    //partner
    Route::get('partner/court/{court_type_id}',[PartnerCourtController::class,'get_court'])->middleware(['role:Partner']);
    Route::post('partner/court',[PartnerCourtController::class,'add_court'])->middleware(['role:Partner']);
    Route::post('partner/court-update',[PartnerCourtController::class,'update_court'])->middleware(['role:Partner']);
    Route::delete('partner/court-delete/{id}',[PartnerCourtController::class,'delete_court'])->middleware(['role:Partner']);
    Route::post('partner/add-court-image',[PartnerCourtController::class,'add_image'])->middleware(['role:Partner']);
    Route::delete('partner/delete-court-image/{id}',[PartnerCourtController::class,'delete_image'])->middleware(['role:Partner']);

    Route::get('partner/court-type',[PartnerCourtTypeController::class,'get_court_type']);

    Route::get('partner/court/price/{court_id}/{day?}',[PartnerCourtPriceController::class,'get_court_price'])->middleware(['role:Partner']);
    Route::post('partner/court/add-price',[PartnerCourtPriceController::class,'add_court_price'])->middleware(['role:Partner']);
    Route::post('partner/court/clear-price',[PartnerCourtPriceController::class,'clear_court_price'])->middleware(['role:Partner']);
    Route::get('partner/court-detail/{id}',[PartnerCourtController::class,'get_court_detail'])->middleware(['role:Partner']);

    //booking
    Route::get('partner/home/{court_type_id}/{date?}',[PartnerBookingController::class,'home'])->middleware(['role:Partner']);
    Route::post('partner/check-booking',[PartnerBookingController::class,'check_booking'])->middleware(['role:Partner']);
    Route::post('partner/booking',[PartnerBookingController::class,'save_booking'])->middleware(['role:Partner']);
    Route::post('partner/membership',[PartnerBookingController::class,'save_membership_new'])->middleware(['role:Partner']);
    Route::delete('partner/booking-delete/{id}',[PartnerBookingController::class,'cancel_booking'])->middleware(['role:Partner']);
    Route::post('partner/booking-payment',[PartnerBookingController::class,'booking_payment'])->middleware(['role:Partner']);
    Route::get('partner/remain-payment/{id}',[PartnerBookingController::class,'calculate_payment'])->middleware(['role:Partner']);
    //keep
    Route::post('partner/check-keep',[PartnerKeepController::class,'check_keep'])->middleware(['role:Partner']);
    Route::post('partner/keep',[PartnerKeepController::class,'save_keep'])->middleware(['role:Partner']);
    Route::post('partner/keep-to-booking',[PartnerKeepController::class,'transfer_keep_to_book'])->middleware(['role:Partner']);
    Route::delete('partner/keep-delete/{id}',[PartnerKeepController::class,'cancel_keep'])->middleware(['role:Partner']);

    Route::get('partner/get_booking_user_list',[PartnerUserController::class,'get_booking_user_list'])->middleware(['role:Partner']);

    //schedule
    Route::get('partner/schedule/{court_type_id}/{date?}',[PartnerScheduleController::class,'schedule'])->middleware(['role:Partner']);

    //transaction
    Route::get('partner/transaction-total',[PartnerTransactionController::class,'header_transaction'])->middleware(['role:Partner']);
    Route::get('partner/transaction-data',[PartnerTransactionController::class,'data_transaction'])->middleware(['role:Partner']);
    Route::get('partner/transaction-detail/{booking_id}',[PartnerTransactionController::class,'transaction_detail'])->middleware(['role:Partner']);

    //court_partner
    Route::post('partner/update-time',[PartnerCourtPartnerController::class,'update_time'])->middleware(['role:Partner']);
    Route::post('partner/update-down-payment-type',[PartnerCourtPartnerController::class,'update_down_payment_type'])->middleware(['role:Partner']);
    Route::post('partner/update-down-payment-amount',[PartnerCourtPartnerController::class,'update_down_payment_amount'])->middleware(['role:Partner']);
    Route::post('partner/update-down-payment-percentage',[PartnerCourtPartnerController::class,'update_down_payment_percentage'])->middleware(['role:Partner']);
    Route::post('partner/update-membership-type',[PartnerCourtPartnerController::class,'update_membership_type'])->middleware(['role:Partner']);
    Route::post('partner/update-is-keep',[PartnerCourtPartnerController::class,'update_is_keep'])->middleware(['role:Partner']);
    Route::post('partner/update-profile',[PartnerCourtPartnerController::class,'add_image'])->middleware(['role:Partner']);
    Route::delete('partner/delete-profile',[PartnerCourtPartnerController::class,'delete_image'])->middleware(['role:Partner']);
    Route::post('partner/update-location',[PartnerCourtPartnerController::class,'update_location'])->middleware(['role:Partner']);

    //pin
    Route::post('partner/submit-pin',[PartnerPinController::class,'pin'])->middleware(['role:Partner']);
    Route::put('partner/update-pin',[PartnerPinController::class,'edit'])->middleware(['role:Partner']);

    //user
    Route::get('user/court-partner-list',[UserCourtPartnerController::class,'get_court_partner_list'])->middleware(['role:User']);
    Route::get('user/court-partner-data/{id}',[UserCourtPartnerController::class,'get_court_partner_data'])->middleware(['role:User']);
    Route::get('user/court-list',[UserCourtPartnerController::class,'get_court_list'])->middleware(['role:User']);
    Route::get('user/court-available-time',[UserCourtPartnerController::class,'get_court_available_time'])->middleware(['role:User']);

    Route::post('user/booking',[UserBookingController::class,'save_booking'])->middleware(['role:User']);
    Route::delete('user/booking-delete/{id}',[UserBookingController::class,'cancel_booking'])->middleware(['role:User']);
    Route::get('user/history',[UserBookingController::class,'booking_history'])->middleware(['role:User']);
    Route::post('user/booking-check',[UserBookingController::class,'check_booking'])->middleware(['role:User']);

    Route::get('user/incoming-schedule',[UserBookingController::class,'incoming_schedule'])->middleware(['role:User']);

    Route::get('facilities',[FacilityController::class,'get']);
    Route::post('facilities',[FacilityController::class,'add'])->middleware(['role:Admin']);
    Route::post('facilities-edit',[FacilityController::class,'edit'])->middleware(['role:Admin']);
    Route::delete('facilities/{id}',[FacilityController::class,'delete'])->middleware(['role:Admin']);

    Route::post('partner/facilities-update',[PartnerCourtPartnerController::class,'set_facility'])->middleware(['role:Partner']);

    Route::get('city',[CityController::class,'get']);
    Route::post('city',[CityController::class,'add'])->middleware(['role:Admin']);
    Route::post('city-edit',[CityController::class,'edit'])->middleware(['role:Admin']);
    Route::delete('city/{id}',[CityController::class,'delete'])->middleware(['role:Admin']);

    Route::post('callback',[\App\Http\Controllers\IpaymuController::class,'callback']);

});
