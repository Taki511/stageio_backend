<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send password reset link to user.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate reset token
        $token = Str::random(64);
        
        // Store token in database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Send notification email
        $this->notificationService->notifyPasswordReset($user, $token);

        return response()->json([
            'message' => 'Password reset link has been sent to your email.',
        ]);
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'message' => 'Invalid reset token.',
            ], 400);
        }

        // Check if token is valid (expires after 60 minutes)
        $createdAt = $resetRecord->created_at instanceof Carbon 
            ? $resetRecord->created_at 
            : Carbon::parse($resetRecord->created_at);
        
        if (now()->diffInMinutes($createdAt) > 60) {
            return response()->json([
                'message' => 'Reset token has expired.',
            ], 400);
        }

        // Verify token
        if (!Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'message' => 'Invalid reset token.',
            ], 400);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }
        
        $user->update(['password' => Hash::make($request->password)]);

        // Delete reset token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ]);
    }
}
