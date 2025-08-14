<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\AppVerificationCode;
use App\Models\Patient;
use App\Models\SmsVerification;
use Illuminate\Http\Request;
use App\Models\Api;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessTokenResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SoapClient;


class ApiController extends Controller
{
    /** SMS authorisation  */
    public function auth()
    {
        $client = new SoapClient('http://turbosms.in.ua/api/wsdl.html');

//      Можно просмотреть список доступных методов сервера
//      print_r($client->__getFunctions());

        $args = [
            'login' => 'okc',
            'password' => 'YVHf5vges7g3MhW'
        ];
        $client->Auth($args);

        return $client;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $apis = Api::all();

        return response()->json($apis);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = new Api();
        $user->phone = $request->phone;
        $user->birthday = $request->birthday;
        $user->save();

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function register(Request $request){

        Log::info('Raw body', ['body' => $request->getContent()]);

        // Форматуємо дату народження
        $dobs = explode(".", $request->dob);
        $dob = $dobs[2] . '-' . $dobs[1] . '-' . $dobs[0];

        // Пошук пацієнта
        $patient = Patient::where('last_name', $request->lastName)
            ->where('first_name', $request->firstName)
            ->where('middle_name', $request->middleName)
            ->where('phone', $request->phone)
            ->where('dob', $dob)
            ->first();

        if (!$patient) {
            return response()->json(['ok' =>false, 'error' => 'Пацієнта не знайдено'], 404);
        }

        $user = AppUser::where('patient_id', $patient->id)->first();

        if ($user) {
            return response()->json(['ok' => false, 'error' => 'Користувач існує. Авторизуйтеся.'], 409);
        }

        // Перевірка останнього СМС
        $lastSms = SmsVerification::where('phone', $request->phone)
            ->latest()
            ->first();

        if ($lastSms && $lastSms->last_sent_at && $lastSms->last_sent_at->gt(now()->subMinutes(3))) {
            $waitMinutes = now()->diffInMinutes($lastSms->last_sent_at, false);
            return response()->json([
                'ok' => true,
                'error' => "СМС вже відправлено. Спробуйте знову через {$waitMinutes} хв."
            ], 429);
        }

        // Генеруємо код з 4 цифр
        $code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Зберігаємо код
        SmsVerification::updateOrCreate(
            ['phone' => $request->phone],
            [
                'code' => $code,
                'last_sent_at' => now(),
                'expires_at' => now()->addMinutes(15),
            ]
        );

        // Відправка СМС
        try {
            $client = $this->auth();
            $sms = [
                'sender' => 'OK-Centre',
                'destination' => $request->phone,
                'text' => $code,
            ];
            $sended = $client->SendSMS($sms);
            $result = $sended->SendSMSResult->ResultArray[0];
        } catch (\Exception $e) {
            Log::error('SMS sending failed', ['error' => $e->getMessage()]);
            $result = 'Помилка відправки СМС';
        }

        return response()->json([
            'ok' => true,
            'message' => 'Вам відправлено СМС з кодом. Введіть його в поле вище.',
            'sms_status' => $result
        ]);
    }

    public function sms_verification(Request $request){
        $phone = $request->phone;
        $code = $request->code;

        Log::info('Raw body', ['body' => $request->getContent()]);
        // Знаходимо останній код для цього телефону
        $sms = SmsVerification::where('phone', $phone)
            ->orderByDesc('created_at')
            ->first();

        if (!$sms) {
            return response()->json([
                'ok' => false,
                'message' => 'Код не знайдено'
            ], 404);
        }

        // Перевірка часу дії
        if ($sms->expires_at->lt(now())) {
            return response()->json([
                'ok'=>false,
                'message' => 'Код прострочено'
            ], 400);
        }

        // Перевірка коду
        if ($sms->code !== $code) {
            return response()->json([
                'ok' => false,
                'message' => 'Невірний код'
            ], 400);
        }

        $sms->delete();

        return response()->json([
            'ok'=> true,
            'message' => 'Код підтверджено успішно'
        ]);
    }

    public function create_password(Request $request){

        $patientId = $request->patientId;

        $patient = Patient::find($patientId);

        $user = AppUser::where('patient_id', $patientId)->first();
        if(!$user){
            $user = new AppUser();
            $user->patient_id = $patientId;
            $user->login = $patient->phone;
            $user->password = Hash::make($request->password);
            $user->save();

            $token = $user->createToken('MobileApp')->plainTextToken;

            // Відправка відповіді з токеном
            return response()->json([
                'message' => 'User created successfully!',
                'token' => $token,
                'user' => $user,
                'ok' => true,
            ], 201);

        }
    }
}
