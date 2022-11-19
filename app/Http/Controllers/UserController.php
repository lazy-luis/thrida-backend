<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function getAllUsers()
    {
        return User::all();
    }

    public function getUserDetails(StoreAdminRequest $request, $id)
    {
        return User::where('id', $id)->get();
    }

    public function updateUserWallet(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|string',
            'wallet_type' => 'required|image',
        ]);

        $user = User::find($id);
        $user_wallets = json_decode($user->wallets);
        if ($request->wallet_type === 'Demo') {
            $user_wallets->demo_wallet = floatval($user_wallets->demo_wallet) + floatval($request->amount);
        } elseif ($request->wallet_type === 'Live') {
            $user_wallets->real_wallet = floatval($user_wallets->real_wallet) + floatval($request->amount);
        } else {
            return response()->json([
                'message' => 'Invalid wallet type',
            ], 400);
        }
        $user->wallets = json_encode($user_wallets);
        $user->save();

        return response()->json([
            'message' => 'User wallet updated successfully',
            'user' => $user
        ], 200);
    }

    public function updateUserDetails(Request $request, $id)
    {

        $user = User::find($id);
        $user->update($request->all());

        return response()->json([
            'message' => 'User details updated successfully',
            'user' => $user
        ], 200);
    }

    public function deleteUser($id)
    {
        $user = User::find($id);
        $user->update([
            'is_deleted' => true,
        ]);
        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    public function freezeUser($id)
    {
        $user = User::find($id);
        $user->update([
            'is_frozen' => true,
        ]);
        return response()->json([
            'message' => 'User frozen successfully'
        ]);
    }

    //User Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials))
            return response()->json([
                'message' => 'User does not exist'
            ], 401);
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->accessToken;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(3);
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->accessToken->expires_at
            )->toDateTimeString(),
            'user' => $user
        ]);
    }

    //User Sign Up
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string',
        ]);

        $newWallet = json_encode([
            'demo_wallet' => 350000,
            'real_wallet' => 0,
            'tournament_wallet' => 0,
        ]);

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'wallets' => $newWallet,
            'password' => bcrypt($request->password),
        ]);

        $user->save();
        return response()->json([
            'message' => 'Successfully created user!'
        ], 201);
    }

    public function getUserInfo(Request $request)
    {
        return response()->json($request->user());
    }
}
