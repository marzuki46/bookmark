<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PasswordChangeRequest;
use Illuminate\Http\Request;

final class PasswordChangeController extends Controller
{
    public function approve(Request $request, string $token)
    {
        $changeRequest = PasswordChangeRequest::where('token', $token)->first();

        if (! $changeRequest) {
            return view('auth.password-change-result', [
                'success' => false,
                'message' => 'Invalid confirmation link.',
            ]);
        }

        if ($changeRequest->isExpired()) {
            return view('auth.password-change-result', [
                'success' => false,
                'message' => 'This confirmation link has expired. Please request a new one.',
            ]);
        }

        if ($changeRequest->approved_at !== null) {
            return view('auth.password-change-result', [
                'success' => false,
                'message' => 'This request has already been processed.',
            ]);
        }

        $user = $changeRequest->user;
        $changeRequest->update(['approved_at' => now()]);

        switch ($changeRequest->type) {
            case 'password':
                $user->update(['password' => $changeRequest->new_value_hash]);
                break;

            case 'pin':
                $user->update(['pin_hash' => $changeRequest->new_value_hash]);
                break;

            case 'email':
                $user->update(['email' => $changeRequest->new_value_plain]);
                break;
        }

        return view('auth.password-change-result', [
            'success' => true,
            'message' => ucfirst($changeRequest->type).' has been updated successfully.',
        ]);
    }

    public function reject(Request $request, string $token)
    {
        $changeRequest = PasswordChangeRequest::where('token', $token)->first();

        if (! $changeRequest) {
            return view('auth.password-change-result', [
                'success' => false,
                'message' => 'Invalid link.',
            ]);
        }

        $changeRequest->delete();

        return view('auth.password-change-result', [
            'success' => true,
            'message' => 'Change request has been cancelled.',
        ]);
    }
}
