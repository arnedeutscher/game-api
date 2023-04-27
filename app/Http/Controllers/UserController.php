<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Auth;

class UserController extends Controller
{
    /**
     * Login Client
	 * 
	 * @param App\Http\Requests\Auth\LoginRequest
	 * 
	 * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {

        $input = $request->all();

		Auth::attempt($input);

		$user = Auth::user();

		$token = $user->createToken($user['id'])->accessToken;
		
		return response()->json(['message' => 'Login sucessful.', 'token' => $token], 200);
    }

    /**
     * Testing if authentication works, by getting user details.
	 * 
	 * @return \Illuminate\Http\JsonResponse
     */
    public function user_details(): JsonResponse
    {
        $user = Auth::guard('api')->user();

		if (!Auth::guard('api')->check()) {
			return response()->json(['message' => 'Unauthorized.'], 401);
		}
		
		return response()->json(['user' => $user], 200);
    }
}
