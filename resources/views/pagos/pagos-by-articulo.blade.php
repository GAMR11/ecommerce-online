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
                        <h5 class="card-header">Pagos de la compra realizada</h5>
                        <div class="table-responsive text-nowrap">
                            @if (count($historialPagos) > 0)
                                <table id="example" class="stripe row-border order-column nowrap" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Cobrador</th>
                                            <th>Cliente</th>
                                            <th>Fecha</th>
                                            <th>Monto</th>
                                            <th>Tipo</th>
                                            <th>Saldo</th>
                                            <th>Estado</th>
                                            <th>Comentario</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($historialPagos as $key => $pago)
                                            <tr id="id-pago-{{ $pago->id }}" data-id="{{ $pago->id }}"
                                                data-product="{{ json_encode($pago) }}">
                                                <td>{{ $pago->usuario->name }}</td>
                                                <td>{{ $pago->cliente->nombre }} {{ $pago->cliente->apellidos }}</td>
                                                <td>{{ $pago->fecha_pago }}</td>
                                                <td>{{ $pago->monto_pagado }}</td>
                                                <td>{{ $pago->metodo_pago }}</td>
                                                <td>{{ $pago->saldo_restante }}</td>
                                                <td>{{ $pago->estado_pago == '1' ? 'Pendiente' : 'Recibido' }}</td>
                                                <td>{{ $pago->comentarios }}</td>
                                                <td>

                                                    @if($pago->imagen && $pago->estado_pago == 'Recibido')
                                                        <a data-bs-toggle="modal" title="Ver Imagen del Pago"
                                                        data-bs-target="#infoModal{{ $pago->id }}"
                                                        class="btn btn-primary"><i class="fas fa-eye"></i></a>

                                                        <a title="Descargar Recibo del Pago" target="_blank"
                                                        href="{{ route('pago.download', $pago->id) }}"
                                                        class="btn btn-danger"><i class="fas fa-download"></i></a>
                                                    @else
                                                        <a data-bs-toggle="modal" title="Cargar Imagen del Pago"
                                                        data-bs-target="#editarModal{{ $pago->id }}"
                                                        class="btn btn-warning"><i class="fas fa-edit"></i></a>
                                                    @endif
                                                    {{--


                                                    --}}
                                                    {{-- @if (isset($pago->imagen))

                                                            --}}
                                                    {{-- @else --}}

                                                    {{-- @endif --}}



                                                    {{-- <a onclick="if(confirm('¿Estás seguro de que deseas eliminar este pago?')) { document.getElementById('eliminarProducto{{ $key }}').submit(); }" class="btn btn-danger"><i class="fas fa-trash"></i></a>

                                                        <form id="eliminarProducto{{ $key }}" method="POST" action="{{ route('producto.destroy',$pago->id) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form> --}}

                                                </td>
                                            </tr>

                                            <div class="modal fade" id="infoModal{{ $pago->id }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel1">Imagen del Pago
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">


                                                            <div class="row w-100">
                                                                @if (isset($pago->imagen))
                                                                    <img src="{{ $pago->imagen->url }}" alt="Imagen"
                                                                        class="img-fluid rounded-start"
                                                                        style="max-width: 100%;">
                                                                @endif
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

                                            {{-- Editar el pago --}}

                                            <div class="modal fade" id="editarModal{{ $pago->id }}" tabindex="-1"
                                                aria-hidden="true">
                                                <form action="{{ route('pago.actualizarPago') }}" method="POST"
                                                    enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="modal-dialog modal-sm" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="exampleModalLabel1">Cargar
                                                                    Imagen del Pago</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">

                                                                <div class="row w-100 d-flex justify-content-center">
                                                                    <div class="">
                                                                        <label for="imagenabono" class="form-label">Imagen
                                                                            del pago o comprobante</label>
                                                                        <input type="file" required accept="image/*"
                                                                            class="form-control" name="imagenabono"
                                                                            id="imagenabono" />
                                                                    </div>


                                                                    <input type="hidden" name="pago_id"
                                                                        value="{{ $pago->id }}">

                                                                </div>

                                                                <div class="row g-6">
                                                                     <!-- Estado del pago en una nueva fila -->
                                                                     <div class="col-12" style="margin-top:2em;">
                                                                        <label for="estado" class="form-label">Estado del pago</label>
                                                                        <select name="estado" class="form-control">
                                                                            <option selected disabled>Seleccionar..</option>
                                                                            <option value="Pendiente" {{ $pago->estado_pago == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                                                                            <option value="Recibido" {{ $pago->estado_pago == 'Recibido' ? 'selected' : '' }}>Recibido</option>
                                                                        </select>
                                                                    </div>

                                                                    <!-- Comentarios ocupando todo el ancho -->
                                                                    <div class="col-12 my-2">
                                                                        <label for="comentarios" class="form-label">Comentario</label>
                                                                        <textarea name="comentarios" class="form-control" cols="10" rows="6">{{ $pago->comentarios }}</textarea>
                                                                    </div>


                                                                </div>


                                                                <div class="modal-footer d-flex justify-content-center">

                                                                    <button type="submit"
                                                                        class="btn btn-outline-success mx-2 my-2">
                                                                        Guardar
                                                                    </button>

                                                                    <button type="button" class="btn btn-outline-secondary"
                                                                        data-bs-dismiss="modal">
                                                                        Cerrar
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                </form>
                                            </div>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="alert alert-warning text-center" role="alert">
                                    No hay pagos registrados aún.
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
