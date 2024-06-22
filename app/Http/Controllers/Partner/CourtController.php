<?php

namespace App\Http\Controllers\Partner;

use App\Helpers\ImageService;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Admin\CourtPartnerController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\Court\AddCourtRequest;
use App\Http\Requests\Partner\Court\AddImageRequest;
use App\Http\Requests\Partner\Court\DeleteCourtRequest;
use App\Http\Requests\Partner\Court\GetCourtRequest;
use App\Http\Requests\Partner\Court\UpdateCourtRequest;
use App\Models\Court;
use App\Models\CourtImage;
use App\Models\CourtPartner;
use App\Models\CourtPrice;
use App\Models\CourtType;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class CourtController extends Controller
{

    private $image_service;

    public function __construct(ImageService $image_service){
        $this->image_service =  $image_service;
    }

    public function get_court($court_type_id){
        try{

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $court = Court::select('id','name','status','description')->where('court_partner_id', $court_partner->id)->where('court_type_id',$court_type_id)->get();

            $index_court = 0;
            foreach($court as $each_court){
                $index = 0;
                if(count($each_court->images)>0){
                    foreach($each_court->images as $image){
                        try{
                            $image_path = public_path('storage/'.$image->images);
                            $image_extension = explode('.',$image->images)[1];
                            $image_temp = file_get_contents($image_path);
                            $base64_image = base64_encode($image_temp);
                            $data = [
                                'image' => 'data:image/'.$image_extension.';base64,'.$base64_image,
                                'id' => $image->id
                            ];
                            $court[$index_court]->images[$index] = $data;
                            $index++;
                        }catch (Exception $e){

                        }

                    }
                }
                $index_court++;
            }

            return ResponseFormatter::success([
                'court' => $court
            ]);

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function get_court_detail($id){
        try{

            $court = Court::find($id);

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            if($court->court_partner_id != $court_partner->id){
                return ResponseFormatter::error(null,'Lapangan ini bukan milik anda');
            }

            $image_list = [];
            if(count($court->images)>0){
                foreach($court->images as $image){
                    try{
                        $image_path = public_path('storage/'.$image->images);
                        $image_extension = explode('.',$image->images)[1];
                        $image_temp = file_get_contents($image_path);
                        $base64_image = base64_encode($image_temp);

                        $data = [
                            'image' => 'data:image/'.$image_extension.';base64,'.$base64_image,
                            'id' => $image->id
                        ];

                        $image_list[] = $data;
                    }catch(Exception $e){
                    }
                }
            }

            return ResponseFormatter::success([
                'id' => $court->id,
                'name' => $court->name,
                'description' => $court->description,
                'status' => boolval($court->status),
                'court_type' => [
                    'name' => $court->court_type->name,
                    'id' => $court->court_type_id
                ],
                'image' => $image_list
            ],'Berhasil Mendapatkan Data Lapangan');


        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function add_court(AddCourtRequest $request){
        try{

            DB::beginTransaction();
            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $court = Court::where('court_partner_id', $court_partner->id)->where('court_type_id',$request->court_type_id)->where('name',$request->name)->first();

            if($court != null){
                return ResponseFormatter::error(
                    null,
                    'Nama Court telah digunakan',
                    500
                );
            }

            $court = Court::create([
               'name' => $request->name,
               'description' => $request->description,
               'court_partner_id' => $court_partner->id,
                'court_type_id' => $request->court_type_id
            ]);

            $start = strtotime($court_partner->start_at);
            $end = strtotime($court_partner->end_at);

            $hour = 1;

            $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

            for($j = 0;$j<7;$j++){
                for ($i = $start; $i<$end;$i+= ($hour*3600)){
                    $court_price = CourtPrice::Create([
                        'court_id' => $court->id,
                        'start_time' => date('H:i', $i),
                        'end_time' => date('H:i',$i+3600),
                        'day'=> $days[$j],
                    ]);
                }
            }

            foreach($request->image as $image){
                $extension = $image->getClientOriginalExtension();
                $file_name = (uniqid().time()).'.'.$extension;
                $content = file_get_contents($image->getRealPath());
                Storage::put('public/'.'court/'.$file_name, $content);

                $new_court_image = CourtImage::create([
                    'court_id' => $court->id,
                    'images' => 'court/'.$file_name
                ]);
            }
            DB::commit();
            return ResponseFormatter::success(null, 'Court Baru Telah Berhasil Ditambahkan');

        }catch(Exception $e){
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function update_court(UpdateCourtRequest $request){
        try{

            $court = Court::find($request->id);

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            if($court->court_partner_id != $court_partner->id){
                return ResponseFormatter::error(
                    null,
                    'Court Bukan Milik Anda',
                    500
                );
            }

            $court->name = $request->name;
            $court->description = $request->description;


            $court->status = $request->status;

            $court->save();

            return ResponseFormatter::success(null, 'Court Telah Berhasil Diubah');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function delete_court($id){
        try{

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            $court = Court::find($id);

            if($court == null){
                return ResponseFormatter::error(
                    null,
                    'Court Tidak Ditemukan',
                    500
                );
            }

            if($court->court_partner_id != $court_partner->id){
                return ResponseFormatter::error(
                    null,
                    'Court Bukan Milik Anda',
                    500
                );
            }

            $court_images = CourtImage::where('court_id',$court->id)->get();

            foreach($court_images as $image){
                Storage::disk('public')->delete($image->images);
            }

            $court->delete();

            return ResponseFormatter::success(null, 'Court Telah Berhasil Dihapus');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function add_image(AddImageRequest $request){
        try{

            $court = Court::find($request->court_id);

            $court_partner = CourtPartner::where('user_id',Auth::user()->id)->first();

            if($court->court_partner_id != $court_partner->id){
                return ResponseFormatter::error(null,'Lapangan ini bukan milik anda');
            }

            $court_image_list = CourtImage::where('court_id',$court->id)->get();

            if(count($court_image_list) > 5){
                return ResponseFormatter::error(null,'Anda Mencapai limit gambar');
            }

            DB::beginTransaction();

            //$file_name = 'court/'.$this->image_service->storeImage("court/",$request->image);

            $court_images = CourtImage::where('court_id',$court->id)->get();

            foreach($court_images as $image){
                Storage::disk('public')->delete($image->images);
                $image->delete();
            }

            foreach($request->image as $image){
                $extension = $image->getClientOriginalExtension();
                $file_name = (uniqid().time()).'.'.$extension;
                $content = file_get_contents($image->getRealPath());
                Storage::put('public/'.'court/'.$file_name, $content);

                $new_court_image = CourtImage::create([
                    'court_id' => $court->id,
                    'images' => 'court/'.$file_name
                ]);
            }

            DB::commit();

            return ResponseFormatter::success(null,'Berhasil Menambahkan Gambar Lapangan');

        }catch(Exception $e){
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }

    public function delete_image($id){
        try{

            DB::beginTransaction();

            $court_image = CourtImage::find($id);

            if($court_image != null){
                Storage::disk('public')->delete($court_image->image);

                $court_image->delete();
            }else{
                return ResponseFormatter::error(null,'Gambar tidak ditemukan');
            }

            DB::commit();

            return ResponseFormatter::success(null,'Berhasil Menghapus Gambar');

        }catch(Exception $e){
            DB::rollBack();
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'General Error', 500);
        }
    }
}
