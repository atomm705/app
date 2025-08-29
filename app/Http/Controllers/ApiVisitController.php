<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\LegacyVisit;
use App\Models\LegacyVisitInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\LegacyClient;

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

        $visits = LegacyVisit::select('id', 'date', 'facility_id', 'doctor_id', 'time_slot_id', 'status')->where('visitor_id', $pid)->with(['doctor', 'facility', 'time_slot'])->where('status', ['new', 'payed', 'partpayed'])->get();

        $items = $visits->map(function($v){
           return [
               'id' => $v->id,
               'date' => $v->date,
               'time' => $v->time_slot->time,
               'facility' => $v->facility->facility_name,
               'doctor' => $v->doctor->last_name .' ' .$v->doctor->first_name,
               'status' => $v->status,
           ];
        });
        return response()->json([
            'ok' => true,
            'visits' => $items,

        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function visit(Request $request){

        Log::info('Raw body', ['body' => $request->getContent()]);

        /* One app - one visitor */

        $visitId = (int) ($request->input('visitId') ?? $request->input('visit_id'));
        if(!$visitId){
            return response()->json([
                'ok' => false,
                'message' => 'У Вас немає доступу до цього візиту, або візит не існує',
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        $visit = LegacyVisit::query()
            ->with([
                'doctor',
                'facility',
            ])
            ->select('id','date','time','status','doctor_id','facility_id','visitor_id') // FK обовʼязково
            ->find($visitId);
        if(!$visit){
            return response()->json([
                'ok' => false,
                'message' => 'У Вас немає доступу до цього візиту, або візит не існує',
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        $user = $request->user();
        $pid  = (int) $visit->visitor_id; // або patient_id, якщо у вас так

        // Перевірка доступу: або many-to-many (patients), або hasOne (oneVisitor)
        $hasAccess = method_exists($user, 'patients')
            ? $user->patients()->whereKey($pid)->exists()
            : (optional($user->oneVisitor)->id === $pid);

        if (!$hasAccess) {
            return response()->json(['ok'=>false,'message'=>'Немає доступу до цього пацієнта'], 403, [], JSON_UNESCAPED_UNICODE);
        }


        $resp = LegacyClient::pdf($request->visitId);
        if ($resp->failed()) {
            return response()->json(['ok'=>false,'message'=>'Legacy error','status'=>$resp->status()], 502);
        }

        $name = "visit_{$request->visitId}.pdf";
        return response($resp->body(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'"',
            'Cache-Control'       => 'private, max-age=0, must-revalidate',
        ]);
    }

    public function pdf(Request $request, int $id)
    {
        $visit = LegacyVisit::select('id','visitor_id','facility_id','date')->findOrFail($id);

        // Перевірка доступу
        $user = $request->user();
        $pid  = (int) $visit->visitor_id;
        $hasAccess = method_exists($user, 'patients')
            ? $user->patients()->whereKey($pid)->exists()
            : (optional($user->oneVisitor)->id === $pid);

        if (!$hasAccess) {
            return response()->json(['ok'=>false,'message'=>'Немає доступу'], 403, [], JSON_UNESCAPED_UNICODE);
        }

        // Генерація PDF (використай вашу наявну логіку/вʼюхи)
        // приклад із DomPDF:
        $info = VisitDiagInfo::where('visit_id', $visit->id)->get();
        $pdf = Pdf::loadView('visits.default.to_pdf', [
            'visit' => $visit,
            'info'  => $info,
            'childrens' => collect(), // якщо потрібно — підставте як у вашому коді
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $fileName = "visit_{$visit->id}.pdf";

        // inline (перегляд) або attachment (завантаження)
        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'Cache-Control'       => 'private, max-age=0, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    private function safeJsonDecode($value)
    {
        if (is_array($value)) return $value;
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }
        return $value;
    }
}
