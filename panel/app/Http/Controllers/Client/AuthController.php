<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('client.dashboard');
        }

        return view('client.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();

            if (Auth::guard('web')->user()->role === 'admin') {
                Auth::guard('web')->logout();
                return back()->withErrors(['email' => 'Usa el panel de administrador para iniciar sesión.']);
            }

            return redirect()->route('client.dashboard');
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas.']);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        return redirect()->route('client.login');
    }
}
