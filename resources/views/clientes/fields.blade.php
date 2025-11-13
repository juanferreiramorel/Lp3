<!-- Clie Nombre Field -->
<div class="form-group col-sm-6">
    {!! Form::label('clie_nombre', 'Nombre:') !!}
    {!! Form::text('clie_nombre', null, ['class' => 'form-control', 'required', 'placeholder' => 'Ingrese el Nombre']) !!}
</div>

<!-- Cli Apellido Field -->
<div class="form-group col-sm-6">
    {!! Form::label('clie_apellido', 'Apellido:') !!}
    {!! Form::text('clie_apellido', null, ['class' => 'form-control', 'required', 'placeholder' => 'Ingrese el Apellido']) !!}
</div>

<!-- Clie Ci Field -->
<div class="form-group col-sm-6">
    {!! Form::label('clie_ci', 'Nro. Documento:') !!}
    {!! Form::text('clie_ci', null, ['class' => 'form-control', 'required', 'placeholder' => 'Ingrese el Nro. de Documento']) !!}
</div>

<!-- Clie Telefono Field -->
<div class="form-group col-sm-6">
    {!! Form::label('clie_telefono', 'Telefono:') !!}
    {!! Form::text('clie_telefono', null, ['class' => 'form-control', 'placeholder' => 'Ingrese el Telefono']) !!}
</div>

<!-- Clie Direccion Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('clie_direccion', 'Direccion:') !!}
    {!! Form::textarea('clie_direccion', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Ingrese la Direccion']) !!}
</div>

<!-- Id Departamento Field -->
<div class="form-group col-sm-6">
    {!! Form::label('id_departamento', 'Departamento:') !!}
    {!! Form::select('id_departamento', $departamentos, null,
     ['class' => 'form-control', 'id' => 'id_departamento', 'required', 'placeholder' => 'Seleccione el Departamento']) !!}
</div>

<!-- Id Ciudad Field -->
<div class="form-group col-sm-6">
    {!! Form::label('id_ciudad', 'Ciudad:') !!}
    <select name="id_ciudad" id="id_ciudad" class="form-control" required>
        <option value="">Seleccione la Ciudad</option>
    </select>
</div>

<!-- Clie Fecha Nac Field -->
<div class="form-group col-sm-6">
    {!! Form::label('clie_fecha_nac', 'Fecha de Nacimiento:') !!}
    {!! Form::date('clie_fecha_nac', null, ['class' => 'form-control','id'=>'clie_fecha_nac', 'required', 'placeholder' => 'Ingrese la Fecha de Nacimiento']) !!}
</div>

<script>
    // Datos de ciudades desde PHP
    const ciudadesData = @json($ciudades);
    const ciudadSeleccionada = "{{ old('id_ciudad', isset($clientes) ? $clientes->id_ciudad : '') }}";
    const departamentoSeleccionado = "{{ old('id_departamento', isset($clientes) ? $clientes->id_departamento : '') }}";

    // Función para cargar ciudades según departamento
    function cargarCiudades(idDepartamento, ciudadASeleccionar = null) {
        const selectCiudad = document.getElementById('id_ciudad');
        selectCiudad.innerHTML = '<option value="">Seleccione la Ciudad</option>';

        if (!idDepartamento) {
            return;
        }

        // Filtrar ciudades por departamento
        const ciudadesFiltradas = ciudadesData.filter(ciudad => ciudad.id_departamento == idDepartamento);

        ciudadesFiltradas.forEach(ciudad => {
            const option = document.createElement('option');
            option.value = ciudad.id_ciudad;
            option.textContent = ciudad.descripcion;

            // Seleccionar la ciudad si coincide
            if (ciudadASeleccionar && ciudad.id_ciudad == ciudadASeleccionar) {
                option.selected = true;
            }

            selectCiudad.appendChild(option);
        });
    }

    // Evento cuando cambia el departamento
    document.getElementById('id_departamento').addEventListener('change', function() {
        cargarCiudades(this.value);
    });

    // Cargar ciudades al inicio si hay departamento seleccionado (para el edit)
    document.addEventListener('DOMContentLoaded', function() {
        if (departamentoSeleccionado) {
            cargarCiudades(departamentoSeleccionado, ciudadSeleccionada);
        }
    });
</script>

