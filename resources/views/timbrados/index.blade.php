@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Timbrados</h1>
                </div>
                <div class="col-sm-6">
                    @can('timbrados create')
                    <a class="btn btn-primary float-right"
                       href="{{ route('timbrados.create') }}">
                       <i class="fas fa-plus"></i>
                        Agregar Timbrado
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('timbrados.index') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Empresa</label>
                                <select name="id_empresa" class="form-control">
                                    <option value="">Todas</option>
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id_empresa }}"
                                            {{ request('id_empresa') == $empresa->id_empresa ? 'selected' : '' }}>
                                            {{ $empresa->razon_social }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tipo de Documento</label>
                                <select name="id_tipo_timbrado" class="form-control">
                                    <option value="">Todos</option>
                                    @foreach($tipos as $tipo)
                                        <option value="{{ $tipo->id_tipo_timbrado }}"
                                            {{ request('id_tipo_timbrado') == $tipo->id_tipo_timbrado ? 'selected' : '' }}>
                                            {{ $tipo->descripcion }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Estado</label>
                                <select name="estado" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="VIGENTE" {{ request('estado') == 'VIGENTE' ? 'selected' : '' }}>Vigente</option>
                                    <option value="VENCIDO" {{ request('estado') == 'VENCIDO' ? 'selected' : '' }}>Vencido</option>
                                    <option value="AGOTADO" {{ request('estado') == 'AGOTADO' ? 'selected' : '' }}>Agotado</option>
                                    <option value="CANCELADO" {{ request('estado') == 'CANCELADO' ? 'selected' : '' }}>Cancelado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Buscar</label>
                                <input type="text" name="buscar" class="form-control"
                                    placeholder="Número de timbrado o empresa"
                                    value="{{ request('buscar') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card tabla-container">
            @include('timbrados.table')
        </div>

        <div class="card-footer clearfix">
            {!! $timbrados->appends(request()->except('page'))->links() !!}
        </div>
    </div>

@endsection
