<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('layouts.welcome');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
        $attempt = [
            $loginField => $credentials['login'],
            'password' => $credentials['password'],
        ];

        if (Auth::attempt($attempt, false)) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($user->role !== 'admin') {
                Auth::logout();

                return back()->withErrors([
                    'login' => 'You are not authorized to access admin panel.',
                ])->onlyInput('login');
            }

            return redirect()->intended('/admin/dashboard');
        }

        return back()->withErrors([
            'login' => 'Invalid credentials',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
