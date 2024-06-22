<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnregisterUser extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'id'
    ];

    public function courtPartner(){
        return $this->belongsTo(CourtPartner::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
