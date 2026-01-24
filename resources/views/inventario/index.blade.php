@extends('layout')
@section('content')
    <div class="content-wrapper">
        <!-- Content -->

        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-12 d-flex justify-content-center">
                <!-- Form controls -->

                {{-- TABLA DE CATEGORIAS EXISTENTES --}}
                <div class="col-md-12">
                    <div class="card overflow-hidden">
                        <h5 class="card-header">Productos</h5>

                        <div>
                            <label for="">Total en Inventario: {{ $totalInventario }}</label>
                        </div>
                        <div class="table-responsive text-nowrap">
                            @if (count($productos) > 0)
                                <table id="example" class="stripe row-border order-column nowrap" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Marca</th>
                                            <th>Modelo</th>
                                            <th>Color</th>
                                            <th>Stock</th>
                                            <th>Categoría</th>
                                            <th>POriginal</th>
                                            <th>PContado</th>
                                            <th>PCredito</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($productos as $key => $producto)
                                            <tr id="id-producto-{{ $producto->id }}" data-id="{{ $producto->id }}"
                                                data-product="{{ json_encode($producto) }}">
                                                <td>{{ $producto->nombre }}</td>
                                                <td>{{ $producto->marca }}</td>
                                                <td>{{ $producto->modelo }}</td>
                                                @if(Str::startsWith($producto->color, '#'))
                                                <td>
                                                    <div style="width: 20px; height: 20px; background-color: {{ $producto->color }};"></div>
                                                </td>
                                            @else
                                            <td>
                                                {{ $producto->color }}
                                            </td>
                                            @endif
                                                <td>{{ $producto->cantidad }}</td>
                                                <td>{{ $producto->categoria->nombre }}</td>
                                                <td>{{ $producto->precio_original }}</td>
                                                <td>{{ $producto->precio_contado }}</td>
                                                <td>{{ $producto->precio_credito }}</td>
                                                <td>
                                                    <a data-bs-toggle="modal"
                                                        data-bs-target="#infoModal{{ $producto->id }}"
                                                        class="btn btn-primary"><i class="fas fa-eye"></i></a>
                                                    <a data-bs-toggle="modal"
                                                        data-bs-target="#editarModal{{ $producto->id }}"
                                                        class="btn btn-warning"><i class="fas fa-edit"></i></a>

                                                        <a onclick="if(confirm('¿Estás seguro de que deseas eliminar este producto?')) { document.getElementById('eliminarProducto{{ $key }}').submit(); }" class="btn btn-danger"><i class="fas fa-trash"></i></a>

                                                        <form id="eliminarProducto{{ $key }}" method="POST" action="{{ route('producto.destroy',$producto->id) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>

                                                </td>
                                            </tr>





                                            {{-- MODAL DE DETALLES DEL PRODUCTO --}}
                                            <div class="modal fade" id="infoModal{{ $producto->id }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel1">Detalle del
                                                                producto</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="nombre" class="form-label">Nombre</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $producto->nombre }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="marca" class="form-label">Marca</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $producto->marca }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="modelo" class="form-label">Modelo</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $producto->modelo }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="color" class="form-label">Color</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $producto->color }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="modelo"
                                                                        class="form-label">Series/Código</label>

                                                                    <select class="form-control">
                                                                        @foreach ($producto->inventarios as $inventario)
                                                                            <option
                                                                                value="{{ $inventario->numero_serie }}">
                                                                                {{ $inventario->numero_serie }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="color"
                                                                        class="form-label">Categoría</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $producto->categoria->nombre }}"
                                                                        readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="precio_original" class="form-label">Precio
                                                                        Original</label>

                                                                    <input type="number" name="precio_original"
                                                                        class="form-control"
                                                                        value="{{ $producto->precio_original }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="precio_contado" class="form-label">Precio
                                                                        Contado</label>

                                                                    <input type="number" name="precio_contado"
                                                                        class="form-control"
                                                                        value="{{ $producto->precio_contado }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="precio_credito" class="form-label">Precio
                                                                        Credito</label>
                                                                    <input type="number" class="form-control" readonly
                                                                        value="{{ $producto->precio_credito }}" />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="descripcion"
                                                                        class="form-label">Descripción</label>
                                                                    <textarea class="form-control" cols="30" rows="6" readonly>{{ $producto->descripcion }}</textarea>
                                                                </div>
                                                            </div>
                                                            <div class="row g-6 my-2">
                                                                <div class="container-imagenes"
                                                                    style="align-items: center;display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;gap:1em;">
                                                                    @if ($producto->imagenes()->count() > 0)
                                                                        @for ($i = 0; $i < count($producto->imagenes); $i++)
                                                                            <div class="col-md-4">
                                                                                <img src="{{ $producto->imagenes[$i]->url }}"
                                                                                    alt="Imagen"
                                                                                    class="img-fluid rounded-start">
                                                                            </div>
                                                                        @endfor
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer d-flex justify-content-center">
                                                            <button type="button" class="btn btn-outline-secondary"
                                                                data-bs-dismiss="modal">
                                                                Cerrar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal fade" id="editarModal{{ $producto->id }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <form id="formEditarProducto{{ $producto->id }}" method="POST" action="{{ route('producto.update',$producto->id) }}" enctype="multipart/form-data">
                                                        @csrf
                                                        {{ method_field('PUT') }}
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="exampleModalLabel1">Editar Producto</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="nombre"
                                                                            class="form-label">Nombre</label>
                                                                        <input type="text" name="nombre"
                                                                            class="form-control"
                                                                            value="{{ $producto->nombre }}" />
                                                                    </div>
                                                                    <div class="col mb-0">
                                                                        <label for="marca" class="form-label">Marca</label>
                                                                        <input type="text" name="marca"
                                                                            class="form-control"
                                                                            value="{{ $producto->marca }}" />
                                                                    </div>
                                                                </div>
                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="modelo"
                                                                            class="form-label">Modelo</label>
                                                                        <input type="text" name="modelo"
                                                                            class="form-control"
                                                                            value="{{ $producto->modelo }}" />
                                                                    </div>
                                                                    <div class="col mb-0">
                                                                        <label for="color" class="form-label">Color</label>
                                                                        <input type="text" name="color"
                                                                            class="form-control"
                                                                            value="{{ $producto->color }}" />
                                                                    </div>
                                                                </div>
                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="modelo"
                                                                            class="form-label">Series/Código</label>

                                                                        <select class="form-control">
                                                                            @foreach ($producto->inventarios as $inventario)
                                                                                <option
                                                                                    value="{{ $inventario->numero_serie }}">
                                                                                    {{ $inventario->numero_serie }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="col mb-0">
                                                                        <label for="color"
                                                                            class="form-label">Categoría</label>
                                                                            <select name="categoria_id" class="form-control">
                                                                                @foreach ($categorias as $categoria)
                                                                                <option value="{{ $categoria->id }}" @selected($categoria->id == $producto->categoria_id)>
                                                                                    {{ $categoria->nombre }}
                                                                                </option>
                                                                            @endforeach
                                                                            </select>
                                                                    </div>
                                                                </div>

                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="precio_original" class="form-label">Precio
                                                                            Original</label>

                                                                        <input type="number" name="precio_original"
                                                                            class="form-control"
                                                                            value="{{ $producto->precio_original }}"
                                                                             />
                                                                    </div>
                                                                    <div class="col mb-0">
                                                                        <label for="modelo" class="form-label">Precio
                                                                            Contado</label>

                                                                        <input type="number" name="precio_contado"
                                                                            class="form-control"
                                                                            value="{{ $producto->precio_contado }}"
                                                                             />
                                                                    </div>
                                                                </div>

                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="color" class="form-label">Precio
                                                                            Credito</label>
                                                                        <input type="number" name="precio_credito" class="form-control"
                                                                            value="{{ $producto->precio_credito }}" />
                                                                    </div>
                                                                </div>

                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="modelo"
                                                                            class="form-label">Descripción</label>
                                                                        <textarea class="form-control" name="descripcion" cols="30" rows="6">{{ $producto->descripcion }}</textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="row g-6 my-2">
                                                                    <div class="container-imagenes" style="align-items: center; display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center; gap:1em;">
                                                                        @if ($producto->imagenes()->count() > 0)
                                                                            @for ($i = 0; $i < count($producto->imagenes); $i++)
                                                                                <div class="col-md-4 position-relative" id="imagen-{{ $i }}">
                                                                                    <a class="btn btn-danger btn-sm position-absolute top-0 end-0" onclick="eliminarImagen({{ $producto->id }}, {{ $producto->imagenes[$i]->id }}, {{ $i }})">X</a>
                                                                                    <img src="{{ $producto->imagenes[$i]->url }}" alt="Imagen" class="img-fluid rounded-start">
                                                                                </div>
                                                                            @endfor
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                <input type="hidden" id="imagenesEliminadas{{ $producto->id }}" name="imagenesEliminadas">



                                                                <div class="row g-6 d-flex justify-content-center">
                                                                    <div class="col mb-0">
                                                                        <label for="modelo" class="form-label">Cargar nueva imagen</label>

                                                                        <input type="file" accept="image/*" multiple name="imagenes[]"
                                                                            class="form-control"
                                                                             />
                                                                    </div>
                                                                </div>

                                                            </div>
                                                            <div class="modal-footer d-flex justify-content-center " style="gap:1em;">
                                                                <button type="button" class="btn btn-outline-secondary"
                                                                    data-bs-dismiss="modal">
                                                                    Cerrar
                                                                </button>
                                                                <a onclick="guardarCambios({{ $producto->id }})" class="btn btn-primary" id="btnGuardarCambios">Guardar</a>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>



                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="alert alert-warning text-center" role="alert">
                                    No hay productos registrados
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/2.1.8/js/jquery.dataTables.min.js"></script>

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
        $(document).ready(function() {
            // Inicializar DataTable
            const table = $('#example').DataTable({
                columnDefs: [{
                    orderable: false,
                    targets: 0
                }],
                order: [
                    [1, 'asc']
                ],
                paging: true,
                pageLength: 10,
                scrollCollapse: true,
                scrollX: true,
                scrollY: 999,
                language: {
                    "decimal": ",",
                    "thousands": ".",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "info": "Mostrando de _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros en total)",
                    "loadingRecords": "Cargando registros...",
                    "zeroRecords": "No se encontraron resultados",
                    "emptyTable": "No hay datos disponibles en la tabla",
                    "paginate": {
                        "first": "Primero",
                        "previous": "Anterior",
                        "next": "Siguiente",
                        "last": "Último"
                    },
                    "aria": {
                        "sortAscending": ": activar para ordenar la columna ascendente",
                        "sortDescending": ": activar para ordenar la columna descendente"
                    }
                }

            });


        });

        var imagenesEliminadas = [];

        function eliminarImagen(pid, id, index) {
            console.log('pid', pid);

            const imagenElement = document.getElementById(`imagen-${index}`);
            if (imagenElement) {
                imagenElement.remove(); // Eliminar el elemento del DOM
            }
            // Añadir el ID a la lista de eliminadas si no está ya en la lista
            if (!imagenesEliminadas.includes(id)) {
                console.log('entra e inserta')
                imagenesEliminadas.push(id);
            }

            // Actualizar el campo oculto con los IDs de las imágenes eliminadas
            document.getElementById('imagenesEliminadas'+pid).value = imagenesEliminadas.join(',');
            console.log('getImagenesEliminadas', imagenesEliminadas);
        }

        function guardarCambios(id)
        {
            document.getElementById('imagenesEliminadas'+id).value = imagenesEliminadas;
            document.getElementById('formEditarProducto' + id).submit();
        }
    </script>
@endsection
