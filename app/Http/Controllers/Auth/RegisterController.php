<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
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
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $messages = [
            'name.required'                  => 'El nombre es obligatorio.',
            'email.required'                 => 'El nombre de usuario es obligatorio.',
            'email.alpha_dash'               => 'El nombre de usuario solo puede contener letras, numeros, guiones y guiones bajos.',
            'email.min'                      => 'El nombre de usuario debe tener al menos :min caracteres.',
            'email.max'                      => 'El nombre de usuario no puede tener mas de :max caracteres.',
            'email.unique'                   => 'El nombre de usuario ya esta registrado.',
            'password.required'              => 'La contrasena es obligatoria.',
            'password.min'                   => 'La contrasena debe tener al menos :min caracteres.',
            'password.confirmed'             => 'Las contrasenas no coinciden.',
            'password_confirmation.required' => 'Debe confirmar la contrasena.',
        ];

        $attributes = [
            'name'                  => 'nombre',
            'password'              => 'contrasena',
            'password_confirmation' => 'confirmacion de contrasena',
        ];

        return Validator::make(
            $data,
            [
                'name'                  => ['required', 'string', 'max:255'],
                'email'              => ['required', 'string', 'alpha_dash', 'min:3', 'max:32', 'unique:users,email'],
                'password'              => ['required', 'string', 'min:8', 'confirmed'],
                'password_confirmation' => ['required'],
            ],
            $messages,
            $attributes
        );
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}
