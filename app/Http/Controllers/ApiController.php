<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Patient;
use Illuminate\Http\Request;
use App\Models\Api;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessTokenResult;

class ApiController extends Controller
{
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
                return response()->json($patient);
            }
            else{
                return response()->json('Користувач існує. Авторизуйтеся.');
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
