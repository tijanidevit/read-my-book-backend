<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ResponseTrait;
    public function register(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create($data);
        Auth::login($user);

        $token = $user->createToken('readmybook')->plainTextToken;

        return $this->successResponse(data:compact('user', 'token'));
    }

    public function login(Request $request) {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (! Auth::attempt($data)) {
            return $this->invalidInputResponse('Invalid credentials');
        }

        $user = auth()->user();
        $token = $user->createToken('readmybook')->plainTextToken;

        return $this->successResponse(data:compact('user', 'token'));
    }
}
