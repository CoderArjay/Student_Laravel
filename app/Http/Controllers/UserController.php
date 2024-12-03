<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //
    public function register(Request $request){

        $formField = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
        User::create($formField);
        return $request;
    }

    public function login(Request $request){
        $request->validate([
            'email'=>'required|email|exists:users', //users is a table name
            'password' => 'required'
        ]);
        $user = User::where('email', $request->email)->first();
        if(!$user || !Hash::check($request->password, $user->password)){
            return [
                'message'=>'Invalid credentials'
            ];
        }
        $token = $user->createToken($user->name);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function logout(Request $request){
        $request->user()->tokens()->delete();
        return[
            'message' => 'You are logged out'
        ];
       
    }
}
