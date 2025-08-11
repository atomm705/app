<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'phone' => 'required|email|unique:users,email',
            'birthday' => 'required|min:8|confirmed',
        ]);

        dd($request->phone);
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('YourAppName')->plainTextToken;

        return response()->json(['token' => $token]);
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('YourAppName')->plainTextToken;

        return response()->json(['token' => $token]);
    }
}
