<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Models\Otp;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        try {
            $user = User::firstOrCreate([
                'name' => $request->name,
                'phone_number' => $request->phone_number
            ]);

            $otp = rand(100000, 999999);

            $otp = Otp::create([
                'phone_number' => $user->phone_number,
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5)
            ]);

            // send otp to user via whatsapp

            $res = Http::post('localhost:8888/send-message', [
                'number' => $user->phone_number,
                'message' => "Ini adalah OTP anda $otp->otp"
            ]);

            if ($res->json()['status'] !== 'success') {
                return response()->json([
                    'message' => 'Failed to send OTP'
                ], 500);
            }

            return response()->json([
                'message' => 'OTP sent successfully',
                'otp_id' => $otp->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        try {

            if ($this->checkTooManyAttempts()) {
                return response()->json([
                    'message' => 'Too many attempts'
                ], 429);
            }

            $otp = Otp::findOrFail($request->otp_id);

            if ($otp->is_used || $otp->expires_at < now()) {
                RateLimiter::hit($this->throttleKey());
                return response()->json([
                    'message' => 'OTP is no longer valid'
                ], 400);
            }

            if ($otp->otp !== $request->otp) {
                RateLimiter::hit($this->throttleKey());
                return response()->json([
                    'message' => 'OTP is invalid'
                ], 400);
            }

            $otp->update([
                'is_used' => true
            ]);

            RateLimiter::clear($this->throttleKey());

            return response()->json([
                'message' => 'OTP verified successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function throttleKey()
    {
        return request('otp_id') . '|' . request()->ip();
    }

    public function checkTooManyAttempts()
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), $perMinute = 3)) {
            return true;
        }

        return false;
    }
}
