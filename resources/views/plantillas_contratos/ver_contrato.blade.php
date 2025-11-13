@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        <i class="fas fa-file-contract"></i>
                        Contrato Generado
                    </h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-default float-right" href="{{ route('plantillas_contratos.contratos_generados') }}">
                        <i class="fas fa-list"></i> Ver Todos los Contratos
                    </a>
                    <a class="btn btn-danger float-right mr-2" href="{{ route('plantillas_contratos.descargar_pdf', $contrato->id_contrato_generado) }}" target="_blank">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </a>
                    <button class="btn btn-primary float-right mr-2" onclick="imprimirContrato()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('sweetalert::alert')

        <!-- Información del Contrato -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong><i class="fas fa-file-alt"></i> Plantilla:</strong>
                        <p>{{ $contrato->nombre_plantilla }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-user"></i> Cliente:</strong>
                        <p>{{ $contrato->clie_apellido }}, {{ $contrato->clie_nombre }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-id-card"></i> CI:</strong>
                        <p>{{ $contrato->clie_ci }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-calendar"></i> Fecha de Generación:</strong>
                        <p>{{ \Carbon\Carbon::parse($contrato->fecha_generacion)->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                @if ($contrato->observaciones)
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <strong><i class="fas fa-sticky-note"></i> Observaciones:</strong>
                            <p>{{ $contrato->observaciones }}</p>
                        </div>
                    </div>
                @endif

                <div class="row mt-2">
                    <div class="col-12">
                        <strong><i class="fas fa-user-tie"></i> Generado por:</strong>
                        <p>{{ $contrato->usuario_nombre ?? 'No especificado' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido del Contrato -->
        <div class="card" id="contrato-contenido">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-file-signature"></i> Contenido del Contrato</h5>
            </div>
            <div class="card-body" style="background-color: white; padding: 40px;">
                <div style="white-space: pre-wrap; font-family: 'Times New Roman', serif; font-size: 14px; line-height: 1.6; color: black;">{{ $contrato->contenido_generado }}</div>
            </div>
        </div>
    </div>

    @push('page_scripts')
        <script>
            function imprimirContrato() {
                // Crear una ventana de impresión con el contenido del contrato
                const contenido = document.getElementById('contrato-contenido').querySelector('.card-body').innerHTML;

                const ventanaImpresion = window.open('', '', 'width=800,height=600');
                ventanaImpresion.document.write('<html><head><title>Contrato - {{ $contrato->clie_apellido }}, {{ $contrato->clie_nombre }}</title>');
                ventanaImpresion.document.write('<style>');
                ventanaImpresion.document.write('body { font-family: "Times New Roman", serif; font-size: 14px; line-height: 1.6; padding: 40px; }');
                ventanaImpresion.document.write('</style>');
                ventanaImpresion.document.write('</head><body>');
                ventanaImpresion.document.write(contenido);
                ventanaImpresion.document.write('</body></html>');
                ventanaImpresion.document.close();

                // Esperar a que se cargue el contenido y luego imprimir
                setTimeout(() => {
                    ventanaImpresion.print();
                    ventanaImpresion.close();
                }, 250);
            }
        </script>
    @endpush
@endsection
