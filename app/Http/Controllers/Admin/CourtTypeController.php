<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageService;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourtType\AddCourtTypeRequest;
use App\Http\Requests\Admin\CourtType\DeleteCourtTypeRequest;
use App\Http\Requests\Admin\CourtType\GetCourtTypeRequest;
use App\Http\Requests\Admin\CourtType\UpdateCourtTypeRequest;
use App\Models\CourtType;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Storage;

class CourtTypeController extends Controller
{
    private $image_service;

    public function __construct(ImageService $image_service){
        $this->image_service =  $image_service;
    }

    public function add_court_type(AddCourtTypeRequest $request){
        try{

            //$file_name = 'court_type/'.$this->image_service->storeImage("court_type/",$request->image);


            $extension = $request->image->getClientOriginalExtension();
            $file_name = (uniqid().time()).'.'.$extension;
            $content = file_get_contents($request->image->getRealPath());
            Storage::put('public/'.'court_type/'.$file_name, $content);
            $court_type = CourtType::Create([
                'name' => $request->name,
                'icon' => 'court_type/'.$file_name
            ]);

            return ResponseFormatter::success(null,'Court Type Baru Berhasil Ditambahkan');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function delete_court_type($id){
        try{
            $court_type = CourtType::find($id);

            if($court_type->icon){
                Storage::disk('public')->delete($court_type->icon);
            }

            if($court_type == null){
                return ResponseFormatter::error(
                    null,
                    'Court Type Tidak Ditemukan',
                    500
                );
            }

            $court_type->delete();

            return ResponseFormatter::success(null,'Court Type Telah Berhasil Dihapus');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function update_court_type(UpdateCourtTypeRequest $request){
        try{
            $court_type = CourtType::find($request->id);

            if($court_type == null){
                return ResponseFormatter::error(
                    null,
                    'Court Type Tidak Ditemukan',
                    500
                );
            }

            if($request->image){
                //$file_name = 'court_type/'.$this->image_service->storeImage("court_type/",$request->image);
                $extension = $request->image->getClientOriginalExtension();
                $file_name = (uniqid().time()).'.'.$extension;
                $content = file_get_contents($request->image->getRealPath());
                Storage::put('public/'.'court_type/'.$file_name, $content);
                if($court_type->icon){
                    Storage::disk('public')->delete($court_type->icon);
                }

                $court_type->icon = 'court_type/'.$file_name;
            }

            if($request->name){
                $court_type->name = $request->name;
            }
            $court_type->save();

            return ResponseFormatter::success(null,'Court Type Berhasil Diubah');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function get_court_type(){
        try{

            $query = CourtType::query()->select('id','name','icon');

            $total_court_type = $query->count();
            $court_types = $query->get();

            $index = 0;
            foreach($court_types as $each){
                try{
                    $image_path = public_path('storage/'.$each->icon);
                    $image_extension = explode('.',$each->icon)[1];
                    $image = file_get_contents($image_path);
                    $base64_image = base64_encode($image);
                    $court_types[$index]->icon = 'data:image/'.$image_extension.';base64,'.$base64_image;
                    $index++;
                }catch (Exception $e){
                    $court_types[$index]->icon = null;
                }
            }

            return ResponseFormatter::success([
                'court_type'=> $court_types,
                'total_court_type' => $total_court_type
            ]);

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }
}
