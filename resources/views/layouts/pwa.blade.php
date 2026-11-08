{{-- PWA head tags and service worker registration.
     Included once from layouts/app.blade.php inside <head>. --}}

<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

{{-- Matches --stamp in public/css/theme.css, and the manifest's theme_color. --}}
<meta name="theme-color" content="#0284c7">

{{-- iOS does not read the manifest: these three give it the standalone shell. --}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="TAP ERP">
<link rel="apple-touch-icon" href="{{ asset('img/pwa/apple-touch-icon.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('img/pwa/icon-192.png') }}">

<script>
    // Register the service worker after load so it never competes with the
    // first paint. Registration failing must stay silent: the app works
    // without it, and a throw here would break unrelated inline scripts.
    (function () {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        window.addEventListener('load', function () {
            try {
                navigator.serviceWorker
                    .register('{{ asset('sw.js') }}', { scope: '/' })
                    .catch(function (error) {
                        console.warn('Service worker registration failed:', error);
                    });
            } catch (error) {
                console.warn('Service worker registration failed:', error);
            }
        });
    })();
</script>
