@extends('layout')
@section('content')
    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-6">

                {{-- FORMULARIO REGISTRO --}}
                <div class="col-md-6">
                    <form method="POST" action="{{ route('categoria.store') }}">
                        @csrf
                        <div class="card">
                            <h5 class="card-header d-flex align-items-center gap-2">
                                <i class="bx bx-category-alt"></i> Registrar Categoría
                            </h5>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label for="nombre" class="form-label">Nombre de categoría</label>
                                    <input class="form-control" minlength="3" maxlength="50" type="text"
                                        oninput="soloTexto(event); validarFormulario()" name="nombre" id="nombre"
                                        placeholder="ingrese el nombre..." />
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted">Mínimo 3 caracteres</small>
                                        <small id="char-count" class="text-muted">0 / 50</small>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-center align-items-center">
                                    <button type="submit" id="submit-btn" class="btn btn-primary my-2 mx-2" disabled>
                                        <i class="bx bx-save me-1"></i> Guardar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- TABLA DE CATEGORIAS --}}
                <div class="col-md-6">
                    <div class="card overflow-hidden">
                        <h5 class="card-header d-flex align-items-center justify-content-between">
                            <span><i class="bx bx-list-ul me-1"></i> Categorías</span>
                            <span class="badge bg-primary rounded-pill">{{ $categorias->count() }}</span>
                        </h5>
                        <div class="table-responsive text-nowrap">
                            @if($categorias->count() > 0)
                            <table class="table table-dark table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 40px">#</th>
                                        <th>Nombre</th>
                                        <th style="width: 80px" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    @foreach ($categorias as $key => $categoria)
                                        <tr>
                                            <td><small class="text-muted">{{ $key + 1 }}</small></td>
                                            <td>
                                                <span class="fw-medium">{{ $categoria->nombre }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <button type="button" class="btn btn-sm p-0 dropdown-toggle hide-arrow"
                                                        data-bs-toggle="dropdown">
                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item" type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editarCategoria{{ $key }}">
                                                            <i class="bx bx-edit-alt me-1 text-primary"></i> Editar
                                                        </a>
                                                        <form id="eliminarCategoria{{ $key }}" method="POST"
                                                            action="{{ route('categoria.destroy', $categoria->id) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <a onclick="confirmarEliminar({{ $key }})"
                                                                class="dropdown-item text-danger"
                                                                href="javascript:void(0);">
                                                                <i class="bx bx-trash me-1"></i> Eliminar
                                                            </a>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- MODAL EDITAR --}}
                                        <form method="POST" action="{{ route('categoria.update', $categoria->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal fade" id="editarCategoria{{ $key }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">
                                                                <i class="bx bx-edit-alt me-1"></i> Editar Categoría
                                                            </h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col mb-6">
                                                                    <label for="nombre" class="form-label">Nombre</label>
                                                                    <input type="text" oninput="soloTexto(event)"
                                                                        id="nombre" name="nombre"
                                                                        class="form-control"
                                                                        value="{{ $categoria->nombre }}"
                                                                        minlength="3" maxlength="50"
                                                                        placeholder="Ingrese el nombre..." />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer d-flex justify-content-center">
                                                            <button type="button"
                                                                class="btn btn-outline-secondary my-2 mx-2"
                                                                data-bs-dismiss="modal">
                                                                <i class="bx bx-x me-1"></i> Cancelar
                                                            </button>
                                                            <button type="submit" class="btn btn-primary my-2 mx-2">
                                                                <i class="bx bx-save me-1"></i> Guardar cambios
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>

                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <div class="p-4">
                                <div class="alert alert-warning d-flex align-items-center gap-2 mb-0" role="alert">
                                    <i class="bx bx-info-circle fs-5"></i>
                                    No hay categorías registradas aún.
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- / Content -->

        @include('templates.footer')
        <div class="content-backdrop fade"></div>
    </div>

    @include('sweetalert::alert')

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Errores en el formulario',
                html: `{!! implode('<br>', $errors->all()) !!}`,
                toast: true,
                position: 'top-end',
                timer: 5000,
                showConfirmButton: false
            });
        </script>
    @endif

    <script>
        function validarFormulario() {
            const input    = document.getElementById('nombre');
            const nombre   = input.value.trim();
            const submitBtn = document.getElementById('submit-btn');
            const charCount = document.getElementById('char-count');

            // Actualizar contador de caracteres
            charCount.textContent = `${input.value.length} / 50`;
            charCount.className   = input.value.length >= 45
                ? 'text-warning small'
                : 'text-muted small';

            submitBtn.disabled = nombre.length < 3;
        }

        function confirmarEliminar(key) {
            Swal.fire({
                title: '¿Eliminar categoría?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('eliminarCategoria' + key).submit();
                }
            });
        }
    </script>
@endsection