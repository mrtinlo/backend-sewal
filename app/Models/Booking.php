<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = [];

    protected $hidden = [
        'id'
    ];

    public function court_partner(){
        return $this->belongsTo(CourtPartner::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function booking_detail(){
        return $this->hasMany(BookingDetail::class);
    }

    public function payment(){
        return $this->hasMany(Payment::class);
    }

    public function courtPartnerNotification(){
        return $this->hasMany(CourtPartnerNotification::class);
    }

    public function customerNotification(){
        return $this->hasMany(CustomerNotification::class);
    }
}
