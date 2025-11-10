<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - {{ config('app.name') }}</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #3c8dbc 0%, #367fa9 100%);
            position: relative;
            overflow-x: hidden;
            padding: 20px 0;
        }

        /* Animated background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .bg-animation span {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            animation: animate 25s linear infinite;
            bottom: -150px;
        }

        .bg-animation span:nth-child(1) {
            left: 25%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }

        .bg-animation span:nth-child(2) {
            left: 10%;
            width: 20px;
            height: 20px;
            animation-delay: 2s;
            animation-duration: 12s;
        }

        .bg-animation span:nth-child(3) {
            left: 70%;
            width: 20px;
            height: 20px;
            animation-delay: 4s;
        }

        .bg-animation span:nth-child(4) {
            left: 40%;
            width: 60px;
            height: 60px;
            animation-delay: 0s;
            animation-duration: 18s;
        }

        .bg-animation span:nth-child(5) {
            left: 65%;
            width: 20px;
            height: 20px;
            animation-delay: 0s;
        }

        .bg-animation span:nth-child(6) {
            left: 75%;
            width: 110px;
            height: 110px;
            animation-delay: 3s;
        }

        .bg-animation span:nth-child(7) {
            left: 35%;
            width: 150px;
            height: 150px;
            animation-delay: 7s;
        }

        .bg-animation span:nth-child(8) {
            left: 50%;
            width: 25px;
            height: 25px;
            animation-delay: 15s;
            animation-duration: 45s;
        }

        .bg-animation span:nth-child(9) {
            left: 20%;
            width: 15px;
            height: 15px;
            animation-delay: 2s;
            animation-duration: 35s;
        }

        .bg-animation span:nth-child(10) {
            left: 85%;
            width: 150px;
            height: 150px;
            animation-delay: 0s;
            animation-duration: 11s;
        }

        @keyframes animate {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 0;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }

        /* Particles layer */
        #particles-js {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: auto;
        }

        /* Register container */
        .register-container {
            position: relative;
            z-index: 10;
            width: 500px;
            max-width: 90%;
            animation: slideInDown 0.8s ease-out;
            pointer-events: auto;
        }

        /* Asegurar que todos los elementos interactivos funcionen */
        .register-card,
        .register-card * {
            pointer-events: auto;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Logo section */
        .register-logo {
            text-align: center;
            margin-bottom: 25px;
            animation: fadeIn 1s ease-in;
        }

        .register-logo i {
            font-size: 55px;
            color: #fff;
            margin-bottom: 12px;
            display: block;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .register-logo h1 {
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            text-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
            margin: 0;
        }

        .register-logo p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 15px;
            margin-top: 6px;
        }

        /* Card */
        .register-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Alert messages */
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.5s ease-out;
            font-size: 13px;
            font-weight: 500;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert i {
            font-size: 18px;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #fff;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        /* Form groups */
        .form-group {
            margin-bottom: 20px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
            z-index: 1;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 13px 18px 13px 48px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
        }

        .form-control:focus + .input-icon {
            color: #fff;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-control.is-invalid {
            border-color: rgba(239, 68, 68, 0.6);
            background: rgba(239, 68, 68, 0.1);
        }

        .invalid-feedback {
            display: block;
            color: #fca5a5;
            font-size: 12px;
            margin-top: 6px;
            margin-left: 5px;
            font-weight: 500;
        }

        /* Button */
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3c8dbc 0%, #367fa9 100%);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 0 4px 15px rgba(60, 141, 188, 0.4);
            margin-top: 10px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(60, 141, 188, 0.6);
            background: linear-gradient(135deg, #367fa9 0%, #3c8dbc 100%);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        /* Links */
        .register-footer {
            text-align: center;
            margin-top: 20px;
        }

        .register-footer a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 13px;
        }

        .register-footer a:hover {
            color: #5dade2;
            text-shadow: 0 0 10px rgba(93, 173, 226, 0.5);
        }

        /* Autocomplete fix */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 30px rgba(255, 255, 255, 0.1) inset !important;
            -webkit-text-fill-color: #fff !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Responsive */
        @media (max-width: 500px) {
            .register-card {
                padding: 28px 22px;
            }

            .register-logo h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated background -->
    <div class="bg-animation">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- Particles -->
    <div id="particles-js"></div>

    <!-- Register container -->
    <div class="register-container">
        <!-- Logo -->
        <div class="register-logo">
            <i class="fas fa-user-plus"></i>
            <h1>{{ config('app.name') }}</h1>
            <p>Crea tu cuenta</p>
        </div>

        <!-- Card -->
        <div class="register-card">
            <!-- Validation errors -->
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Register form -->
            <form method="POST" action="{{ route('register') }}" novalidate>
                @csrf

                <!-- Nombre -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Nombre completo"
                            class="form-control @error('name') is-invalid @enderror"
                            autofocus
                            autocomplete="name"
                            required
                        >
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Nombre de Usuario -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <input
                            type="text"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Nombre de usuario"
                            class="form-control @error('email') is-invalid @enderror"
                            required
                        >
                        <i class="fas fa-user-circle input-icon"></i>
                    </div>
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <input
                            type="password"
                            name="password"
                            placeholder="Contraseña"
                            class="form-control @error('password') is-invalid @enderror"
                            autocomplete="new-password"
                            required
                        >
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirmar Password -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <input
                            type="password"
                            name="password_confirmation"
                            placeholder="Confirmar contraseña"
                            class="form-control @error('password_confirmation') is-invalid @enderror"
                            autocomplete="new-password"
                            required
                        >
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    @error('password_confirmation')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Submit button -->
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Crear Cuenta
                </button>
            </form>

            <!-- Footer -->
            <div class="register-footer">
                <a href="{{ route('login') }}">
                    <i class="fas fa-arrow-left"></i> ¿Ya tienes cuenta? Inicia sesión
                </a>
            </div>
        </div>
    </div>

    <!-- Particles.js -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        particlesJS('particles-js', {
            particles: {
                number: {
                    value: 100,
                    density: { enable: true, value_area: 800 }
                },
                color: { value: '#ffffff' },
                shape: {
                    type: ['circle', 'triangle'],
                    stroke: { width: 0, color: '#000000' }
                },
                opacity: {
                    value: 0.4,
                    random: true,
                    anim: { enable: true, speed: 1, opacity_min: 0.1, sync: false }
                },
                size: {
                    value: 4,
                    random: true,
                    anim: { enable: true, speed: 2, size_min: 0.3, sync: false }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#ffffff',
                    opacity: 0.3,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 2.5,
                    direction: 'none',
                    random: true,
                    straight: false,
                    out_mode: 'out',
                    bounce: false,
                    attract: { enable: true, rotateX: 600, rotateY: 1200 }
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: {
                        enable: true,
                        mode: ['grab', 'bubble']
                    },
                    onclick: {
                        enable: true,
                        mode: 'push'
                    },
                    resize: true
                },
                modes: {
                    grab: {
                        distance: 200,
                        line_linked: { opacity: 0.8 }
                    },
                    bubble: {
                        distance: 250,
                        size: 8,
                        duration: 2,
                        opacity: 0.8,
                        speed: 3
                    },
                    push: {
                        particles_nb: 6
                    },
                    remove: {
                        particles_nb: 2
                    }
                }
            },
            retina_detect: true
        });
    </script>
</body>
</html>
