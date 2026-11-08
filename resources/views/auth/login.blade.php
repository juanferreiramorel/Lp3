<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>

    {{-- Esta pagina no extiende layouts/app, asi que trae por su cuenta el
         icono y el manifest. Sin esto la pestana del login queda sin icono
         mientras el resto del sistema si lo tiene. --}}
    @include('layouts.pwa')

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Dark Blue / Slate Gradient to match TAP ERP Header */
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            position: relative;
            overflow: hidden;
        }

        /* Canvas for Particle Network */
        #networkCanvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        /* Glass Card */
        .login-card {
            position: relative;
            z-index: 10;
            width: 420px;
            max-width: 90%;
            padding: 50px 40px;
            background: rgba(30, 41, 59, 0.7); /* Darker glass */
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.5);
            color: #fff;
            overflow: hidden;
            animation: cardEntrance 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Avatar / Logo */
        .avatar-container {
            width: 90px;
            height: 90px;
            margin: 0 auto 20px auto;
            background: linear-gradient(135deg, #06b6d4 0%, #22d3ee 100%); /* Cyan gradient */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 25px rgba(6, 182, 212, 0.3); /* Cyan shadow */
            transition: transform 0.3s;
        }

        .avatar-container:hover {
            transform: scale(1.05) rotate(5deg);
        }
        
        .avatar-container i {
            font-size: 40px;
            color: #fff;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .login-header h2 {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }

        .login-header p {
            text-align: center;
            font-size: 15px;
            margin-bottom: 35px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Inputs */
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            background: rgba(15, 23, 42, 0.6); /* Darker input bg */
            border: 1px solid rgba(255, 255, 255, 0.1);
            outline: none;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .input-group input:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: #22d3ee; /* Cyan border */
            box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.1); /* Cyan glow */
        }

        .input-group i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: rgba(255, 255, 255, 0.4);
            transition: 0.3s;
        }

        .input-group input:focus + i {
            color: #22d3ee; /* Cyan icon */
            transform: translateY(-50%) scale(1.1);
        }

        /* Button */
        .btn-submit {
            width: 100%;
            padding: 16px;
            border: none;
            outline: none;
            background: linear-gradient(135deg, #06b6d4 0%, #22d3ee 100%); /* Cyan gradient */
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(6, 182, 212, 0.3);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); /* Reverse gradient */
            transition: 0.5s;
            z-index: -1;
            opacity: 0;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(6, 182, 212, 0.4);
        }
        
        .btn-submit:hover::before {
            opacity: 1;
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Footer Links */
        .footer-links {
            margin-top: 30px;
            text-align: center;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s;
            border-bottom: 1px solid transparent;
        }

        .footer-links a:hover {
            color: #22d3ee;
            border-bottom-color: #22d3ee;
        }

        /* Alerts */
        .alert {
            background: rgba(254, 202, 202, 0.1);
            border: 1px solid rgba(254, 202, 202, 0.2);
            color: #fecaca;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        .is-invalid {
            border-color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.1) !important;
        }
        
        .invalid-feedback {
            color: #fecaca;
            font-size: 12px;
            margin-top: 5px;
            margin-left: 5px;
        }

    </style>
</head>
<body>

    <!-- Canvas Background -->
    <canvas id="networkCanvas"></canvas>

    <!-- Glass Card -->
    <div class="login-card">
        
        <div class="avatar-container">
            <i class="fas fa-user-lock"></i>
        </div>

        <div class="login-header">
            <h2>{{ config('app.name') }}</h2>
            <p>Acceso al Sistema</p>
        </div>

        <!-- System Messages -->
        @if(session('flash_notification'))
            @foreach(session('flash_notification') as $message)
                <div class="alert" style="background: rgba(34, 211, 238, 0.1); border-color: rgba(34, 211, 238, 0.2); color: #a5f3fc; animation: none;">
                    <i class="fas fa-info-circle"></i> {{ $message['message'] }}
                </div>
            @endforeach
        @endif

        @if($errors->any())
            <div class="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div style="flex:1">
                   @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                   @endforeach
                </div>
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ url('/login') }}">
            @csrf

            <!-- Username -->
            <div class="input-group">
                <input type="text" name="email" placeholder="Usuario / Correo" value="{{ old('email') }}" required autofocus>
                <i class="fas fa-user"></i>
            </div>

            <!-- Password -->
            <div class="input-group">
                <input type="password" name="password" placeholder="Contraseña" required class="@error('password') is-invalid @enderror">
                <i class="fas fa-lock"></i>
                @error('password')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn-submit">
                INGRESAR SISTEMA
            </button>

            <div class="footer-links">
                <a href="{{ route('register') }}">Crear cuenta</a>
            </div>
        </form>
    </div>

    <!-- Particle Network Script -->
    <script>
        const canvas = document.getElementById('networkCanvas');
        const ctx = canvas.getContext('2d');
        
        let width, height;
        let particles = [];
        
        // Configuration
        const particleCount = 60; // Number of dots
        const connectionDistance = 150; // Distance to connect
        const mouseDistance = 200; // Mouse interaction range
        
        // Mouse state
        const mouse = { x: null, y: null };

        // Resize handler
        function resize() {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
            initParticles();
        }

        // Particle Class
        class Particle {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.vx = (Math.random() - 0.5) * 1.5; // Velocity X
                this.vy = (Math.random() - 0.5) * 1.5; // Velocity Y
                this.size = Math.random() * 2 + 1;
                // Subtle Cyan/Teal particles
                this.color = `rgba(34, 211, 238, ${Math.random() * 0.4 + 0.1})`;
            }

            update() {
                // Move
                this.x += this.vx;
                this.y += this.vy;

                // Bounce off edges
                if (this.x < 0 || this.x > width) this.vx *= -1;
                if (this.y < 0 || this.y > height) this.vy *= -1;

                // Mouse Interaction
                if (mouse.x != null) {
                    let dx = mouse.x - this.x;
                    let dy = mouse.y - this.y;
                    let distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < mouseDistance) {
                        const forceDirectionX = dx / distance;
                        const forceDirectionY = dy / distance;
                        const force = (mouseDistance - distance) / mouseDistance;
                        const directionX = forceDirectionX * force * 3; // Push strength
                        const directionY = forceDirectionY * force * 3;
                        
                        // Optional: attract/repel logic could go here
                    }
                }
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.fill();
            }
        }

        function initParticles() {
            particles = [];
            for (let i = 0; i < particleCount; i++) {
                particles.push(new Particle());
            }
        }

        function animate() {
            ctx.clearRect(0, 0, width, height);

            // Update and draw particles
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
                particles[i].draw();

                // Draw connections
                for (let j = i; j < particles.length; j++) {
                    let dx = particles[i].x - particles[j].x;
                    let dy = particles[i].y - particles[j].y;
                    let distance = Math.sqrt(dx*dx + dy*dy);

                    if (distance < connectionDistance) {
                        ctx.beginPath();
                        // Cyan connections
                        ctx.strokeStyle = `rgba(34, 211, 238, ${0.2 * (1 - distance / connectionDistance)})`;
                        ctx.lineWidth = 1;
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.stroke();
                    }
                }

                // Draw connection to mouse
                if (mouse.x != null) {
                    let dx = particles[i].x - mouse.x;
                    let dy = particles[i].y - mouse.y;
                    let distance = Math.sqrt(dx*dx + dy*dy);

                    if (distance < mouseDistance) {
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(34, 211, 238, ${(1 - distance / mouseDistance) * 0.4})`;
                        ctx.lineWidth = 1.5;
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(mouse.x, mouse.y);
                        ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(animate);
        }

        // Event Listeners
        window.addEventListener('resize', resize);
        window.addEventListener('mousemove', (e) => {
            mouse.x = e.x;
            mouse.y = e.y;
        });
        window.addEventListener('mouseout', () => {
             mouse.x = undefined;
             mouse.y = undefined;
        });

        // Initialize
        resize();
        animate();
    </script>
</body>
</html>
