<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendPasswordResetOtpJob;
use App\Models\User;
use App\Services\WebpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function sample()
    {
        $samples = \App\Models\sapmle::all();
        return response()->json([
            'success' => true,
            'data'    => $samples,
        ]);
    }

    // User Registration
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone'    => 'required|digits:10|unique:users,phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $user,
        ]);
    }
    // User Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login'    => 'required|string', // email OR phone
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'phone';

        // Fetch user manually
        $user = User::where($loginField, $request->login)->first();

        // User not found
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

                                     // ❌ Block non-admins explicitly
        if ($user->role != 'user') { // 2 = admin
            return response()->json([
                'success' => false,
                'message' => 'Access denied. User only.',
            ], 403);
        }

        // Password check
        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Generate JWT token manually
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success'    => true,
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => $user,
        ]);
    }

    public function admin_register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone'    => 'required|digits:10|unique:users,phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'role'     => 'admin',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    public function admin_login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'username' => 'required|string', // email OR phone
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'phone';

        // Fetch user manually
        $user = User::where($loginField, $request->username)->first();

        // User not found
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->role != 'admin') { // 2 = admin
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admins only.',
            ], 403);
        }

        // Password check
        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Generate JWT token manually
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success'    => true,
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => $user,

        ]);
    }

    public function sendOtp(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required | email | exists: users, email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $otp = rand(100000, 999999);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['otp' => $otp, 'created_at' => now()]
        );

        dispatch(new SendPasswordResetOtpJob($request->email, $otp));

        return response()->json([
            'success' => true,
            'message' => 'OTP senttoyouremail',
        ]);
    }

    public function verifyOtp(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required | email',
            'otp'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified',
        ]);
    }

    public function resetPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email'    => 'required | email',
            'otp'      => 'required',
            'password' => 'required | min: 6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password resetsuccessfully',
        ]);
    }

    public function profile()
    {
        return response()->json([
            'success' => true,
            'user'    => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name'     => 'required | string | max: 255',
            // 'email'    => 'required | email | unique: users, email, ' . $user->id,
            // 'phone'    => 'nullable | digits: 10 | unique: users, phone, ' . $user->id,'email' => 'required|email|unique:users,email,' . $user->id,

            'email'    => 'required|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|digits:10|unique:users,phone,' . $user->id,

            'password' => 'nullable | min: 6',
            'avatar'   => 'nullable | image | max: 4048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        // ================= BASIC INFO =================
        $user->name  = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        // ================= PASSWORD =================
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // ================= AVATAR =================
        // if ($request->hasFile('avatar')) {

        //     if ($user->avatar && Storage::disk('')->exists($user->avatar)) {
        //         Storage::disk('')->delete($user->avatar);
        //     }

        //     $file = $request->file('avatar');
        //     // generate filename
        //     $fileName = Str::uuid() . '.';

        //     // final path
        //     $relativePath = '/' . $fileName;
        //     $absolutePath = storage_path('app/public/avatars/' . $fileName);

        //     WebpService::convert(
        //         $file->getRealPath(),
        //         $absolutePath,
        //         75
        //     );

        //     $user->avatar = $relativePath;
        // }

        if ($request->hasFile('avatar')) {

            // delete old avatar
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $file = $request->file('avatar');

            // ✅ filename WITH .webp
            $fileName = Str::uuid() . '.webp';

            // ✅ paths
            $relativePath = 'avatars/' . $fileName;
            $absolutePath = storage_path('app/public/' . $relativePath);

            // convert to WEBP
            WebpService::convert(
                $file->getRealPath(),
                $absolutePath,
                75
            );

            // save in DB
            $user->avatar = $relativePath;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user'    => $user,
        ]);
    }

    public function logout()
    {
        Auth::logout();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

}
