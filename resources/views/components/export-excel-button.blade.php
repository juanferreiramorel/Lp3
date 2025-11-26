{{--
    Componente de botón para exportar a Excel

    Uso:
    @include('components.export-excel-button', [
        'route' => 'nombre-de-la-ruta',
        'params' => ['param1' => 'value1', 'param2' => 'value2'],
        'class' => 'clases-adicionales' (opcional),
        'text' => 'Texto del botón' (opcional, por defecto: 'Exportar Excel')
    ])
--}}

@php
    $buttonClass = $class ?? '';
    $buttonText = $text ?? 'Exportar Excel';
    $buttonId = $id ?? 'btn-export-excel';
@endphp

<button
    type="button"
    id="{{ $buttonId }}"
    class="btn btn-success {{ $buttonClass }}"
    data-toggle="tooltip"
    data-placement="top"
    title="Exportar a Excel">
    <i class="fas fa-file-excel"></i>
    {{ $buttonText }}
</button>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#{{ $buttonId }}').on('click', function(e) {
            e.preventDefault();

            @if(isset($route))
                let url = '{{ url($route) }}';
                let params = new URLSearchParams();

                @if(isset($params) && is_array($params))
                    @foreach($params as $key => $value)
                        @if(strpos($value, '$') === 0)
                            // Es una referencia a un input
                            let fieldValue = $('{{ $value }}').val();
                            if (fieldValue) {
                                params.append('{{ $key }}', fieldValue);
                            }
                        @else
                            params.append('{{ $key }}', '{{ $value }}');
                        @endif
                    @endforeach
                @endif

                params.append('exportar', 'excel');

                let fullUrl = url + '?' + params.toString();
                window.open(fullUrl, '_blank');
            @else
                console.error('No se ha especificado una ruta para el botón de exportar');
            @endif
        });
    });
</script>
@endpush
