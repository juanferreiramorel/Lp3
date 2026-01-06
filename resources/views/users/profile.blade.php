@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        Pérfil {{ auth()->user()->name }}
                    </h1>
                </div>

            </div>
        </div>
    </section>

    <section class="content">

        @include('adminlte-templates::common.errors')
        <div class="container-fluid">
            <div class="row">

                <!-- /.col -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item"><a class="nav-link active" href="#editar-perfil" data-toggle="tab">Editar Perfil</a></li>
                                <li class="nav-item"><a class="nav-link" href="#cambiar-clave" data-toggle="tab">Actualizar Contraseña</a></li>
                            </ul>
                        </div><!-- /.card-header -->
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="active tab-pane" id="editar-perfil">
                                    <form action="{{ route('users.updatePerfil') }}" method="post" enctype="multipart/form-data">
                                        @csrf
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h4>Información Personal</h4>
                                                <div class="form-group text-center">
                                                    <label>Avatar</label>
                                                    <div class="position-relative d-inline-block" id="avatar-container" style="cursor: pointer;">
                                                         <!-- Image Preview -->
                                                        <img class="profile-user-img img-fluid img-circle"
                                                            src="{{ auth()->user()->avatar_url ? auth()->user()->avatar_url : 'https://png.pngtree.com/png-vector/20210706/ourlarge/pngtree-blank-whatsapp-bussiness-man-photo-profile-png-image_3562846.jpg' }}"
                                                            alt="User profile picture"
                                                            id="avatar-preview"
                                                            style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%;">
                                                        
                                                        <!-- Overlay with Camera Icon -->
                                                        <div class="avatar-overlay text-center d-flex align-items-center justify-content-center" 
                                                             style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); border-radius: 50%; opacity: 0; transition: opacity 0.3s;">
                                                            <i class="fas fa-camera text-white fa-2x"></i>
                                                        </div>

                                                        <!-- Delete Button (Only if custom avatar exists) -->
                                                        @if(auth()->user()->avatar_url)
                                                            <button type="button" class="btn btn-danger btn-sm rounded-circle position-absolute" 
                                                                    id="delete-avatar-btn"
                                                                    style="bottom: 0; right: 0; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;"
                                                                    title="Eliminar Foto">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    
                                                    <!-- Hidden File Input -->
                                                    <input type="file" name="avatar" id="avatar-input" style="display: none;" accept="image/*">
                                                    
                                                    <!-- Hidden Delete Flag -->
                                                    <input type="hidden" name="delete_avatar" id="delete_avatar_flag" value="0">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Nombre</label>
                                                    <input type="text" class="form-control" name="name" value="{{ auth()->user()->name }}" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input type="email" class="form-control" name="email" value="{{ auth()->user()->email }}" required>
                                                </div>

                                                <div class="form-group">
                                                    <label>Dirección</label>
                                                    <input type="text" class="form-control" name="direccion" value="{{ auth()->user()->direccion }}">
                                                </div>

                                                <div class="form-group">
                                                    <label>Teléfono</label>
                                                    <input type="text" class="form-control" name="telefono" value="{{ auth()->user()->telefono }}">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <h4>Configuración de Accesos Rápidos</h4>
                                                <div class="form-group">
                                                    <label>Modo de Accesos Directos (Navbar)</label>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="shortcut_mode" id="mode_automatic" value="automatic" {{ auth()->user()->shortcut_mode == 'automatic' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="mode_automatic">
                                                            Automático (Últimas 5 visitadas)
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="shortcut_mode" id="mode_manual" value="manual" {{ auth()->user()->shortcut_mode == 'manual' ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="mode_manual">
                                                            Manual (Seleccionar hasta 5)
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="form-group" id="manual_shortcuts_group" style="{{ auth()->user()->shortcut_mode == 'manual' ? '' : 'display:none;' }}">
                                                    <label>Seleccionar Accesos Directos</label>
                                                    <select class="form-control select2" name="shortcuts[]" multiple="multiple" data-placeholder="Seleccione sus accesos" style="width: 100%;">
                                                        @php
                                                            $selectedShortcuts = json_decode(auth()->user()->shortcuts) ?? [];
                                                        @endphp
                                                        @foreach($availableShortcuts as $route => $label)
                                                            <option value="{{ $route }}" {{ in_array($route, $selectedShortcuts) ? 'selected' : '' }}>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Maximo 5 opciones.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row mt-3">
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-success float-right"> <i class="fa fa-save"></i> Guardar Cambios</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane" id="cambiar-clave">
                                    <div class="card-body px-0">
                                        <p class="login-box-msg p-0 pb-3">Por favor introduzca su nueva contraseña</p>
                                        <form action="{{ url('users/perfil/cambiar-password') }}" method="post">
                                            @csrf
                                            <div class="input-group mb-3">
                                                <input type="password" class="form-control" required name="password"
                                                    placeholder="Password">
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <span class="fas fa-lock"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="input-group mb-3">
                                                <input type="password" class="form-control" required name="confirm-password"
                                                    placeholder="Confirm Password">
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <span class="fas fa-lock"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary btn-block">
                                                        <i class="fa fa-save"></i>
                                                        Cambiar Contraseña</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- /.tab-content -->
                        </div><!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Interactive Avatar Logic
        $('#avatar-container').hover(function() {
            $(this).find('.avatar-overlay').css('opacity', 1);
        }, function() {
            $(this).find('.avatar-overlay').css('opacity', 0);
        });

        // Trigger file input on overlay click
        $('.avatar-overlay').click(function() {
            $('#avatar-input').click();
        });

        // Preview image on selection
        $('#avatar-input').change(function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#avatar-preview').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
                // Reset delete flag if new image selected
                $('#delete_avatar_flag').val('0');
            }
        });

        // Handle Delete Button
        $('#delete-avatar-btn').click(function(e) {
            e.stopPropagation(); // Prevent overlay click
            if(confirm('¿Está seguro de eliminar su foto de perfil?')) {
                // Set default image preview
                $('#avatar-preview').attr('src', 'https://png.pngtree.com/png-vector/20210706/ourlarge/pngtree-blank-whatsapp-bussiness-man-photo-profile-png-image_3562846.jpg');
                // Set delete flag
                $('#delete_avatar_flag').val('1');
                // Clear file input
                $('#avatar-input').val('');
                // Hide delete button
                $(this).hide();
            }
        });

        // Toggle visibility of shortcuts select based on mode
        $('input[name="shortcut_mode"]').change(function() {
            if ($(this).val() === 'manual') {
                $('#manual_shortcuts_group').slideDown();
            } else {
                $('#manual_shortcuts_group').slideUp();
            }
        });

        // Initialize Select2 if not already initialized (or re-initialize if needed specific options)
        // Initialize Select2
         $('.select2').select2({
            maximumSelectionLength: 5,
            language: {
                maximumSelected: function (e) {
                    return "Solo puedes seleccionar " + e.maximum + " opciones";
                }
            }
        });
        
         // File input custom label
        $(".custom-file-input").on("change", function() {
            var fileName = $(this).val().split("\\").pop();
            $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
        });
    });
</script>
@endpush
