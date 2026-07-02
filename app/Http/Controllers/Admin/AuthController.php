<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginAdminRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        // Si ya está logueado, lo mandamos al dashboard
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    public function login(LoginAdminRequest $request)
    {
        $credenciales = $request->only('email', 'password');

        if (Auth::attempt($credenciales, $request->filled('remember'))) {
            // Regenerar la sesión evita ataques de "Session Fixation"
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        // Si falla, regresamos con error genérico por seguridad (no revelar qué falló)
        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}