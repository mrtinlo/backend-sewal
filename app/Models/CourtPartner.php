<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class CourtPartner extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['pivot'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function court(){
        return $this->hasMany(Court::class);
    }

    public function booking(){
        return $this->hasMany(Booking::class);
    }

    public function court_types(){
        return $this->belongsToMany(CourtType::class,'court_partner_court_types','court_partner_id','court_type_id')->orderBy('court_partner_court_types.created_at', 'desc');
    }

    public function court_types_without_pivot(){
        return $this->belongsToMany(CourtType::class,'court_partner_court_types','court_partner_id','court_type_id')->orderBy('court_partner_court_types.created_at', 'desc');
    }

    public function total_court(){
        return $this->hasMany(Court::class)->count('id');
    }

    public function city(){
        return $this->belongsTo(City::class);
    }

    public function facilities(){
        return $this->belongsToMany(Facility::class, 'court_partner_facilities','court_partner_id','facility_id');
    }
}
