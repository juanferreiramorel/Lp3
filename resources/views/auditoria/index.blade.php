@extends('layouts.app')

@section('content')
    @php
        $activeFiltersCount = collect([
            request()->get('fecha_desde'),
            request()->get('fecha_hasta'),
            request()->get('user_id'),
            request()->get('operation'),
            request()->get('table_name'),
            request()->get('id_sucursal'),
            request()->get('buscar'),
        ])->filter(fn($v) => $v !== null && $v !== '')->count();

        $isPaginated = $auditoria instanceof \Illuminate\Pagination\LengthAwarePaginator;
        $collection = $isPaginated ? $auditoria->getCollection() : collect($auditoria);
        $perPage = method_exists($auditoria, 'perPage') ? $auditoria->perPage() : $collection->count();

        $statsSummary = [
            'total' => $statsTotals['total'] ?? ($isPaginated ? $auditoria->total() : $collection->count()),
            'pagina' => $collection->count(),
            'agrega' => $statsTotals['agrega'] ?? $collection->where('operacion', 'AGREGA')->count(),
            'modifica' => $statsTotals['modifica'] ?? $collection->where('operacion', 'MODIFICA')->count(),
            'elimina' => $statsTotals['elimina'] ?? $collection->where('operacion', 'ELIMINA')->count(),
        ];
    @endphp

    <section class="content-header audit-hero">
        <div class="container-fluid">
            <div class="row align-items-center mb-4">
                <div class="col-lg-7 hero-copy">
                    <div class="hero-eyebrow">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Panel · Auditoría
                    </div>
                    <h1 class="hero-title mb-2">Bitácora viva, lista para accionar</h1>
                    <p class="hero-lead mb-3">Observa cambios, detecta anomalías y aplica filtros precisos sin perder tiempo.</p>
                    <div class="hero-badges">
                        <span class="pill pill-dark">
                            <i class="fas fa-filter mr-1"></i>
                            <strong class="mr-1">{{ $activeFiltersCount }}</strong> filtros activos
                        </span>
                        <span class="pill pill-light">
                            <i class="fas fa-list mr-1"></i>
                            <strong class="mr-1">{{ $statsSummary['pagina'] }}</strong> en pantalla
                        </span>
                        <span class="pill pill-outline">
                            <i class="fas fa-chart-bar mr-1"></i>
                            <strong class="mr-1">{{ $perPage }}</strong> por página
                        </span>
                    </div>
                </div>
                <div class="col-lg-5 mt-3 mt-lg-0">
                    <div class="hero-kpi-grid">
                        <div class="hero-kpi-main">
                            <div class="kpi-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="kpi-content">
                                <div class="kpi-label">Total histórico</div>
                                <div class="kpi-value">{{ number_format($statsSummary['total']) }}</div>
                            </div>
                        </div>
                        <div class="hero-kpi-status">
                            <i class="fas fa-bolt text-warning mr-2"></i>
                            <span class="font-weight-bold">Auditoría en tiempo real</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row stats-row g-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card soft-blue" role="button" tabindex="0">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">En esta página</div>
                            <div class="stat-value">{{ $statsSummary['pagina'] }}</div>
                            <div class="stat-sub">Registros visibles</div>
                        </div>
                        <div class="stat-decoration"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card soft-green" role="button" tabindex="0" data-quick-operation="AGREGA">
                        <div class="stat-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Agregados</div>
                            <div class="stat-value">{{ $statsSummary['agrega'] }}</div>
                            <div class="stat-sub">Operación "AGREGA"</div>
                        </div>
                        <div class="stat-decoration"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card soft-amber" role="button" tabindex="0" data-quick-operation="MODIFICA">
                        <div class="stat-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Modificados</div>
                            <div class="stat-value">{{ $statsSummary['modifica'] }}</div>
                            <div class="stat-sub">Operación "MODIFICA"</div>
                        </div>
                        <div class="stat-decoration"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card soft-red" role="button" tabindex="0" data-quick-operation="ELIMINA">
                        <div class="stat-icon">
                            <i class="fas fa-trash-alt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Eliminados</div>
                            <div class="stat-value">{{ $statsSummary['elimina'] }}</div>
                            <div class="stat-sub">Operación "ELIMINA"</div>
                        </div>
                        <div class="stat-decoration"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3 auditoria-shell">
        @includeIf('auditoria.filtros', [
            'usuarios' => $usuarios,
            'sucursales' => $sucursales,
            'operaciones' => $operaciones,
            'tablas' => $tablas,
        ])

        <div class="quick-actions card mb-3">
            <div class="card-header" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 2px solid #dee2e6;">
                <h3 class="card-title mb-0" style="color: #495057; font-weight: 700; font-size: 1rem;">
                    <i class="fas fa-bolt text-warning mr-2"></i>
                    Acciones Rápidas
                </h3>
            </div>
            <div class="card-body" style="padding: 20px;">
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="filter-section">
                            <label class="d-block mb-3 filter-label">
                                <i class="far fa-calendar-alt mr-2"></i>
                                <span>Filtrar por Fecha</span>
                            </label>
                            <div class="date-chip-group">
                                <button type="button" class="btn-date-chip btn-date-chip-primary" data-quick-range="today">
                                    <i class="fas fa-calendar-day"></i>
                                    <span>Hoy</span>
                                </button>
                                <button type="button" class="btn-date-chip btn-date-chip-primary" data-quick-range="7d">
                                    <i class="fas fa-calendar-week"></i>
                                    <span>7 días</span>
                                </button>
                                <button type="button" class="btn-date-chip btn-date-chip-primary" data-quick-range="30d">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>30 días</span>
                                </button>
                                <button type="button" class="btn-date-chip btn-date-chip-clean" data-quick-range="clear">
                                    <i class="fas fa-redo"></i>
                                    <span>Limpiar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="filter-section">
                            <label class="d-block mb-3 filter-label">
                                <i class="fas fa-filter mr-2"></i>
                                <span>Filtrar por Operación</span>
                            </label>
                            <div class="btn-group-custom">
                                <button class="btn-quick-action btn-success" data-quick-operation="AGREGA">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Agregados</span>
                                </button>
                                <button class="btn-quick-action btn-warning" data-quick-operation="MODIFICA">
                                    <i class="fas fa-edit"></i>
                                    <span>Modificados</span>
                                </button>
                                <button class="btn-quick-action btn-danger" data-quick-operation="ELIMINA">
                                    <i class="fas fa-trash-alt"></i>
                                    <span>Eliminados</span>
                                </button>
                                <button class="btn-quick-action btn-secondary" data-quick-operation="">
                                    <i class="fas fa-list"></i>
                                    <span>Todas</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card tabla-container glass shadow-soft">
            @include('auditoria.table')
        </div>
    </div>
@endsection

@push('third_party_stylesheets')
    <style>
        .auditoria-shell { position: relative; z-index: 1; }

        /* === HERO SECTION === */
        .audit-hero {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border-radius: 8px;
            margin: 0 0 20px;
            padding: 24px 28px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
        }
        .audit-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .audit-hero::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-copy { color: #ffffff; position: relative; z-index: 2; }
        .hero-eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 700;
            margin-bottom: 8px;
        }
        .hero-title {
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ffffff;
            font-size: 2.2rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .hero-lead {
            color: rgba(255, 255, 255, 0.95);
            max-width: 720px;
            font-size: 1.05rem;
        }
        .hero-badges { display: flex; flex-wrap: wrap; gap: 10px; }
        .pill {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
            transition: all 0.2s ease;
        }
        .pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .pill-dark {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .pill-light {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .pill-outline {
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        /* === KPI SECTION === */
        .hero-kpi-grid {
            position: relative;
            z-index: 2;
        }
        .hero-kpi-main {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 20px 24px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }
        .kpi-icon {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #ffffff;
        }
        .kpi-content {
            flex: 1;
        }
        .kpi-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .kpi-value {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.1;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .hero-kpi-status {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.9rem;
        }

        /* === STAT CARDS === */
        .stats-row { margin-top: 20px; }
        .stat-card {
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            color: #0f172a;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 16px;
            min-height: 110px;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            z-index: 2;
            position: relative;
        }
        .stat-content {
            flex: 1;
            z-index: 2;
            position: relative;
        }
        .stat-label {
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 2px;
        }
        .stat-sub {
            font-size: 0.8rem;
            opacity: 0.75;
        }
        .stat-decoration {
            position: absolute;
            right: -20px;
            bottom: -20px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            opacity: 0.1;
            pointer-events: none;
        }
        .soft-blue { background: #fff; border: 1px solid #dee2e6; }
        .soft-blue .stat-icon { background: #17a2b8; color: #fff; }
        .soft-blue .stat-decoration { background: #17a2b8; }
        .soft-green { background: #fff; border: 1px solid #dee2e6; }
        .soft-green .stat-icon { background: #28a745; color: #fff; }
        .soft-green .stat-decoration { background: #28a745; }
        .soft-amber { background: #fff; border: 1px solid #dee2e6; }
        .soft-amber .stat-icon { background: #ffc107; color: #fff; }
        .soft-amber .stat-decoration { background: #ffc107; }
        .soft-red { background: #fff; border: 1px solid #dee2e6; }
        .soft-red .stat-icon { background: #dc3545; color: #fff; }
        .soft-red .stat-decoration { background: #dc3545; }

        /* === CARDS === */
        .glass {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 16px;
            background: #ffffff;
        }
        .shadow-soft { box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }

        /* === QUICK ACTIONS === */
        .quick-actions {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        }

        .filter-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
        }

        .filter-label {
            font-weight: 700;
            color: #495057;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }

        .filter-label i {
            color: #007bff;
            font-size: 1.1rem;
        }

        .btn-group-custom {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .btn-quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 18px 16px;
            border: 2px solid transparent;
            border-radius: 12px;
            background: #ffffff;
            color: #495057;
            font-weight: 700;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            min-height: 95px;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            position: relative;
            overflow: hidden;
        }

        .btn-quick-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,123,255,.05) 0%, rgba(0,86,179,.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }

        .btn-quick-action::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border-radius: 12px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .btn-quick-action i {
            font-size: 1.8rem;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-quick-action span {
            position: relative;
            z-index: 1;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.8rem;
            font-weight: 800;
        }

        /* === QUICK DATE BUTTONS === */
        .date-chip-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }

        .btn-date-chip,
        .filter-section button[data-quick-range]:not([data-quick-range="clear"]) {
            border-radius: 999px;
            border: 1px solid #d8dee8;
            background: linear-gradient(180deg, #ffffff 0%, #f4f7fb 100%);
            color: #495057;
            font-weight: 600;
            padding: 10px 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.01em;
            text-transform: none;
            font-size: 0.85rem;
            min-height: 44px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.7), 0 2px 6px rgba(15,23,42,0.08);
            transition: all 0.2s ease;
        }

        .btn-date-chip i,
        .filter-section button[data-quick-range]:not([data-quick-range="clear"]) i {
            font-size: 1rem;
            color: #0d6efd;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .btn-date-chip span,
        .filter-section button[data-quick-range]:not([data-quick-range="clear"]) span {
            font-weight: 700;
        }

        .btn-date-chip:hover,
        .filter-section button[data-quick-range]:not([data-quick-range="clear"]):hover {
            transform: translateY(-2px);
            border-color: #0d6efd;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.7), 0 6px 14px rgba(13,110,253,0.2);
        }

        .btn-date-chip:hover i,
        .filter-section button[data-quick-range]:not([data-quick-range="clear"]):hover i {
            transform: scale(1.1);
        }

        .btn-date-chip:active,
        .filter-section button[data-quick-range]:not([data-quick-range="clear"]):active {
            transform: translateY(0);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.6), 0 2px 8px rgba(13,110,253,0.18);
        }

        .btn-date-chip-primary,
        .filter-section button[data-quick-range]:not([data-quick-range="clear"]) {
            border-color: #bdd5ff;
            background: linear-gradient(135deg, #f1f6ff 0%, #ffffff 100%);
            color: #1b375c;
        }

        .btn-date-chip-primary:hover,
        .filter-section button[data-quick-range]:not([data-quick-range="clear"]):hover {
            background: linear-gradient(135deg, #e5f0ff 0%, #f8fbff 100%);
        }

        .btn-date-chip-clean,
        .filter-section button[data-quick-range="clear"] {
            border-color: #ffe0a1;
            background: linear-gradient(135deg, #fff8e1 0%, #fffdf4 100%);
            color: #785200;
        }

        .btn-date-chip-clean i,
        .filter-section button[data-quick-range="clear"] i {
            color: #e0a800;
        }

        .btn-date-chip-clean:hover,
        .filter-section button[data-quick-range="clear"]:hover {
            background: linear-gradient(135deg, #ffeec2 0%, #fff9e2 100%);
            border-color: #ffc107;
            color: #5d3b00;
        }

        .btn-quick-action.btn-success {
            background: linear-gradient(135deg, #e7f7ed 0%, #d4edda 100%);
            color: #155724;
            box-shadow: 0 2px 8px rgba(40,167,69,.2), inset 0 1px 0 rgba(255,255,255,.6);
        }

        .btn-quick-action.btn-success::before {
            background: linear-gradient(135deg, rgba(40,167,69,.15) 0%, rgba(33,136,56,.15) 100%);
        }

        .btn-quick-action.btn-success:hover {
            color: #0f5132;
            box-shadow: 0 8px 20px rgba(40,167,69,.35), inset 0 1px 0 rgba(255,255,255,.6);
        }

        .btn-quick-action.btn-warning {
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
            color: #856404;
            box-shadow: 0 2px 8px rgba(255,193,7,.2), inset 0 1px 0 rgba(255,255,255,.6);
        }

        .btn-quick-action.btn-warning::before {
            background: linear-gradient(135deg, rgba(255,193,7,.15) 0%, rgba(224,168,0,.15) 100%);
        }

        .btn-quick-action.btn-warning:hover {
            color: #d39e00;
            box-shadow: 0 8px 20px rgba(255,193,7,.35), inset 0 1px 0 rgba(255,255,255,.6);
        }

        .btn-quick-action.btn-danger {
            background: linear-gradient(135deg, #fdeaec 0%, #f8d7da 100%);
            color: #721c24;
            box-shadow: 0 2px 8px rgba(220,53,69,.2), inset 0 1px 0 rgba(255,255,255,.6);
        }

        .btn-quick-action.btn-danger::before {
            background: linear-gradient(135deg, rgba(220,53,69,.15) 0%, rgba(200,35,51,.15) 100%);
        }

        .btn-quick-action.btn-danger:hover {
            color: #a71d2a;
            box-shadow: 0 8px 20px rgba(220,53,69,.35), inset 0 1px 0 rgba(255,255,255,.6);
        }

        .btn-quick-action.btn-secondary {
            background: linear-gradient(135deg, #f0f1f2 0%, #e2e3e5 100%);
            color: #383d41;
            box-shadow: 0 2px 8px rgba(108,117,125,.2), inset 0 1px 0 rgba(255,255,255,.6);
        }

        .btn-quick-action.btn-secondary::before {
            background: linear-gradient(135deg, rgba(108,117,125,.15) 0%, rgba(90,98,104,.15) 100%);
        }

        .btn-quick-action.btn-secondary:hover {
            color: #23272b;
            box-shadow: 0 8px 20px rgba(108,117,125,.35), inset 0 1px 0 rgba(255,255,255,.6);
        }

        @media (max-width: 576px) {
            .btn-group-custom {
                grid-template-columns: 1fr;
            }
            .date-chip-group {
                grid-template-columns: 1fr;
            }
        }

        /* === TABLE === */
        .table-modern-wrap { border-radius: 14px; overflow: hidden; }
        .table-modern {
            margin-bottom: 0;
        }
        .table-modern thead th {
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
            color: #475569;
            font-weight: 700;
            padding: 16px 12px;
        }
        .table-modern tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-modern tbody tr:nth-child(even) { background: #fafafa; }
        .table-modern tbody tr:hover {
            background: #eff6ff !important;
            box-shadow: inset 4px 0 0 #0ea5e9;
            transform: scale(1.01);
        }
        .table-modern tbody td {
            vertical-align: middle;
            padding: 14px 12px;
            font-size: 0.9rem;
        }
        .tabla-container { overflow: hidden; }

        /* === RESPONSIVE === */
        @media (max-width: 992px) {
            .hero-title { font-size: 1.8rem; }
            .kpi-value { font-size: 2rem; }
        }
        @media (max-width: 768px) {
            .audit-hero {
                margin: 0 8px 16px;
                padding: 24px 18px 20px;
            }
            .hero-title { font-size: 1.5rem; }
            .hero-kpi-main { width: 100%; }
            .stat-card { min-height: 100px; }
            .stat-value { font-size: 1.6rem; }
            .chip-group { width: 100%; }
            .chip-separator { display: none; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function() {
            const applyFilters = (payload) => {
                if (window.auditoriaFilters && typeof window.auditoriaFilters.setAndApply === 'function') {
                    window.auditoriaFilters.setAndApply(payload);
                }
            };

            const formatDate = (date) => {
                const d = new Date(date);
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${d.getFullYear()}-${month}-${day}`;
            };

            const $clearFiltersBtn = $('#btn-limpiar-filtros');

            $('[data-quick-range]').on('click', function() {
                const range = $(this).data('quick-range');
                const today = new Date();
                let desde = '', hasta = '';

                if (range === 'today') {
                    desde = hasta = formatDate(today);
                } else if (range === '7d') {
                    const start = new Date(today);
                    start.setDate(today.getDate() - 6);
                    desde = formatDate(start);
                    hasta = formatDate(today);
                } else if (range === '30d') {
                    const start = new Date(today);
                    start.setDate(today.getDate() - 29);
                    desde = formatDate(start);
                    hasta = formatDate(today);
                } else if (range === 'clear') {
                    if ($clearFiltersBtn.length) {
                        $clearFiltersBtn.trigger('click');
                        return;
                    }
                    desde = '';
                    hasta = '';
                }

                applyFilters({ fecha_desde: desde, fecha_hasta: hasta });
            });

            $('[data-quick-operation]').on('click', function() {
                const operation = $(this).data('quick-operation');
                applyFilters({ operation: operation || '' });
            });

            // Click en tarjetas de estadísticas
            $('.stat-card[data-quick-operation]').on('click', function() {
                const operation = $(this).data('quick-operation');
                if (operation) {
                    applyFilters({ operation: operation });
                }
            });
        });
    </script>
@endpush
