<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function changePassword(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $rules = [
            'new_password' => 'required|string|min:8|confirmed',
        ];

        if (empty($user->force_password_change)) {
            $rules['current_password'] = 'required|string';
        }

        $validated = $request->validate($rules);

        if (empty($user->force_password_change)) {
            if (! Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'Current password is invalid'], 422);
            }
        }

        $user->password = Hash::make($validated['new_password']);
        $user->force_password_change = false;
        $user->save();

        return response()->json(['message' => 'Password changed successfully'], 200);
    }
}
