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
                        <h5 class="card-header">Clientes</h5>
                        <div class="my-3" style="display:flex; justify-content:center; align-items:center;">
                            <a class="btn btn-success" data-bs-target="#registrarCliente" data-bs-toggle="modal"
                                href="#">Agregar</a>
                        </div>

                        <div class="table-responsive text-nowrap">
                            @if (count($clientes) > 0)
                                <table id="example" class="stripe row-border order-column nowrap" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Apellido</th>
                                            <th>Identificación</th>
                                            <th>Teléfono</th>
                                            <th>Dirección</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($clientes as $key => $cliente)
                                            <tr id="id-cliente-{{ $cliente->id }}" data-id="{{ $cliente->id }}"
                                                data-product="{{ json_encode($cliente) }}">
                                                <td>{{ $cliente->nombre }}</td>
                                                <td>{{ $cliente->apellidos }}</td>
                                                <td>{{ $cliente->identificacion }}</td>
                                                <td>{{ $cliente->telefono }}</td>
                                                <td>{{ $cliente->direccion }}</td>
                                                <td>
                                                    <a title="Ver Creditos" href="{{ route('cliente.creditos',['id'=>$cliente->id]) }}"
                                                    class="btn btn-outline-success"><i class="fa-solid fa-file-invoice-dollar"></i></a>

                                                    <a data-bs-toggle="modal"
                                                        data-bs-target="#infoModalCliente{{ $cliente->id }}"
                                                        class="btn btn-primary"><i class="fas fa-eye"></i></a>
                                                    <a data-bs-toggle="modal"
                                                        data-bs-target="#editarCliente{{ $cliente->id }}"
                                                        class="btn btn-warning"><i class="fas fa-edit"></i></a>

                                                    <a onclick="if(confirm('¿Estás seguro de que deseas eliminar el cliente?')) { document.getElementById('eliminarCliente{{ $key }}').submit(); }"
                                                        class="btn btn-danger"><i class="fas fa-trash"></i></a>

                                                    <form id="eliminarCliente{{ $key }}" method="POST"
                                                        action="{{ route('cliente.destroy', $cliente->id) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>

                                                </td>
                                            </tr>

                                            {{-- MODAL DE DETALLES DEL CLIENTE --}}
                                            <div class="modal fade" id="infoModalCliente{{ $cliente->id }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel1">Información del
                                                                Cliente</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">


                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="nombre" class="form-label">Nombre</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $cliente->nombre }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="marca"
                                                                        class="form-label">Apellidos</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $cliente->apellidos }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="nombre"
                                                                        class="form-label">Identificación</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $cliente->identificacion }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="marca"
                                                                        class="form-label">Dirección</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $cliente->direccion }}" readonly />
                                                                </div>
                                                            </div>

                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="nombre"
                                                                        class="form-label">Telefono</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $cliente->telefono }}" readonly />
                                                                </div>
                                                            </div>

                                                            @if (isset($cliente->garante))
                                                                <div class="text-center my-3">
                                                                    <h5>Datos del Garante</h5>
                                                                </div>
                                                            @endif

                                                            {{-- @if (count($hv['historial_pagos']) > 0)
                                                            <div class="text-center my-3"><h5>Historial de Pagos</h5></div>
                                                            @endif

                                                            @foreach ($hv['historial_pagos'] as $key => $historialPago)
                                                                <div class="text-center my-1"><h5>Pago #{{ $key + 1 }}</h5></div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="modelo"
                                                                        class="form-label">Fecha</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $historialPago['fecha_pago'] }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="modelo"
                                                                        class="form-label">Monto</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $historialPago['monto_pagado'] }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="modelo"
                                                                        class="form-label">Forma de Pago</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $historialPago['metodo_pago'] }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="modelo"
                                                                        class="form-label">Comentarios</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $historialPago['comentarios'] }}" readonly />
                                                                </div>
                                                            </div>
                                                            @endforeach --}}

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
                                            {{-- <div class="modal fade" id="infoModal{{ $producto->id }}" tabindex="-1"
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
                                                                        @for ($i = 0; $i < count($producto->imagenes) / count($producto->inventarios); $i++)
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
                                            </div> --}}

                                            <div class="modal fade" id="editarCliente{{ $cliente->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <form id="formGuardarCliente" method="POST" action="{{ route('cliente.update', $cliente->id) }}"
                                                        enctype="multipart/form-data">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="exampleModalLabel1">Editar Cliente</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                    aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">

                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="nombre" class="form-label">Nombre</label>
                                                                        <input type="text" name="nombre" minlength="3" maxlength="40"
                                                                            class="form-control" value="{{ $cliente->nombre }}" required/>
                                                                    </div>
                                                                    <div class="col mb-0">
                                                                        <label for="apellidos" class="form-label">Apellidos</label>
                                                                        <input type="text" name="apellidos" minlength="3" maxlength="40"
                                                                            class="form-control" value="{{ $cliente->apellidos }}" required/>
                                                                    </div>
                                                                </div>

                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="identificacion" class="form-label">Identificación</label>
                                                                        <input name="identificacion" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                                            type="text" minlength="10"maxlength="13" value="{{ $cliente->identificacion }}" class="form-control" required/>
                                                                    </div>
                                                                    <div class="col mb-0">
                                                                        <label for="telefono" class="form-label">Teléfono</label>
                                                                        <input name="telefono" type="text" minlength="10" maxlength="10" id="telefono"
                                                                        value="{{ $cliente->telefono }}"
                                                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" required/>
                                                                    </div>
                                                                </div>

                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="direccion" class="form-label">Dirección</label>
                                                                        <input name="direccion"
                                                                            type="text" value="{{ $cliente->direccion }}" class="form-control" required/>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer d-flex justify-content-center " style="gap:1em;">
                                                                <a type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                                    Cerrar
                                                                </a>
                                                                <button type="submit" class="btn btn-primary">Guardar</button>
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
                                    No hay clientes registrados
                                </div>
                            @endif
                        </div>
                    </div>
                    <!--/ Bootstrap Dark Table -->
                </div>
            </div>
        </div>
        <!-- / Content -->

        <div class="modal fade" id="registrarCliente" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form id="formGuardarCliente" method="POST" action="{{ route('cliente.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel1">Registrar Cliente</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">

                            <div class="row g-6">
                                <div class="col mb-0">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" name="nombre" minlength="3" maxlength="40"
                                        class="form-control" required/>
                                </div>
                                <div class="col mb-0">
                                    <label for="apellidos" class="form-label">Apellidos</label>
                                    <input type="text" name="apellidos" minlength="3" maxlength="40"
                                        class="form-control" required/>
                                </div>
                            </div>

                            <div class="row g-6">
                                <div class="col mb-0">
                                    <label for="identificacion" class="form-label">Identificación</label>
                                    <input name="identificacion" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        type="text" minlength="10"maxlength="13" class="form-control" required/>
                                </div>
                                <div class="col mb-0">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input name="telefono" type="text" minlength="10" maxlength="10" id="telefono"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" required/>
                                </div>
                            </div>

                            <div class="row g-6">
                                <div class="col mb-0">
                                    <label for="direccion" class="form-label">Dirección</label>
                                    <input name="direccion"
                                        type="text" class="form-control" required/>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex justify-content-center " style="gap:1em;">
                            <a type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Cerrar
                            </a>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <!-- Footer -->
        @include('templates.footer')
        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/2.1.8/js/jquery.dataTables.min.js"></script>

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
            document.getElementById('imagenesEliminadas' + pid).value = imagenesEliminadas.join(',');
            console.log('getImagenesEliminadas', imagenesEliminadas);
        }

        function guardarCambios(id) {
            document.getElementById('imagenesEliminadas' + id).value = imagenesEliminadas;
            document.getElementById('formEditarProducto' + id).submit();
        }
    </script>
@endsection
