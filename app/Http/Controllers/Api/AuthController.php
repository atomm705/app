<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\SmsVerification;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function precreate(Request $request){
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'middle_name' => 'string',
            'phone' => 'required|string',
            'birthday' => 'required|date',
        ]);

        // 🔁 Звернення до CRM API
        $response = Http::withToken(config('service.crm.token'))
        ->get(config('services.crm.url') . '/patients/check', [
            'fio' => $request->last_name .' '. $request->first_name.' ' .$request->middle_name,
            'phone' => $request->phone,
            'birthday' => $request->birthday,
        ]);

        if ($response->status() !== 200) {
            return response()->json(['error' => 'Пацієнта не знайдено'], 404);
        }

        $code = rand(1000, 9999);

        SmsVerification::updateOrCreate(
            ['phone' => $request->phone],
            ['code' => $code, 'expires_at' => now()->addMinutes(5)]
        );

        // TODO: Надіслати SMS — тут заглушка
        logger("СМС-код для {$request->phone}: {$code}");

        return response()->json(['message' => 'Код надіслано '.$code]);
    }

    public function verifyCode(Request $request){
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string',
        ]);

        $record = SmsVerification::where('phone', $request->phone)->first();

        if (!$record || $record->code !== $request->code || $record->isExpired()) {
            return response()->json(['error' => 'Невірний або протермінований код'], 422);
        }

        return response()->json(['message' => 'Код підтверджено']);
    }

    public function createPassword(Request $request){
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        $record = SmsVerification::where('phone', $request->phone)->first();

        if (!$record || $record->isExpired()) {
            return response()->json(['error' => 'Код протермінований'], 422);
        }

        $user = User::updateOrCreate(
            ['phone' => $request->phone],
            [
                'name' => 'Пацієнт',
                'password' => Hash::make($request->password),
                'phone_verified_at' => now(),
            ]
        );

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

}
