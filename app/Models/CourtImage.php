<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourtImage extends Model
{
    use HasFactory;

    protected $guarded =[];

    protected $hidden = ['pivot'];

    public function court(){
        return $this->belongsTo(Court::class);
    }
}
