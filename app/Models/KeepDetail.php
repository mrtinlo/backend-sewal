<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeepDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function court(){
        return $this->belongsTo(Court::class);
    }

    public function keep(){
        return $this->belongsTo(Keep::class);
    }
}
