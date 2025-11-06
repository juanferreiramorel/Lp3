{{-- Modal Diff --}}
<div class="modal fade" id="modalDiff" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title" id="modalDiffTitle">Diferencias</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">×</span>
        </button>
      </div>

      <div class="modal-body p-0">
        <div class="p-2 border-bottom d-flex align-items-center justify-content-between">
            <div id="wrapOcultarIguales">
                <label class="mb-0">
                <input type="checkbox" id="ocultarIguales" checked>
                Ocultar campos iguales
                </label>
            </div>
            <div class="small text-muted">
                <span class="badge badge-warning">Cambiado</span>
                <span class="badge badge-success">Agregado</span>
                <span class="badge badge-danger">Eliminado</span>
                <span class="badge badge-secondary">Igual</span>
            </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm mb-0" id="tablaDiff">
            <thead class="thead-light">
              <tr>
                <th style="width: 260px">Campo</th>
                <th>Anterior</th>
                <th>Nuevo</th>
                <th style="width: 130px">Estado</th>
              </tr>
            </thead>
            <tbody><!-- rows dinámicos --></tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<style>
  /* Colores de filas según estado */
  #tablaDiff tbody tr.diff-changed   { background: rgba(255, 193, 7, 0.10); }   /* warning */
  #tablaDiff tbody tr.diff-added     { background: rgba(40, 167, 69, 0.10); }   /* success */
  #tablaDiff tbody tr.diff-removed   { background: rgba(220, 53, 69, 0.10); }   /* danger  */
  #tablaDiff tbody tr.diff-same      { background: rgba(108, 117, 125, 0.07); } /* secondary */
  /* Monoespaciado amigable para JSON/strings largos */
  #tablaDiff td:nth-child(2),
  #tablaDiff td:nth-child(3) {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    white-space: pre-wrap;
    word-break: break-word;
  }
</style>

<script>
(function(){
  const SENSITIVE_KEYS = ['password','remember_token','token','secret','api_key'];

  function pretty(v){
    if (v === null || v === undefined) return '<em class="text-muted">null</em>';
    if (typeof v === 'boolean') return v ? 'true' : 'false';
    if (typeof v === 'number')  return String(v);
    if (typeof v === 'object')  return JSON.stringify(v, null, 2);
    return String(v);
  }
  function maskIfSensitive(key, raw){
    return key && SENSITIVE_KEYS.includes(String(key).toLowerCase()) ? '••••••••' : raw;
  }
  function deepEqual(x,y){
    if (x === y) return true;
    if (typeof x !== typeof y) return false;
    if (x && y && typeof x === 'object'){
      if (Array.isArray(x) !== Array.isArray(y)) return false;
      if (Array.isArray(x)){
        if (x.length !== y.length) return false;
        for (let i=0;i<x.length;i++) if (!deepEqual(x[i],y[i])) return false;
        return true;
      }
      const kx = Object.keys(x), ky = Object.keys(y);
      if (kx.length !== ky.length) return false;
      for (const k of kx) if (!deepEqual(x[k],y[k])) return false;
      return true;
    }
    return false;
  }
  function diffObjects(a,b){
    const keys = new Set([...(Object.keys(a||{})), ...(Object.keys(b||{}))]);
    const rows = [];
    Array.from(keys).sort().forEach(key=>{
      const va = a ? a[key] : undefined;
      const vb = b ? b[key] : undefined;
      let estado = 'same';
      if (va === undefined && vb !== undefined) estado = 'added';
      else if (va !== undefined && vb === undefined) estado = 'removed';
      else if (!deepEqual(va, vb)) estado = 'changed';
      rows.push({ key, va, vb, estado });
    });
    return rows;
  }
  function escapeHtml(s){
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                    .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
                    .replace(/'/g,'&#039;');
  }
  function renderRows(rows, ocultarIguales){
    const $tbody = $('#tablaDiff tbody');
    $tbody.empty();
    rows.forEach(r=>{
      if (ocultarIguales && r.estado === 'same') return;
      let trClass='diff-same', badge='<span class="badge badge-secondary">Igual</span>';
      if (r.estado==='changed'){ trClass='diff-changed'; badge='<span class="badge badge-warning">Cambiado</span>'; }
      if (r.estado==='added'){   trClass='diff-added';   badge='<span class="badge badge-success">Agregado</span>'; }
      if (r.estado==='removed'){ trClass='diff-removed'; badge='<span class="badge badge-danger">Eliminado</span>'; }
      const vaRaw = maskIfSensitive(r.key, pretty(r.va));
      const vbRaw = maskIfSensitive(r.key, pretty(r.vb));

      // Escapar HTML solo si no contiene tags HTML válidos
      const va = vaRaw.includes('<em class=') ? vaRaw : escapeHtml(vaRaw);
      const vb = vbRaw.includes('<em class=') ? vbRaw : escapeHtml(vbRaw);

      $tbody.append(`
        <tr class="${trClass}">
          <td><code>${escapeHtml(r.key)}</code></td>
          <td>${va}</td>
          <td>${vb}</td>
          <td>${badge}</td>
        </tr>
      `);
    });
    if (!$tbody.children().length){
      $tbody.append(`<tr><td colspan="4" class="text-center text-muted py-4">No hay diferencias para mostrar.</td></tr>`);
    }
  }

  // === Helpers nuevos ===
  function b64ToText(b64){
    if (!b64) return '';
    try {
      // Decodificar base64 y manejar caracteres UTF-8 correctamente
      const text = atob(b64);
      return decodeURIComponent(escape(text));
    } catch(e){
      console.error('Error decodificando base64:', e);
      return '';
    }
  }
  function parseJsonFlexible(txt){
    if (!txt || !txt.trim()) return {};
    let v = txt.trim();
    v = v.replace(/&quot;/g, '"').replace(/&#039;/g, "'");
    try {
      const j = JSON.parse(v);
      if (typeof j === 'string') {
        try { return JSON.parse(j); } catch { return j; }
      }
      return j ?? {};
    } catch (e1) {
      if (v.startsWith("'") && v.endsWith("'")) {
        try { return JSON.parse(v.slice(1,-1)); } catch(e2){}
      }
      return {};
    }
  }
  function coerceTypes(obj){
    if (!obj || typeof obj !== 'object') return {};
    const out = {};
    for (const k of Object.keys(obj)){
      let v = obj[k];
      if (typeof v === 'string'){
        const s = v.trim();
        if (s === 'true') v = true;
        else if (s === 'false') v = false;
        else if (s === 'null' || s === '') v = null;
        else if (!isNaN(s) && s !== '') v = Number(s);
      }
      out[k] = v;
    }
    return out;
  }

  // Normaliza texto de operación (trim, mayúsculas, sin acentos)
  function normalizeOp(txt){
    return (txt || '')
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'') // sin acentos
      .toUpperCase()
      .trim();
  }

  // === MAIN ===
  window.verDiferencias = function(el){
    const titulo    = el.getAttribute('data-titulo') || 'Diferencias';
    const operRaw   = el.getAttribute('data-operacion') || '';
    const operacion = normalizeOp(operRaw);

    // Tomamos los JSON en Base64
    const anteriorTxt = b64ToText(el.getAttribute('data-anterior-b64') || '');
    const nuevoTxt    = b64ToText(el.getAttribute('data-nuevo-b64') || '');

    // Parseo robusto
    let objA = coerceTypes(parseJsonFlexible(anteriorTxt));
    let objB = coerceTypes(parseJsonFlexible(nuevoTxt));

    if (Array.isArray(objA)) objA = Object.assign({}, objA);
    if (Array.isArray(objB)) objB = Object.assign({}, objB);

    // Detección robusta de operación (cubre "ELIMINA REGISTRO", "Modifica", etc.)
    const isAgrega   = operacion.includes('AGREGA');
    const isElimina  = operacion.includes('ELIMINA');
    const isModifica = operacion.includes('MODIFICA');

    let rows = [];

    // Contenedor del checkbox
    const $wrapChk = $('#wrapOcultarIguales');
    // Aseguramos que no quede escondido por clases utilitarias
    $wrapChk.removeClass('d-none invisible');

    if (isAgrega){
      rows = Object.keys(objB || {}).sort().map(k => ({ key:k, va: undefined, vb: objB[k], estado:'added' }));
      $wrapChk.toggle(false);
      $('#ocultarIguales').prop('checked', true).prop('disabled', true);
    } else if (isElimina){
      rows = Object.keys(objA || {}).sort().map(k => ({ key:k, va: objA[k], vb: undefined, estado:'removed' }));
      $wrapChk.toggle(false);
      $('#ocultarIguales').prop('checked', true).prop('disabled', true);
    } else {
      rows = diffObjects(objA, objB);
      // Mostrar checkbox SOLO en MODIFICA
      $wrapChk.toggle(isModifica);
      $('#ocultarIguales').prop('checked', true).prop('disabled', !isModifica);
    }

    $('#modalDiffTitle').html(`${escapeHtml(titulo)} <small class="text-muted">[${escapeHtml(operRaw)}]</small>`);

    // Si el checkbox está visible usamos su estado; si no, ocultamos iguales por defecto
    const usarChk = $wrapChk.is(':visible');
    const ocultar = usarChk ? $('#ocultarIguales').prop('checked') : true;
    renderRows(rows, ocultar);

    if ($('#modalDiff').modal) { $('#modalDiff').modal('show'); }
    else { (new bootstrap.Modal(document.getElementById('modalDiff'))).show(); }

    // onChange: solo cuando el checkbox está visible (MODIFICA)
    $('#ocultarIguales').off('change').on('change', function(){
      if (!$('#wrapOcultarIguales').is(':visible')) return;
      renderRows(rows, this.checked);
    });

    console.debug('[AUDIT-DIFF]', { operacionNormalizada: operacion, operacionOriginal: operRaw, rowsCount: rows.length, mostrarCheckbox: isModifica });
  };
})();
</script>
