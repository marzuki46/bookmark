<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

final class SetupController extends Controller
{
    public function show()
    {
        if ($this->isSetupCompleted()) {
            return redirect()->route('dashboard');
        }

        return view('auth.setup');
    }

    public function store(Request $request)
    {
        if ($this->isSetupCompleted()) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => ['required', 'confirmed', Password::min(8)],
            'pin' => 'required|numeric|digits_between:4,6',
        ]);

        $user = User::firstOrCreate(
            ['email' => $request->email],
            [
                'name' => $request->name,
                'password' => $request->password,
                'pin_hash' => Hash::make($request->pin),
                'setup_completed' => true,
            ]
        );

        if (! $user->wasRecentlyCreated) {
            $user->update([
                'name' => $request->name,
                'password' => $request->password,
                'pin_hash' => Hash::make($request->pin),
                'setup_completed' => true,
            ]);
        }

        auth()->login($user);

        return redirect()->route('dashboard')->with('success', 'Setup completed! Welcome to Knowledge Hub.');
    }

    private function isSetupCompleted(): bool
    {
        $user = User::where('setup_completed', true)->first();

        return $user !== null;
    }
}
