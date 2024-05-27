<?php

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            // User not found
            return redirect()->back()->with('error', 'User not found.');
        }

        // Generate OTP and store it in the user's table
        $otp = Str::random(6);
        $user->update([
            'otp' => Hash::make($otp),
        ]);

        // Send OTP to the user's email
        Mail::raw("Your OTP: $otp", function ($message) use ($email) {
            $message->to($email)->subject('One-Time Password (OTP)');
        });

        return redirect()->back()->with('success', 'OTP sent successfully.');
    }
}
