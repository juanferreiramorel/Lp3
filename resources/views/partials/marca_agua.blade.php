{{-- Test-environment watermark. Shown on every environment that is not
     production, across the sign-in screen and the whole app, so nobody
     invoices for real in a test copy, or tests in the real one believing it
     is a copy. Production sets APP_ENV=production in compose.prod.yaml.

     A real element and not a ::after on body: generated content can be read
     aloud by some screen readers, and aria-hidden can only sit on an element.
     It never takes a click. --}}
@unless (app()->isProduction())
    <div class="marca-agua" aria-hidden="true">
        <span>AMBIENTE DE PRUEBAS · {{ mb_strtoupper(app()->environment()) }}</span>
    </div>
@endunless
