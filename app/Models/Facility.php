<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['pivot'];

    public function court_partners(){
        return $this->belongsToMany(CourtPartner::class, 'court_partner_facilities','facility_id','court_partner_id');
    }
}
