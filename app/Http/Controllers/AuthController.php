<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ApiResponse;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Str;

/**
 * @group Authentication
 */
class AuthController extends Controller
{
    /**
     * Đăng ký tài khoản
     * 
     * API này dùng để tạo user mới và gửi OTP xác thực email.
     * 
     * @bodyParam name string required Tên người dùng. Example: Nguyen Van A
     * @bodyParam email string required Email. Example: test@gmail.com
     * @bodyParam password string required Mật khẩu. Example: 123456
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "User registered successfully"
     * }
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => $validatedData['password'],
        ]);

        $otp = rand(100000, 999999);

        $user->update([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(5),
            'otp_attempts' => 0,
            'otp_last_sent_at' => now()
        ]);

        Mail::to($user->email)->send(new SendOtpMail($otp));


        return ApiResponse::success($user, 'User registered successfully', 201);
    }

    /**
     * Đăng nhập
     * 
     * @bodyParam email string required Email. Example: test@gmail.com
     * @bodyParam password string required Mật khẩu. Example: 123456
     * 
     * @response 200 {
     *   "access_token": "token_here",
     *   "token_type": "Bearer"
     * }
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();


        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'email_verified' => $user->hasVerifiedEmail()
        ], 'Login successful', 200);
    }
    /**
     * Đăng xuất
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "message": "Logged out successfully"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return ApiResponse::success(null, 'Logged out successfully', 200);
    }
    /**
     * Gửi email reset password
     * 
     * @bodyParam email string required Email. Example: test@gmail.com
     * 
     * @response 200 {
     *   "message": "Email reset đã được gửi"
     * }
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink([
            'email' => $request->email
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return ApiResponse::success(null, 'Email reset đã được gửi', 200);
        }

        return ApiResponse::error(null, 'Không thể gửi email', 400);
    }
    /**
     * Reset password
     * 
     * @bodyParam email string required Email
     * @bodyParam token string required Token từ email
     * @bodyParam password string required Password mới
     * @bodyParam password_confirmation string required Xác nhận password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json([
                'success' => true,
                'message' => 'Reset password thành công'
            ])
            : response()->json([
                'success' => false,
                'message' => __($status)
            ], 400);
    }
    /**
     * Gửi lại OTP
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "message": "Đã gửi OTP"
     * }
     */
    public function resendOtp(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email đã xác thực']);
        }

        // chống spam 60s
        if ($user->otp_last_sent_at && now()->diffInSeconds($user->otp_last_sent_at) < 60) {
            return response()->json([
                'message' => 'Đợi 60s rồi thử lại'
            ], 429);
        }

        $otp = rand(100000, 999999);

        $user->update([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(5),
            'otp_attempts' => 0,
            'otp_last_sent_at' => now()
        ]);

        Mail::to($user->email)->send(new SendOtpMail($otp));

        return response()->json([
            'message' => 'Đã gửi OTP'
        ]);
    }
    /**
     * Xác thực OTP
     * 
     * @authenticated
     * 
     * @bodyParam otp string required OTP gồm 6 số. Example: 123456
     * 
     * @response 200 {
     *   "message": "Xác thực thành công"
     * }
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6'
        ]);

        $user = $request->user();

        if (!$user->otp_expires_at) {
            return response()->json([
                'message' => 'OTP không tồn tại'
            ], 400);
        }

        if (now()->gt($user->otp_expires_at)) {
            return response()->json([
                'message' => 'OTP hết hạn'
            ], 400);
        }

        if (!Hash::check($request->otp, $user->otp_code)) {

            $user->increment('otp_attempts');

            if ($user->otp_attempts >= 5) {
                return response()->json([
                    'message' => 'Sai quá nhiều lần'
                ], 429);
            }

            return response()->json([
                'message' => 'OTP không đúng'
            ], 400);
        }

        $user->update([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
            'otp_last_sent_at' => null
        ]);
        $user->save();

        return response()->json([
            'message' => 'Xác thực thành công'
        ]);
    }
}
