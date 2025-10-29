<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    /**
     * POST /api/v1/login
     * body: { "identifier": "email_or_phone", "password": "..." } OR { "identifier": "...", "activation_code": "123456" }
     */
    public function login(Request $request)
    {
        $data = $request->only(['identifier', 'password', 'activation_code']);

        if (empty($data['identifier'])) {
            return response()->json(['message' => 'identifier is required'], 400);
        }

        $identifier = $data['identifier'];

        $user = User::where('email', $identifier)
            ->orWhere('telephone', $identifier)
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Activation code login (one-time)
        if (! empty($data['activation_code'])) {
            $code = (string) $data['activation_code'];
            if (empty($user->activation_code) || (string) $user->activation_code !== $code) {
                return response()->json(['message' => 'Invalid activation code'], 401);
            }

            // check expiry
            if (! empty($user->activation_expires_at) && now()->gt($user->activation_expires_at)) {
                return response()->json(['message' => 'Activation code expired'], 401);
            }

            // consume the activation code (expire it) and mark that the user must change password
            $user->activation_code = null;
            $user->activation_expires_at = null;
            $user->force_password_change = true;
            $user->save();

            // issue a personal access token
            $token = $user->createToken('login-token')->accessToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'must_change_password' => true,
            ], 200);
        }

        // Regular password login
        if (! empty($data['password'])) {
            if (! Hash::check($data['password'], $user->password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $token = $user->createToken('login-token')->accessToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'must_change_password' => (bool) ($user->force_password_change ?? false),
            ], 200);
        }

        return response()->json(['message' => 'password or activation_code is required'], 400);
    }
}
