<?php

namespace App\Http\Controllers\Partner;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\CourtType;
use Illuminate\Http\Request;
use Exception;

class CourtTypeController extends Controller
{
    public function get_court_type(){
        try{

            $query = CourtType::query()->select('id','name');

            $total_court_type = $query->count();
            $court_types = $query->get();

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
