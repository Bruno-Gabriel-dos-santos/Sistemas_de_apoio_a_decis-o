<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        try {
            \Log::info('Iniciando processo de logout');
            
            if (Auth::check()) {
                \Log::info('Usuário autenticado, realizando logout');
                Auth::logout();
            } else {
                \Log::warning('Tentativa de logout sem usuário autenticado');
            }

            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            \Log::info('Logout realizado com sucesso');
            
            return redirect('/')->with('success', 'Logout realizado com sucesso!');
        } catch (\Exception $e) {
            \Log::error('Erro durante o logout: ' . $e->getMessage());
            return redirect('/')->with('error', 'Ocorreu um erro durante o logout.');
        }
    }
} 