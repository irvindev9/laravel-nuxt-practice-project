<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateInfoRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    public function register(RegisterRequest $request){

        $user = User::create($request->only(
            "first_name",
            "last_name",
            "email",
        ) + [
            "password" => \Hash::make($request->input("password")),
            "is_admin" => $request->path() === "api/admin/register" ? 1 : 0,
        ]);

        return response($user, Response::HTTP_CREATED);
    }

    public function login(Request $request){
        if (!\Auth::attempt($request->only("email", "password"))) {
            return response([
                "error" => "invalid credentials"
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = \Auth::user();

        $adminLogin = $request->path() === "api/admin/login";

        if ($adminLogin && !$user->is_admin) {
            return response([
                "error" => "Access Denied!"
            ], Response::HTTP_UNAUTHORIZED);
        }

        $scopes = $adminLogin ? "admin" : "ambassador";

        $jwt = $user->createToken("token", [$scopes])->plainTextToken;

        $cookie = cookie("jwt", $jwt, 60*24); // 1 Day

        return response([
            "message" => $jwt
        ])->withCookie($cookie);
    }

    public function user(Request $request){
        return new UserResource($request->user());
    }

    public function logout(){
        $cookie = \Cookie::forget("jwt");

        return response([
            "message" => "success"
        ])->withCookie($cookie);
    }

    public function updateInfo(UpdateInfoRequest $request){
        $user = $request->user();

        $user->update($request->only("first_name", "last_name", "email"));

        return response($user, Response::HTTP_ACCEPTED);
    }

    public function updatePassword(UpdatePasswordRequest $request){
        $user = $request->user();

        $user->update([
            "password" => \Hash::make($request->password)
        ]);

        return response($user, Response::HTTP_ACCEPTED);
    }
}
