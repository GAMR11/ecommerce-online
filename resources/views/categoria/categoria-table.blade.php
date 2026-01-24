@extends('layout')
@section('content')
    <div class="content-wrapper">
        <!-- Content -->

        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-6">
                <!-- Form controls -->
                <div class="col-md-6">
                    <form method="POST" action="{{ route('categoria.store') }}">
                        @csrf
                        <div class="card">
                            <h5 class="card-header">Registrar Categoría</h5>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label for="nombre" class="form-label">Nombre de categoría</label>
                                    <input class="form-control" minlength="3" maxlength="50" type="text" oninput="soloTexto(event); validarFormulario()" name="nombre" id="nombre"
                                        placeholder="ingrese el nombre..." />
                                </div>
                                {{-- <div>
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" name="descripcion" id="descripcion" rows="3"></textarea>
                                </div> --}}

                                <div class="d-flex justify-content-center align-items-center">
                                    <button type="submit" id="submit-btn" class="btn btn-primary my-2 mx-2" disabled>Enviar</button>
                                </div>

                            </div>

                        </div>
                    </form>
                </div>
                {{-- TABLA DE CATEGORIAS EXISTENTES --}}
                <div class="col-md-6">
                    <div class="card overflow-hidden">
                        <h5 class="card-header">Categorías</h5>
                        <div class="table-responsive text-nowrap">
                            @if($categorias->count() > 0)
                            <table class="table table-dark">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        {{-- <th>Descripción</th> --}}
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    @foreach ($categorias as $key => $categoria)
                                        <tr>
                                            <td>
                                                <span>{{ $categoria->nombre }}</span></td>
                                            {{-- <td>{{ $categoria->descripcion }}</td> --}}
                                            <td>
                                                <div class="dropdown">
                                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                        data-bs-toggle="dropdown">
                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" type="button" data-bs-toggle="modal"
                                                            data-bs-target="#editarCategoria{{ $key }}"><i
                                                                class="bx bx-edit-alt me-1"></i> Editar</a>
                                                                <form id="eliminarCategoria{{ $key }}" method="POST" action="{{ route('categoria.destroy',$categoria->id) }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <a onclick="document.getElementById('eliminarCategoria{{ $key }}').submit();" class="dropdown-item" href="javascript:void(0);"><i
                                                                            class="bx bx-trash me-1"></i> Eliminar</a>
                                                                </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        <form method="POST" action="{{ route('categoria.update',$categoria->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal fade" id="editarCategoria{{ $key }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel1">Editar Categoría
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col mb-6">
                                                                    <label for="nombre" class="form-label">Nombre</label>
                                                                    <input type="text" oninput="soloTexto(event)" id="nombre" name="nombre"
                                                                        class="form-control" value="{{ $categoria->nombre }}" minlength="3" maxlength="50"
                                                                        placeholder="Ingrese el nombre..." />
                                                                </div>
                                                            </div>
                                                            {{-- <div class="row">
                                                                <div class="col mb-6">
                                                                    <label for="nombre" class="form-label">Descripción</label>
                                                                    <textarea name="descripcion" id="descripcion" cols="30" rows="4">{{ $categoria->descripcion }}</textarea>
                                                                </div>
                                                            </div> --}}
                                                        </div>
                                                        <div
                                                            class="modal-footer d-flex flex-direction-row justify-content-center align-items-center">
                                                            <button type="button" class="btn btn-outline-secondary my-2 mx-2"
                                                                data-bs-dismiss="modal">
                                                                Cancelar
                                                            </button>
                                                            <button type="submit" class="btn btn-primary  my-2 mx-2">Guardar
                                                                cambios</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>


                                    @endforeach

                                </tbody>
                            </table>
                            @else
                            <div class="alert alert-warning text-center" role="alert">
                                No hay categorías registradas
                            </div>
                            @endif
                        </div>
                    </div>
                    <!--/ Bootstrap Dark Table -->
                </div>
            </div>
        </div>
        <!-- / Content -->

        <!-- Footer -->
        @include('templates.footer')
        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
    </div>

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
            const nombre = document.getElementById('nombre').value.trim();
            const submitBtn = document.getElementById('submit-btn');


            // Habilitar el botón si hay al menos 1 carácter en el campo nombre
            if (nombre.length > 0) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
    </script>

@endsection
