<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiVisitController extends Controller
{
    public function select(Request $request){

        Log::info('Raw body', ['body' => $request->getContent()]);
    }
}
