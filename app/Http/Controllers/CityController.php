<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Http\Requests\City\CreateCityRequest;
use App\Http\Requests\City\EditCityRequest;
use App\Models\City;
use Illuminate\Http\Request;
use Exception;

class CityController extends Controller
{
    public function add(CreateCityRequest $request){
        try{

            $city = City::create([
                'name' => $request->name
            ]);

            return ResponseFormatter::success(null,'Berhasil Menambahkan Kota');

        }catch(Exception $e){
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function edit(EditCityRequest $request){
        try{

            $city = City::find($request->id);

            $city->name = $request->name;
            $city->save();

            return ResponseFormatter::success(null,'Berhasil Merubah Kota');

        }catch(Exception $e){
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }

    public function get(Request $request){
        try{

            if($request->query('search') !== null){
                $city = City::where('name','LIKE','%'.$request->query('search').'%')->select('name','id')->orderBy('name','asc')->get();
            }else{
                $city = City::select('name','id')->orderBy('name','asc')->get();
            }

            return ResponseFormatter::success($city,'Berhasil Mendapatkan Daftar Kota');

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

            $city = City::find($id);

            if($city == null){
                return ResponseFormatter::error(null,'General Error');
            }

            $city->delete();

            return ResponseFormatter::success(null,'Berhasil Menghapus Kota');

        }catch(Exception $e){
            return ResponseFormatter::error(
                ['error' => $e->getMessage()],
                'General Error',
                500
            );
        }
    }
}
