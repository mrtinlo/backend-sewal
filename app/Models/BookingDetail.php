<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = [];

    public function court(){
        return $this->belongsTo(Court::class);
    }

    public function booking(){
        return $this->belongsTo(Booking::class);
    }

    public function paymentDetail(){
        return $this->hasOne(PaymentDetail::class);
    }

}
