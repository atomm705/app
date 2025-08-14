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

        //Log::info('Raw body', ['body' => $request->getContent()]);

        $dobs = explode(".", $request->dob);
        $dob = $dobs[2].'-'.$dobs[1].'-'.$dobs[0];
        $patient = Patient::where('last_name', $request->lastName)
            ->where('first_name', $request->firstName)
            ->where('middle_name', $request->middleName)
            ->where('phone', $request->phone)
            ->where('dob', $dob)
            ->first();

        if($patient->id){
            $user = AppUser::where('patient_id', $patient->id)->first();
            if(!$user){
                $lastCode = SmsVerification::where('phone', $request->phone)->first();
                if ($lastCode && $lastCode->last_sent_at && $lastCode->last_sent_at->gt(now()->subMinute())) {
                    $waitSeconds = now()->diffInSeconds($lastCode->last_sent_at->addMinute(), false);
                    return response()->json([
                        'ok' => false,
                        'message' => "Код уже відправлено. Можна повторно надіслати через {$waitSeconds} секунд."
                    ], 429);
                }

                $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

                SmsVerification::updateOrCreate(
                    ['phone' => $request->phone],
                    [
                        'code'         => $code,
                        'expires_at'   => now()->addMinutes(3),
                        'last_sent_at' => now(),
                    ]
                );

                $search = [
                    '(__DATE__)',
                    '(__TIME__)',
                ];
                $sms = [
                    'sender' => 'OK-Centre',
                    'destination' => $request->phone,
                    'text' => $code,
                ];
                try {
                    $client = $this->auth();
                    $sended = $client->SendSMS($sms);
                    $result = $sended->SendSMSResult->ResultArray[0];
                } catch (Exception $e) {
                    return response()->json([
                        'ok'      => false,
                        'message' => 'Не вдалося надіслати СМС. Спробуйте пізніше.',
                    ], 502);
                }
                return response()->json([
                    'ok'      => true,
                    'message' => 'Вам відправлено СМС з кодом. Введіть його у полі вище (дійсний 15 хв).',
                ]);
            }
            else{
                return response()->json([
                    'ok'      => false,
                    'message' => 'Користувач існує. Авторизуйтеся.',
                ], 502);
            }
        }
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
            ], 201);

        }
    }
}
