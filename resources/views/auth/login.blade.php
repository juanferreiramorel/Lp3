@php
    // The torn edge of a thermal receipt. Fixed, not random: an edge that
    // changes on every load reads as an effect, one that stays reads as paper.
    $desgarro = 'M0 0 L0 7.2 L12.1 12.0 L22.5 14.6 L30.4 4.9 L37.0 4.7 L48.3 10.2 L59.8 3.2 L70.9 5.3 L81.1 6.9 L93.2 10.0 L103.7 16.7 L116.0 10.4 L123.3 10.1 L137.6 9.4 L146.4 3.1 L150.8 11.8 L159.5 4.9 L168.2 7.5 L177.7 9.0 L184.8 7.3 L192.5 10.4 L206.6 20.3 L214.2 7.1 L221.7 8.9 L232.3 6.5 L245.7 3.1 L250.5 7.9 L262.5 6.3 L267.8 6.6 L275.8 7.6 L290.2 9.0 L303.3 11.4 L315.5 7.7 L321.3 13.9 L332.9 8.2 L340.4 3.0 L351.3 6.7 L357.2 9.4 L367.6 19.3 L378.2 4.1 L391.5 11.3 L401.7 6.9 L409.0 4.9 L416.4 17.0 L426.8 4.5 L436.9 4.4 L451.9 6.2 L463.6 3.3 L469.0 7.4 L482.6 11.4 L496.3 8.1 L501.0 4.9 L514.9 4.0 L527.2 8.8 L540.6 6.8 L546.6 15.1 L551.7 3.5 L556.2 10.8 L565.6 6.8 L579.4 8.5 L588.5 3.6 L599.6 10.7 L611.5 9.8 L623.4 7.8 L637.2 8.2 L650.2 5.1 L656.1 11.4 L660.9 7.8 L669.9 4.4 L679.1 14.4 L692.2 6.5 L706.9 9.3 L718.3 9.7 L732.4 4.6 L747.1 18.6 L754.2 10.0 L765.9 17.3 L771.2 17.9 L776.7 10.9 L780.0 4.8 L780 0 Z';

    $version = fn (string $ruta) => asset($ruta) . '?v=' . (@filemtime(public_path($ruta)) ?: '1');

    $hayError = $errors->any();
    $mensajeError = $errors->first();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar · {{ config('app.name') }}</title>

    {{-- Esta pagina no extiende layouts/app, asi que trae por su cuenta el
         icono y el manifest. --}}
    @include('layouts.pwa')

    {{-- Marks the page as script-capable before first paint, so the sheet
         can start inside the printer. If login.js never arrives, the
         fallback below shows the sheet anyway: a login that stays hidden
         because a file failed to load is a locked door. --}}
    <script>
        document.documentElement.classList.add('js');
        setTimeout(function () {
            if (!window.__comprobanteListo) {
                document.documentElement.classList.remove('js');
            }
        }, 2500);
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap">

    {{-- theme.css is the single source of the product's tokens; login.css
         only lays out this screen with them. --}}
    <link rel="stylesheet" href="{{ $version('css/theme.css') }}">
    <link rel="stylesheet" href="{{ $version('css/login.css') }}">
</head>
<body class="pantalla-login">

<main class="mesa">

    {{-- ============================ LA IMPRESORA ============================ --}}
    <div class="impresora" aria-hidden="true">
        <span>{{ strtoupper(config('app.name')) }}</span>
        <span class="impresora-estado">
            <span class="led" id="led" data-estado="lista"></span>
            <span id="led-texto">LISTA</span>
        </span>
        <span class="ranura"></span>
    </div>

    <div class="boca">
        <div class="hoja" id="hoja" data-imprimir="{{ $hayError ? 'no' : 'si' }}">

            {{-- Top edge: inside the printer while the sheet hangs. It only
                 shows when the ticket is torn off on the way in. --}}
            <svg class="desgarro desgarro-arriba" id="borde-arriba" viewBox="0 0 780 22" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                <path transform="translate(0 22) scale(1 -1)" d="{{ $desgarro }}"/>
            </svg>

            <div class="papel">
                <svg class="grano" aria-hidden="true" focusable="false">
                    <filter id="grano"><feTurbulence type="fractalNoise" baseFrequency="0.85" numOctaves="3" stitchTiles="stitch"/></filter>
                    <rect width="100%" height="100%" filter="url(#grano)"/>
                </svg>

                {{-- The acknowledgement. It sits at the very top, against the
                     slot, because paper leaving a printer always has the
                     newest line closest to the mouth. It is filled in only
                     after the server accepts the credentials. --}}
                <div class="acuse dato" id="acuse" aria-hidden="true">
                    <span class="acuse-ok">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 6 9 17l-5-5"></path></svg>
                        ACCESO CONCEDIDO
                    </span>
                    <span class="acuse-detalle"><span id="acuse-usuario"></span> &nbsp;·&nbsp; <span id="acuse-hora"></span></span>
                </div>

                <div class="contenido">

                    <header class="cabecera">
                        <div class="marca">
                            <svg class="marca-icono" viewBox="0 0 32 32" role="img" aria-label="{{ config('app.name') }}" focusable="false">
                                <rect width="32" height="32" rx="8" fill="currentColor"/>
                                <path d="M9 7h14v15l-2.333 2-2.333-2-2.334 2-2.333-2-2.334 2L9 22z" fill="var(--paper-raised)"/>
                                <rect x="12" y="11" width="8" height="1.7" rx="0.85" fill="currentColor"/>
                                <rect x="12" y="14.8" width="5.4" height="1.7" rx="0.85" fill="currentColor"/>
                            </svg>
                            <span class="marca-texto"><span class="marca-tap">TAP</span> <span class="marca-erp">ERP</span></span>
                        </div>

                        @if ($comprobante['ruc'] || $comprobante['timbrado'])
                            <dl class="datos-fiscales dato">
                                @if ($comprobante['ruc'])
                                    <dt>RUC</dt><dd>{{ $comprobante['ruc'] }}</dd>
                                @endif
                                @if ($comprobante['timbrado'])
                                    <dt>TIMBRADO</dt><dd>{{ $comprobante['timbrado']['numero'] }}</dd>
                                    <dt>EXPEDICIÓN</dt><dd>{{ $comprobante['timbrado']['expedicion'] }}</dd>
                                    <dt>VIGENCIA</dt><dd>{{ $comprobante['timbrado']['vigencia'] }}</dd>
                                @endif
                            </dl>
                        @endif
                    </header>

                    <div class="raya-pesada" aria-hidden="true"></div>

                    <h1 class="titulo">Entrá a tu caja</h1>
                    <p class="subtitulo dato">
                        @if ($comprobante['empresa'])
                            {{ mb_strtoupper($comprobante['empresa']) }} &nbsp;·&nbsp;
                        @endif
                        {{ $comprobante['fecha'] }} &nbsp;<span id="reloj">{{ $comprobante['hora'] }}</span>
                    </p>

                    @if (session('flash_notification'))
                        @foreach (session('flash_notification') as $aviso)
                            <p class="aviso aviso-info" role="status">{{ $aviso['message'] }}</p>
                        @endforeach
                    @endif

                    <form method="POST" action="{{ url('/login') }}" id="form-login" novalidate>
                        @csrf

                        <div class="renglon">
                            <label class="renglon-etiqueta" for="usuario">Usuario</label>
                            <span class="renglon-campo">
                                <input id="usuario" name="email" type="text"
                                       value="{{ old('email') }}"
                                       autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false"
                                       aria-describedby="login-error"
                                       @if ($hayError) aria-invalid="true" @endif
                                       @unless ($hayError) autofocus @endunless>
                            </span>
                        </div>

                        <div class="renglon">
                            <label class="renglon-etiqueta" for="clave">Contraseña</label>
                            <span class="renglon-campo">
                                <input id="clave" name="password" type="password"
                                       autocomplete="current-password"
                                       aria-describedby="login-error"
                                       @if ($hayError) aria-invalid="true" autofocus @endif>
                                <button type="button" class="ojo" id="ojo" aria-label="Mostrar contraseña" aria-pressed="false">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </span>
                        </div>

                        <p class="aviso aviso-error" id="login-error" role="alert" @unless ($hayError) hidden @endunless>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v6"></path><path d="M12 16.5v.5"></path></svg>
                            <span id="login-error-texto">{{ $mensajeError }}</span>
                        </p>

                        <div class="renglon-total">
                            <span class="renglon-etiqueta renglon-etiqueta-fuerte">Acceso</span>
                            <span class="guia" aria-hidden="true"></span>
                            <button type="submit" class="entrar" id="entrar">
                                <span id="entrar-texto">Entrar al sistema</span>
                                <span id="entrar-espera" hidden>Verificando…</span>
                            </button>
                        </div>

                        <p class="solo-lector" role="status" id="estado"></p>
                    </form>

                    <footer class="colofon dato">
                        @if ($comprobante['aviso'])
                            <p class="colofon-aviso">{{ $comprobante['aviso'] }}</p>
                        @endif
                        <span class="colofon-linea">
                            <span>{{ config('app.name') }}</span>
                            <span>{{ request()->getHost() }}</span>
                            <span>¿Olvidaste la contraseña? Pedile a sistemas que la restablezca.</span>
                        </span>
                    </footer>

                </div>
            </div>

            {{-- Bottom edge: the cut of the previous ticket. --}}
            <svg class="desgarro" viewBox="0 0 780 22" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                <path d="{{ $desgarro }}"/>
            </svg>

        </div>
    </div>

</main>

<script src="{{ $version('js/login.js') }}"></script>
</body>
</html>
