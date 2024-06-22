<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keep extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function keepDetail(){
        return $this->hasMany(KeepDetail::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function courtPartner(){
        return $this->belongsTo(CourtPartner::class);
    }
}
