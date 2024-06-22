<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourtType extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function court(){
        return $this->hasMany(Court::class);
    }

    public function court_partners(){
        return $this->belongsToMany(CourtType::class,'court_partner_court_types','court_type_id','court_partner_id')->orderBy('court_partner_court_types.created_at', 'desc');
    }
}
