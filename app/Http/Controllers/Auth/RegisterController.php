<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function showForm(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request): mixed
    {
        $validated = $request->validate([
            'nickname' => ['required', 'string', 'min:2', 'max:20', 'unique:users', 'regex:/^[가-힣a-zA-Z0-9_]+$/u'],
            'email'    => ['required', 'email:rfc,dns', 'unique:users'],
            'password' => ['required', 'min:8', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
        ]);

        $user = User::create([
            'nickname' => $validated['nickname'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status'   => UserStatus::Pending,
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
