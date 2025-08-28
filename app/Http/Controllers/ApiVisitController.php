<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\LegacyVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use function Pest\Mutate\result;

class ApiVisitController extends Controller
{
    public function select(Request $request){

        Log::info('Raw body', ['body' => $request->getContent()]);

        /* One app - one visitor */
        $appUser = $request->user();
        $visitors = $appUser->oneVisitor()   // або ->visitors()
        ->select('id','last_name','first_name','middle_name')
            ->orderBy('last_name')
            ->get();

        // мапимо під відповідь
        $patients = $visitors->map(function ($v) {
            return [
                'id'  => $v->id,
                'fio' => trim($v->last_name.' '.$v->first_name.' '.$v->middle_name),
            ];
        })->values();

        return response()->json([
            'ok'       => true,
            'patients' => $patients,
        ], 200, [], JSON_UNESCAPED_UNICODE);
        /* one app - more visitors */
    }

    public function visits(Request $request){
        Log::info('Raw body', ['body' => $request->getContent()]);

        /* One app - one visitor */

        $appUser = $request->user();


        $pid = (int) $request->input('patientId');
        $hasAccess = $appUser->OneVisitor()->whereKey($pid)->exists();

        if(!$hasAccess){
            return response()->json([
               'ok' => false,
               'message' => 'У вас немає доступу до цього пацієнта, або пацієнт не існує.',
            ], 400, []);
        }

        $visits = LegacyVisit::select('id', 'date')->where('visitor_id', $pid)->with(['doctor', 'facility'])->get();

        $items = $visits->map(function($v){
           return [
               'id' => $v->id,
               'date' => $v->date,
               'facility' => $v->facility->facility_name,
               'doctor' => $v->doctor->last_name .' ' .$v->doctor->first_name,
           ];
        });
        return response()->json([
            'ok' => true,
            'visits' => $items,

        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
