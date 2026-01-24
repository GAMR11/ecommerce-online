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
                        <h5 class="card-header">Historial de Ventas</h5>
                        <div class="table-responsive text-nowrap">
                            @if (count($historialVentas) > 0)
                                <table id="example" class="stripe row-border order-column nowrap" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Vendedor</th>
                                            <th>Cliente</th>
                                            <th>Fecha</th>
                                            <th>Total</th>
                                            <th>Estado</th>
                                            <th>Detalle</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($historialVentas as $key => $hv)
                                            <tr id="id-venta-{{ $key }}" data-id="{{ $key }}"
                                                data-product="{{ json_encode($hv) }}">
                                                <td>{{ $key }}</td>
                                                <td>{{ $hv['venta']['vendedor'] }}</td>
                                                <td>{{ $hv['cliente']['nombre'] }} {{ $hv['cliente']['apellidos'] }} </td>
                                                <td>{{ $hv['venta']['created_at']->format('d-m-Y H:i:s') }}</td>
                                                <td>{{ $hv['venta']['total'] }}</td>
                                                <td>{{ $hv['venta']['estado']}}</td>
                                                <td>
                                                    <a data-bs-toggle="modal"
                                                        data-bs-target="#infoModal{{ $key }}"
                                                        class="btn btn-primary"><i class="fas fa-eye"></i></a>
                                                </td>
                                                <td>
                                                    <a class="btn btn-info" title="Ver cliente" data-bs-toggle="modal" data-bs-target="#infoModalCliente{{ $key }}"><i class="fas fa-eye"></i></a>
                                                    <a data-bs-toggle="modal"
                                                        data-bs-target="#editarModal{{ $key }}"
                                                        class="btn btn-warning"><i class="fas fa-edit"></i></a>

                                                        <a onclick="if(confirm('¿Estás seguro de que deseas eliminar esta venta?')) { document.getElementById('eliminarVenta{{ $key }}').submit(); }" class="btn btn-danger"><i class="fas fa-trash"></i></a>

                                                        <form id="eliminarVenta{{ $key }}" method="POST" action="{{ route('venta.destroy',$hv['venta']['id']) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>

                                                </td>
                                            </tr>


                                            {{-- MODAL DE DETALLES DE LA VENTA --}}
                                            <div class="modal fade" id="infoModal{{ $key }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel1">Detalle de la
                                                                venta</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            @if($hv['venta']['comentario'] != null)
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="modelo"
                                                                        class="form-label">Comentario</label>
                                                                    <textarea class="form-control" name="comentario" cols="30" rows="6" readonly>{{ $hv['venta']['comentario'] }}</textarea>
                                                                </div>
                                                            </div>
                                                            @endif

                                                            @foreach($hv['detalles'] as $keyDetalle => $detalle)
                                                            <div class="text-center my-1"><h5>Artículo #{{ $keyDetalle + 1 }}</h5></div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="nombre" class="form-label">Nombre</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $detalle['producto']['nombre'] }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="marca" class="form-label">Marca</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $detalle['producto']['marca'] }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="modelo" class="form-label">Modelo</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $detalle['producto']['modelo'] }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="color" class="form-label">Color</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $detalle['producto']['color'] }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="modelo" class="form-label">Serie</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $detalle['numero_serie'] }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="color" class="form-label">Categoría</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $detalle['producto']['categoria']['nombre'] }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="modelo" class="form-label">Precio</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $detalle['precio_unitario'] }}" readonly />
                                                                </div>
                                                            </div>


                                                            @endforeach
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

                                            {{-- MODAL PARA EDITAR DATOS DE LA VENTA --}}
                                            <div class="modal fade" id="editarModal{{ $key }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <form id="formEditarProducto{{ $key }}" method="POST" action="{{ route('venta.update',$hv['venta']['id']) }}" enctype="multipart/form-data">
                                                        @csrf
                                                        {{ method_field('PUT') }}
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="exampleModalLabel1">Editar Datos de la Venta</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">


                                                                <div class="row g-6">


                                                                    <div class="col mb-0">
                                                                        <label for="modelo"
                                                                            class="form-label">Estado de la Venta</label>
                                                                        <select class="form-control" name="estado">
                                                                            <option selected disabled>Seleccionar..</option>
                                                                                <option value="Por entregar" {{ $hv['venta']['estado'] == 'Por entregar' ? 'selected' : '' }}>Por Entregar</option>
                                                                                <option value="Entregado" {{ $hv['venta']['estado'] == 'Entregado' ? 'selected' : '' }}>Entregado</option>
                                                                        </select>
                                                                    </div>

                                                                    {{-- pendiente --}}

                                                                </div>

                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="modelo"
                                                                            class="form-label">Comentario</label>
                                                                        <textarea class="form-control" name="comentario" cols="30" rows="6">{{ $hv['venta']['comentario'] }}</textarea>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                            <div class="modal-footer d-flex justify-content-center " style="gap:1em;">
                                                                <button type="button" class="btn btn-outline-secondary"
                                                                    data-bs-dismiss="modal">
                                                                    Cerrar
                                                                </button>
                                                                <button type="submit" class="btn btn-primary" id="btnGuardarCambios">Guardar</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                            {{-- MODAL DE DETALLES DEL CLIENTE --}}
                                            <div class="modal fade" id="infoModalCliente{{ $key }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel1">Información del Cliente</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">


                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="nombre" class="form-label">Nombre</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $hv['kardex_cliente']['cliente']['nombre'] }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="marca" class="form-label">Apellidos</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $hv['kardex_cliente']['cliente']['apellidos'] }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="nombre" class="form-label">Identificación</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $hv['kardex_cliente']['cliente']['identificacion'] }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="marca" class="form-label">Dirección</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $hv['kardex_cliente']['cliente']['direccion'] }}" readonly />
                                                                </div>
                                                            </div>

                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="nombre" class="form-label">Telefono</label>
                                                                    <input type="text" class="form-control"
                                                                        value="{{ $hv['kardex_cliente']['cliente']['telefono'] }}" readonly />
                                                                </div>
                                                            </div>

                                                            {{-- @if(count($hv['historial_pagos']) > 0)
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

                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="alert alert-warning text-center" role="alert">
                                    No hay ventas registradas
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.13.4/sorting/datetime-moment.js"></script>

    <script>
        $(document).ready(function()
        {
            // Inicializar DataTable
            const table = $('#example').DataTable({
                columnDefs: [{
                    orderable: false,
                    targets: 0
                }],
                order: [
                    [1, 'desc']
                ], // Ordenar por la columna de fecha
                paging: true,
                pageLength: 6,
                scrollCollapse: true,
                scrollX: true,
                scrollY: 300,
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





    </script>
@endsection
