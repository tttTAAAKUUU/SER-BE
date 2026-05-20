<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Jobs\SendOtpEmailJob;
use App\Services\Otp\OtpService;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService,
    ) {}

    public function send(SendOtpRequest $request): JsonResponse
    {
        $email = $request->email;

        // Check cooldown
        if (! $this->otpService->canResend($email)) {
            return response()->json([
                'error' => 'Please wait before requesting a new OTP',
                'retry_after' => $this->otpService->getCooldownRemaining($email),
            ], 429);
        }

        // Generate and store OTP
        $otp = $this->otpService->generateOtp($email);

        // Set cooldown
        $this->otpService->setCooldown($email);

        // Queue email job
        SendOtpEmailJob::dispatch($email, $otp);

        return response()->json(['message' => 'OTP sent successfully']);
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $email = $request->email;
        $otp = $request->otp;

        $isValid = $this->otpService->verifyOtp($email, $otp);

        return response()->json(['valid' => $isValid]);
    }
}