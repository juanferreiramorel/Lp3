{{-- Modal Diff --}}
<div class="modal fade" id="modalDiff" tabindex="-1" aria-hidden="true" data-backdrop="false" data-keyboard="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-diff-custom">
    <div class="modal-content modal-diff-content">
      <div class="modal-header modal-diff-header">
        <div class="modal-title-wrapper">
          <div class="modal-icon">
            <i class="fas fa-code-branch"></i>
          </div>
          <div>
            <h5 class="modal-title mb-0" id="modalDiffTitle">Diferencias</h5>
            <p class="modal-subtitle mb-0">Comparación de cambios en el registro</p>
          </div>
        </div>
        <button type="button" class="close modal-close-btn" data-dismiss="modal" aria-label="Cerrar">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="modal-body modal-diff-body p-0">
        <div class="diff-controls">
            <div class="control-left">
                <div id="wrapOcultarIguales" class="custom-checkbox-wrapper">
                    <input type="checkbox" id="ocultarIguales" checked class="custom-checkbox-input">
                    <label for="ocultarIguales" class="custom-checkbox-label">
                        <i class="fas fa-eye-slash mr-2"></i>
                        Ocultar campos iguales
                    </label>
                </div>
            </div>
            <div class="control-right">
                <div class="badge-legend">
                    <span class="legend-item">
                        <span class="legend-badge legend-changed"></span>
                        Cambiado
                    </span>
                    <span class="legend-item">
                        <span class="legend-badge legend-added"></span>
                        Agregado
                    </span>
                    <span class="legend-item">
                        <span class="legend-badge legend-removed"></span>
                        Eliminado
                    </span>
                    <span class="legend-item">
                        <span class="legend-badge legend-same"></span>
                        Igual
                    </span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
          <table class="table mb-0 table-diff-modern" id="tablaDiff">
            <thead>
              <tr>
                <th class="th-campo">
                    <i class="fas fa-tag mr-2"></i>
                    Campo
                </th>
                <th class="th-anterior">
                    <i class="fas fa-history mr-2"></i>
                    Valor Anterior
                </th>
                <th class="th-nuevo">
                    <i class="fas fa-check-circle mr-2"></i>
                    Valor Nuevo
                </th>
                <th class="th-estado">
                    <i class="fas fa-info-circle mr-2"></i>
                    Estado
                </th>
              </tr>
            </thead>
            <tbody><!-- rows dinámicos --></tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer modal-diff-footer">
        <button type="button" class="btn btn-cerrar-modal" data-dismiss="modal">
          <i class="fas fa-times mr-2"></i>
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<style>
  /* === MODAL DIFF STYLES === */
  .modal-diff-custom {
    width: calc(100% - 40px);
    max-width: 1200px;
    margin: 1.5rem auto;
  }
  .modal-diff-content {
    border-radius: 4px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,.5);
    overflow: hidden;
    max-height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
    background: #ffffff;
  }

  .modal-diff-body {
    padding: 0;
    background: #ffffff;
    flex: 1;
    overflow: auto;
    max-height: calc(100vh - 220px);
  }

  /* Modal Header */
  .modal-diff-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .modal-title-wrapper {
    display: flex;
    align-items: center;
    gap: 16px;
    color: #ffffff;
  }
  .modal-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
  }
  .modal-title {
    color: #ffffff;
    font-weight: 700;
    font-size: 1.4rem;
  }
  .modal-subtitle {
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.9rem;
    margin-top: 4px;
  }
  .modal-close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 1.1rem;
    opacity: 1;
    transition: all 0.2s ease;
    padding: 0;
  }
  .modal-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
  }

  /* Diff Controls */
  .diff-controls {
    background: linear-gradient(to bottom, #fafbfc 0%, #f8fafc 100%);
    padding: 18px 24px;
    border-bottom: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    position: sticky;
    top: 0;
    z-index: 2;
  }
  .custom-checkbox-wrapper {
    display: flex;
    align-items: center;
  }
  .custom-checkbox-input {
    width: 20px;
    height: 20px;
    cursor: pointer;
    margin-right: 8px;
  }
  .custom-checkbox-label {
    font-weight: 600;
    color: #475569;
    font-size: 0.95rem;
    margin: 0;
    cursor: pointer;
    display: flex;
    align-items: center;
  }

  /* Badge Legend */
  .badge-legend {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
  }
  .legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 600;
  }
  .legend-badge {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    border: 1px solid rgba(0, 0, 0, 0.1);
  }
  .legend-changed { background: #ffc107; }
  .legend-added { background: #28a745; }
  .legend-removed { background: #dc3545; }
  .legend-same { background: #6c757d; }

  /* Table Diff */
  .table-diff-modern {
    font-size: 0.9rem;
  }
  .table-diff-modern thead th {
    background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
    color: #475569;
    font-weight: 700;
    font-size: 0.85rem;
    letter-spacing: 0.05em;
    border: none;
    padding: 16px 14px;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .table-diff-modern thead th i {
    opacity: 0.7;
  }

  /* Colores de filas según estado */
  #tablaDiff tbody tr.diff-changed {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
  }
  #tablaDiff tbody tr.diff-added {
    background: #d4edda;
    border-left: 4px solid #28a745;
  }
  #tablaDiff tbody tr.diff-removed {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
  }
  #tablaDiff tbody tr.diff-same {
    background: #f8f9fa;
    border-left: 4px solid #dee2e6;
  }

  #tablaDiff tbody tr {
    transition: all 0.2s ease;
  }
  #tablaDiff tbody tr:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  #tablaDiff tbody td {
    padding: 14px;
    border-bottom: 1px solid #e2e8f0;
  }

  /* Primera columna (Campo) */
  #tablaDiff td:nth-child(1) {
    font-weight: 700;
    color: #0f172a;
  }
  #tablaDiff td:nth-child(1) code {
    background: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 700;
    color: #495057;
    border: 1px solid #dee2e6;
  }

  /* Columnas de valores (Anterior y Nuevo) */
  #tablaDiff td:nth-child(2),
  #tablaDiff td:nth-child(3) {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: 0.85rem;
    color: #1e293b;
    background: rgba(255, 255, 255, 0.5);
  }

  /* Columna Estado (badges) */
  #tablaDiff td:nth-child(4) .badge {
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  #tablaDiff td:nth-child(4) .badge-warning {
    background: #ffc107;
    color: #212529;
  }
  #tablaDiff td:nth-child(4) .badge-success {
    background: #28a745;
    color: #ffffff;
  }
  #tablaDiff td:nth-child(4) .badge-danger {
    background: #dc3545;
    color: #ffffff;
  }
  #tablaDiff td:nth-child(4) .badge-secondary {
    background: #6c757d;
    color: #ffffff;
  }

  /* Modal Footer */
  .modal-diff-footer {
    background: linear-gradient(to top, #fafbfc 0%, #f8fafc 100%);
    border-top: 2px solid #e2e8f0;
    padding: 18px 24px;
    position: sticky;
    bottom: 0;
    z-index: 2;
  }
  .btn-cerrar-modal {
    background: #6c757d;
    color: #ffffff;
    border: 1px solid #6c757d;
    padding: 10px 20px;
    border-radius: 4px;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  .btn-cerrar-modal:hover {
    background: #5a6268;
    border-color: #545b62;
    color: #ffffff;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .modal-diff-custom {
      max-width: 98%;
    }
    .diff-controls {
      flex-direction: column;
      align-items: flex-start;
    }
    .badge-legend {
      width: 100%;
      justify-content: space-between;
    }
  }
</style>

<script>
(function(init){
  function safelyInit(){
    if (window.jQuery) {
      init(window.jQuery);
    }
  }
  if (window.jQuery) {
    safelyInit();
  } else {
    document.addEventListener('DOMContentLoaded', safelyInit);
    window.addEventListener('load', safelyInit);
  }
})(function($){
  const SENSITIVE_KEYS = ['password','remember_token','token','secret','api_key'];
  const $modal = $('#modalDiff');
  if (!$modal.length) {
    return;
  }

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
    const $modalDiff = $('#modalDiff');
    if (!$modalDiff.length) {
      console.warn('Modal de diferencias no encontrado');
      return;
    }
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

    if ($modalDiff.modal) {
        $modalDiff.modal('show');
    } else if (window.bootstrap) {
        (new bootstrap.Modal(document.getElementById('modalDiff'))).show();
    }

    // onChange: solo cuando el checkbox está visible (MODIFICA)
    $('#ocultarIguales').off('change').on('change', function(){
      if (!$('#wrapOcultarIguales').is(':visible')) return;
      renderRows(rows, this.checked);
    });

    console.debug('[AUDIT-DIFF]', { operacionNormalizada: operacion, operacionOriginal: operRaw, rowsCount: rows.length, mostrarCheckbox: isModifica });
  };
});
</script>
