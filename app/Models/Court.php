<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Court extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function courtPartner(){
        return $this->belongsTo(CourtPartner::class);
    }

    public function bookingDetail(){
        return $this->hasMany(BookingDetail::class);
    }

    public function keepDetail(){
        return $this->hasMany(KeepDetail::class);
    }

    public function images(){
        return $this->hasMany(CourtImage::class);
    }

    public function court_type(){
        return $this->belongsTo(CourtType::class);
    }
}
