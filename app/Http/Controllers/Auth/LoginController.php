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
                'password.required' => 'La contraseña es obligatoria.',
            ]
        );

        // Bloquear inicio de sesion si el usuario esta inactivo
        $inactiveUser = DB::selectOne(
            'SELECT * FROM users WHERE email = ? AND cast(estado as integer) = 0',
            [$credentials['email']]
        );

        if (!empty($inactiveUser)) {
            return $this->rechazar($request, 'El usuario se encuentra inactivo.');
        }

        // Intentar autenticacion y regenerar la sesion por seguridad
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $destino = redirect()->intended($this->redirectTo)->getTargetUrl();

            // The sign-in screen posts with fetch so it can print the
            // receipt and tear it off before leaving. It needs to know
            // where to go, not to be redirected there behind its back.
            if ($request->expectsJson()) {
                return response()->json(['redirect' => $destino]);
            }

            return redirect($destino);
        }

        return $this->rechazar(
            $request,
            'El usuario o la contraseña no son correctos.'
        );
    }

    /**
     * One shape for every refusal, for both the fetch flow and the plain
     * form post that still works without JavaScript.
     */
    private function rechazar(Request $request, string $mensaje)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $mensaje,
                'errors'  => ['email' => [$mensaje]],
            ], 422);
        }

        return back()
            ->withErrors(['email' => $mensaje])
            ->withInput($request->except('password'));
    }

    public function logout(Request $request)
    {
        // matar sesion
        Auth::logout();

        return redirect('/');
    }
}

