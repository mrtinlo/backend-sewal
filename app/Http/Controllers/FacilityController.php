<?php

namespace App\Http\Controllers;

use App\Helpers\ImageService;
use App\Helpers\ResponseFormatter;
use App\Http\Requests\Facility\CreateFacilityRequest;
use App\Http\Requests\Facility\EditFacilityRequest;
use App\Models\Facility;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Storage;

class FacilityController extends Controller
{
    private $image_service;

    public function __construct(ImageService $image_service){
        $this->image_service =  $image_service;
    }

    public function get(Request $request){
        try{
            if($request->query('search') !== null){
                $facility = Facility::where('name','LIKE','%'.$request->query('search').'%')->select('name','id','icon')->orderBy('name','asc')->get();
            }else{
                $facility = Facility::select('name','id','icon')->orderBy('name','asc')->get();
            }

            $index =0;
            foreach($facility as $each){

                if($each->icon != null){
                    try{
                        $image_path = public_path('storage/'.$each->icon);
                        $image_extension = explode('.',$each->icon)[1];
                        $image = file_get_contents($image_path);
                        $base64_image = base64_encode($image);
                        $facility[$index]->icon = 'data:image/'.$image_extension.';base64,'.$base64_image;
                    }catch(Exception $e){

                    }

                }else{
                    $facility[$index]->icon = null;
                }

                $index++;
            }

            return ResponseFormatter::success($facility,'Berhasil Mendapatkan Daftar Fasilitas');


        }catch(Exception $e){
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function add(CreateFacilityRequest $request){
        try{
            $file_name = null;

            //$file_name = 'facility/'.$this->image_service->storeImage("facility/",$request->image);
            $extension = $request->image->getClientOriginalExtension();
            $file_name = (uniqid().time()).'.'.$extension;
            $content = file_get_contents($request->image->getRealPath());
            Storage::put('public/'.'facility/'.$file_name, $content);
            if($file_name == null){
                return ResponseFormatter::error(null,'Gagal Menyimpan Icon Facility');
            }

            $facility = Facility::create([
                'name' => $request->name,
                'icon' => 'facility/'.$file_name,
            ]);

            return ResponseFormatter::success(null,'Berhasil Menambahkan Fasilitas');

        }catch(Exception $e){

            if($file_name != null){
                Storage::delete('app/public/'.$file_name);
            }

            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function edit(EditFacilityRequest $request){
        try{

            $facility = Facility::find($request->id);

            if($request->image){
                //$file_name = 'facility/'.$this->image_service->storeImage("facility/",$request->image);

                $extension = $request->image->getClientOriginalExtension();
                $file_name = (uniqid().time()).'.'.$extension;
                $content = file_get_contents($request->image->getRealPath());

                Storage::put('public/'.'facility/'.$file_name, $content);
                try{
                    Storage::disk('public')->delete($facility->icon);
                }catch(Exception $e){

                }
                $file_name = 'facility/'.$file_name;
                $facility->icon = $file_name;
            }

            if($request->name != null){
                $facility->name = $request->name;
            }
            $facility->save();

            return ResponseFormatter::success(null,'Berhasil Merubah Fasilitas');

        }catch(Exception $e){
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function delete($id){
        try{

            $facility = Facility::find($id);

            if($facility == null){
                return ResponseFormatter::error(null,'General Error');
            }

            Storage::disk('public')->delete($facility->icon);

            $facility->delete();

            return ResponseFormatter::success(null,'Berhasil Menghapus Fasilitas');

        }catch(Exception $e){
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

}
