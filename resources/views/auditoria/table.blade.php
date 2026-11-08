
    <div class="card-body p-0">
        <div class="table-modern-wrap">
            <x-data-table :columns="$columns" :sort="$sort" :paginator="$auditoria"
                          id="productos-table"
                          table-class="table table-hover align-middle table-modern mb-0"
                          empty="No hay movimientos registrados para este filtro.">
                    @foreach ($auditoria as $fila)
                        <tr class="table-row-hover">
                            <td class="td-id">
                                <span class="badge badge-id">#{{ $fila->id }}</span>
                            </td>
                            <td class="td-sucursal">
                                <div class="cell-content">
                                    <i class="fas fa-building text-muted mr-2"></i>
                                    <span>{{ $fila->sucursal }}</span>
                                </div>
                            </td>
                            <td class="td-usuario">
                                <div class="cell-content">
                                    <div class="user-avatar">
                                        {{ strtoupper(substr($fila->usuario, 0, 1)) }}
                                    </div>
                                    <span>{{ $fila->usuario }}</span>
                                </div>
                            </td>
                            <td class="td-operacion">
                                @php
                                    $operacion = trim($fila->operacion);
                                @endphp
                                @if($operacion == 'AGREGA')
                                    <span class="badge-operacion badge-op-agrega">
                                        <i class="fas fa-plus-circle mr-1"></i>
                                        {{ $operacion }}
                                    </span>
                                @elseif($operacion == 'MODIFICA')
                                    <span class="badge-operacion badge-op-modifica">
                                        <i class="fas fa-edit mr-1"></i>
                                        {{ $operacion }}
                                    </span>
                                @elseif($operacion == 'ELIMINA')
                                    <span class="badge-operacion badge-op-elimina">
                                        <i class="fas fa-trash-alt mr-1"></i>
                                        {{ $operacion }}
                                    </span>
                                @else
                                    <span class="badge-operacion badge-op-other">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        {{ $operacion }}
                                    </span>
                                @endif
                            </td>
                            <td class="td-tabla">
                                <code class="table-name-code">{{ $fila->tabla }}</code>
                            </td>
                            <td class="td-fecha">
                                <div class="fecha-wrapper">
                                    <div class="fecha-dia">{{ \Carbon\Carbon::parse($fila->fecha)->format('d/m/Y') }}</div>
                                    <div class="fecha-hora">{{ \Carbon\Carbon::parse($fila->fecha)->format('H:i:s') }}</div>
                                </div>
                            </td>
                            <td class="td-accion text-center">
                                <button class='btn btn-ver-diferencias'
                                    title="Ver diferencias"
                                    onclick="verDiferencias(this)"
                                    data-titulo="Auditoría #{{ $fila->id }} - {{ $fila->tabla }}"
                                    data-operacion='{{ $fila->operacion }}'
                                    data-anterior-b64='{{ base64_encode($fila->anterior ?? "{}") }}'
                                    data-nuevo-b64='{{ base64_encode($fila->nuevo ?? "{}") }}'>
                                    <i class="fas fa-eye"></i>
                                    <span class="btn-text">Ver</span>
                                </button>
                            </td>
                        </tr>
                    @endforeach
            </x-data-table>
        </div>

        <div class="card-footer card-footer-modern clearfix">
            <div class="float-right">
                @include('adminlte-templates::common.paginate', ['records' => $auditoria])
            </div>
        </div>
    </div>

    <style>
        /* === TABLE STYLES === */
        .table-modern thead th {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            border: none;
            padding: 16px 14px;
            white-space: nowrap;
        }
        .table-modern thead th i {
            opacity: 0.9;
        }

        .table-modern tbody tr {
            transition: all 0.2s ease;
        }
        .table-modern tbody tr:hover {
            background: #f8f9fa !important;
            box-shadow: inset 4px 0 0 #007bff;
        }
        .table-modern tbody td {
            padding: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Badge ID */
        .badge-id {
            background: #007bff;
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        /* Cell Content */
        .cell-content {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* User Avatar */
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #6c757d;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }

        /* Badge Operación */
        .badge-operacion {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        .badge-op-agrega {
            background: #28a745;
            color: #fff;
            border: none;
        }
        .badge-op-modifica {
            background: #ffc107;
            color: #212529;
            border: none;
        }
        .badge-op-elimina {
            background: #dc3545;
            color: #fff;
            border: none;
        }
        .badge-op-other {
            background: #17a2b8;
            color: #fff;
            border: none;
        }

        /* Table Name Code */
        .table-name-code {
            background: #f1f5f9;
            padding: 6px 10px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        /* Fecha Wrapper */
        .fecha-wrapper {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .fecha-dia {
            font-weight: 600;
            color: #0f172a;
            font-size: 0.9rem;
        }
        .fecha-hora {
            font-size: 0.8rem;
            color: #64748b;
        }

        /* Botón Ver Diferencias */
        .btn-ver-diferencias {
            background: #17a2b8;
            color: #ffffff;
            border: 1px solid #17a2b8;
            padding: 6px 14px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-ver-diferencias:hover {
            background: #138496;
            border-color: #117a8b;
            color: #ffffff;
        }
        .btn-ver-diferencias i {
            font-size: 0.95rem;
        }

        /* Card Footer */
        .card-footer-modern {
            background: linear-gradient(to bottom, #fafbfc 0%, #f8fafc 100%);
            border-top: 2px solid #e2e8f0;
            padding: 16px 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .table-modern thead th {
                font-size: 0.75rem;
                padding: 12px 8px;
            }
            .table-modern tbody td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }
            .btn-ver-diferencias .btn-text {
                display: none;
            }
        }
    </style>

    @include('auditoria.modal')
