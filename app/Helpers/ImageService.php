<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class  ImageService
{
    public function storeImage($destination, $image){
        $file_name = null;

        $image_format = explode('/',$image)[1];
        $image_format = explode(';',$image_format)[0];

        $image = explode(',', $image)[1];
        $image = str_replace(' ','+',$image);
        $decode_file = base64_decode($image);

        $extension = ".".$image_format;
        $file_name = (uniqid().time()).$extension;
        Storage::put('public/'.$destination.$file_name, $decode_file);
        return $file_name;
    }

}
