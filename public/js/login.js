/*
 * TAP ERP — sign-in screen.
 *
 * The sheet feeds out of the printer, and on a successful sign-in the
 * printer adds the acknowledgement and the ticket is torn off.
 *
 * Progressive enhancement throughout: without this file the form posts the
 * ordinary way and the server answers with a redirect. Any response this
 * script does not understand falls back to that same ordinary post, so the
 * worst case is the old behaviour, never a dead button.
 */
(function () {
    'use strict';

    const $ = (id) => document.getElementById(id);

    const hoja = $('hoja');
    const form = $('form-login');

    if (!hoja || !form || typeof hoja.animate !== 'function') {
        document.documentElement.classList.remove('js');
        return;
    }

    // If this file arrived after the head's safety timeout gave up on it,
    // take the page back: the sheet goes into the printer and feeds out.
    window.__comprobanteListo = true;
    document.documentElement.classList.add('js');

    const bordeArriba = $('borde-arriba');
    const acuse = $('acuse');
    const led = $('led');
    const ledTexto = $('led-texto');
    const boton = $('entrar');
    const txtEntrar = $('entrar-texto');
    const txtEspera = $('entrar-espera');
    const estado = $('estado');
    const usuario = $('usuario');
    const clave = $('clave');
    const ojo = $('ojo');
    const error = $('login-error');
    const errorTexto = $('login-error-texto');
    const reloj = $('reloj');

    const quieto = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let animacion = null;
    let ocupado = false;

    // How much of the sheet stays inside the printer at rest: the top tear
    // plus the acknowledgement that has not been printed yet.
    const adentro = () => -(bordeArriba.getBoundingClientRect().height
                            + acuse.getBoundingClientRect().height);

    const mover = (keyframes, opciones) => {
        if (animacion) { animacion.cancel(); }
        animacion = hoja.animate(keyframes, Object.assign({ fill: 'forwards' }, opciones));
        return animacion;
    };

    const ponerLed = (imprimiendo) => {
        led.dataset.estado = imprimiendo ? 'imprime' : 'lista';
        ledTexto.textContent = imprimiendo ? 'IMPRIMIENDO' : 'LISTA';
    };

    const esperar = (ms) => new Promise((listo) => setTimeout(listo, ms));

    const terminar = (anim) => new Promise((listo) => {
        anim.onfinish = listo;
        anim.oncancel = listo;
    });

    const enfocar = (campo) => {
        if (campo) { campo.focus({ preventScroll: true }); }
    };

    // ---- 1. The sheet feeds out of the printer -----------------------------
    // steps() and not a curve: a thermal head advances the paper line by
    // line. A smooth curve reads as a web animation; the jumps read as a
    // machine.
    const imprimir = async () => {
        const reposo = adentro();

        // After a plain post that came back with an error, the sheet is
        // already out: printing it again would only delay the correction.
        if (quieto || hoja.dataset.imprimir === 'no') {
            mover([{ transform: `translateY(${reposo}px)` }], { duration: 0 });
            return;
        }

        ponerLed(true);
        await terminar(mover(
            [{ transform: 'translateY(-100%)' }, { transform: `translateY(${reposo}px)` }],
            { duration: 1000, easing: 'steps(36, end)' }
        ));
        ponerLed(false);
    };

    // ---- 2. Errors ----------------------------------------------------------
    const mostrarError = (mensaje) => {
        errorTexto.textContent = mensaje;
        error.hidden = false;
        usuario.setAttribute('aria-invalid', 'true');
        clave.setAttribute('aria-invalid', 'true');
    };

    const limpiarError = () => {
        error.hidden = true;
        usuario.removeAttribute('aria-invalid');
        clave.removeAttribute('aria-invalid');
    };

    const esperando = (si) => {
        ocupado = si;
        boton.disabled = si;
        txtEntrar.hidden = si;
        txtEspera.hidden = !si;
        ponerLed(si);
    };

    // ---- 3. Granted: print the acknowledgement and tear the ticket off -----
    const concedido = async (destino) => {
        const ahora = new Date();
        const dos = (n) => String(n).padStart(2, '0');

        document.getElementById('acuse-usuario').textContent = usuario.value.trim().toUpperCase();
        document.getElementById('acuse-hora').textContent =
            `${dos(ahora.getHours())}:${dos(ahora.getMinutes())}:${dos(ahora.getSeconds())}`;
        acuse.removeAttribute('aria-hidden');
        estado.textContent = 'Acceso concedido. Entrando al sistema.';
        txtEspera.textContent = 'Entrando…';

        if (!quieto) {
            // The paper advances just enough to bring the acknowledgement
            // out of the slot.
            await terminar(mover(
                [{ transform: `translateY(${adentro()}px)` }, { transform: 'translateY(0px)' }],
                { duration: 480, easing: 'steps(14, end)' }
            ));
            ponerLed(false);

            // A beat to read it, then the pull: the sheet drops, turns a
            // little from the slot and goes. That is when the top tear shows,
            // the cut just made.
            await esperar(420);
            await terminar(mover(
                [
                    { transform: 'translateY(0px) rotate(0deg)', opacity: 1 },
                    { transform: 'translateY(26px) rotate(-1.2deg)', opacity: 1, offset: 0.35 },
                    { transform: 'translateY(150px) rotate(-2.4deg)', opacity: 0 },
                ],
                { duration: 520, easing: 'cubic-bezier(0.4, 0, 1, 1)' }
            ));
        } else {
            mover([{ transform: 'translateY(0px)' }], { duration: 0 });
            ponerLed(false);
            await esperar(400);
        }

        window.location.assign(destino);
    };

    // ---- 4. The submit -----------------------------------------------------
    const enviarComoSiempre = () => {
        // HTMLFormElement.submit() skips this listener, so this is the plain
        // post the server has always handled.
        form.submit();
    };

    form.addEventListener('submit', async (evento) => {
        if (typeof window.fetch !== 'function') { return; }

        evento.preventDefault();
        if (ocupado) { return; }

        limpiarError();
        esperando(true);

        let respuesta;
        try {
            respuesta = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
        } catch (fallo) {
            enviarComoSiempre();
            return;
        }

        let cuerpo = null;
        try { cuerpo = await respuesta.json(); } catch (fallo) { cuerpo = null; }

        if (respuesta.ok && cuerpo && typeof cuerpo.redirect === 'string') {
            await concedido(cuerpo.redirect);
            return;
        }

        if (respuesta.status === 422 && cuerpo) {
            const errores = cuerpo.errors || {};
            const primero = Object.values(errores)[0];
            esperando(false);
            mostrarError((Array.isArray(primero) && primero[0]) || cuerpo.message || 'No se pudo entrar.');

            // A wrong password is retyped, a missing user is typed: put the
            // caret where the next keystroke belongs.
            if (errores.email && !usuario.value.trim()) {
                enfocar(usuario);
            } else {
                clave.value = '';
                enfocar(clave);
            }
            return;
        }

        if (respuesta.status === 419) {
            // The screen sat open long enough for its CSRF token to expire,
            // which on a counter PC left on overnight is the normal case.
            esperando(false);
            mostrarError('Esta pantalla estuvo abierta mucho tiempo. Recargá la página (F5) y volvé a entrar.');
            return;
        }

        enviarComoSiempre();
    });

    // ---- 5. Show / hide the password ---------------------------------------
    if (ojo && clave) {
        ojo.addEventListener('click', () => {
            const mostrando = clave.type === 'text';
            clave.type = mostrando ? 'password' : 'text';
            ojo.setAttribute('aria-pressed', mostrando ? 'false' : 'true');
            ojo.setAttribute('aria-label', mostrando ? 'Mostrar contraseña' : 'Ocultar contraseña');
            enfocar(clave);
        });
    }

    // ---- 6. The clock on the receipt ---------------------------------------
    // Rendered by the server; kept honest for a screen left open all night.
    if (reloj) {
        setInterval(() => {
            const ahora = new Date();
            reloj.textContent = `${String(ahora.getHours()).padStart(2, '0')}:${String(ahora.getMinutes()).padStart(2, '0')}`;
        }, 15000);
    }

    window.__comprobanteListo = true;
    imprimir().then(() => {
        enfocar(hoja.dataset.imprimir === 'no' ? clave : usuario);
    });
})();
