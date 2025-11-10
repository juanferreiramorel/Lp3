<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function login(Request $request)
    {
        // Validar entradas y generar mensajes claros para la vista
        $credentials = $request->validate(
            [
                'email'    => ['required', 'string'],
                'password' => ['required', 'string'],
            ],
            [
                'email.required'    => 'El nombre de usuario es obligatorio.',
                'password.required' => 'La contrasena es obligatoria.',
            ]
        );

        // Bloquear inicio de sesion si el usuario esta inactivo
        $inactiveUser = DB::selectOne(
            'SELECT * FROM users WHERE email = ? AND cast(estado as integer) = 0',
            [$credentials['email']]
        );

        if (!empty($inactiveUser)) {
            return back()
                ->withErrors(['email' => 'El usuario se encuentra inactivo.'])
                ->withInput($request->except('password'));
        }

        // Intentar autenticacion y regenerar la sesion por seguridad
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended($this->redirectTo);
        }

        return back()
            ->withErrors([
                'email' => 'El nombre de usuario y/o la contrasena no son correctos. Por favor, intentalo de nuevo.',
            ])
            ->withInput($request->except('password'));
    }

    public function logout(Request $request)
    {
        // matar sesion
        Auth::logout();

        return redirect('/');
    }
}

