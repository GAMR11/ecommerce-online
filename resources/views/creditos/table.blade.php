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
                        <h5 class="card-header text-center">Creditos Vigentes del cliente {{ $cliente->nombre }}</h5>
                        {{-- <div class="my-3" style="display:flex; justify-content:center; align-items:center;">
                            <a class="btn btn-success" data-bs-target="#registrarCliente" data-bs-toggle="modal"
                                href="#">Agregar</a>
                        </div> --}}

                        <div class="table-responsive text-nowrap">
                            @if (count($creditosVigentes) > 0)
                                <table id="example" class="stripe row-border order-column nowrap" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>#Venta</th>
                                            <th>Total</th>
                                            <th>Saldo Pendiente</th>
                                            <th>Cuota</th>
                                            <th>Pagos</th>
                                            <th>Fecha de Compra</th>
                                            <th>Próximo Pago</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($creditosVigentes as $key => $credito)
                                            <tr style="background-color: {{ $credito['estado_credito'] == 'Atrasado' ? '#f7dc6f' : '#82e0aa' }};">
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $credito['credito']->monto_total }}</td>
                                                <td>{{ $credito['credito']->saldo_pendiente }}</td>
                                                <td>{{ $credito['credito']->monto_cuota }}</td>
                                                <td>{{ $credito['pagos'] }}</td>
                                                <td>{{ $credito['credito']->fecha_compra }}</td>
                                                <td>{{ $credito['proximo_pago'] }}</td>
                                                <td>{{ $credito['estado_credito'] }}</td>
                                                <td>
                                                    <a title="Ver Detalle" href="{{ route('cliente.detalleCredito',['id'=>$credito['credito']->id]) }}"
                                                    class="btn btn-info"><i class="fa-solid fa-circle-info"></i></a>

                                                    @if($credito['estado_credito'] == 'Atrasado')
                                                        <a data-bs-toggle="modal" title="Agregar Mora"
                                                            data-bs-target="#infoModalMora{{ $key }}"
                                                            class="btn btn-danger"><i class="fa-solid fa-money-bill-trend-up"></i>
                                                        </a>
                                                    @endif

                                                    {{-- <a data-bs-toggle="modal"
                                                        data-bs-target="#editarCliente{{ $cliente->id }}"
                                                        class="btn btn-warning"><i class="fas fa-edit"></i></a> --}}

                                                    {{-- <a onclick="if(confirm('¿Estás seguro de que deseas eliminar el cliente?')) { document.getElementById('eliminarCliente{{ $key }}').submit(); }"
                                                        class="btn btn-danger"><i class="fas fa-trash"></i></a> --}}

                                                    {{-- <form id="eliminarCliente{{ $key }}" method="POST"
                                                        action="{{ route('cliente.destroy', $cliente->id) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form> --}}

                                                </td>
                                            </tr>

                                            {{-- MODAL DE DETALLES DEL CLIENTE --}}
                                            <div class="modal fade" id="infoModalMora{{ $key }}" tabindex="-1"
                                                aria-hidden="true">
                                                <form method="POST" action="{{ route('pago.agregarMora') }}">
                                                    @csrf
                                                    <div class="modal-dialog modal-lg" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="exampleModalLabel1">Agregar Mora por Atraso</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                    aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">

                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="nombre" class="form-label">Saldo por Pagar</label>
                                                                        <input type="text" class="form-control"
                                                                            value="{{ $credito['credito']->saldo_pendiente }}" readonly />
                                                                    </div>
                                                                    <div class="col mb-0">
                                                                        <label for="marca"
                                                                            class="form-label">Tiempo Diferido</label>
                                                                        <input type="text" class="form-control"
                                                                            value="{{ $credito['credito']->num_cuotas }} meses" readonly />
                                                                    </div>
                                                                </div>

                                                                <div class="row g-6">
                                                                    <div class="col mb-0">
                                                                        <label for="nombre"
                                                                            class="form-label">Monto de las cuotas</label>
                                                                        <input type="text" class="form-control"
                                                                            value="{{ $credito['credito']->monto_cuota }}" readonly />
                                                                    </div>
                                                                    <div class="col mb-0">
                                                                        <label for="marca"
                                                                            class="form-label">Interes del credito</label>
                                                                        <input type="text" class="form-control"
                                                                            value="{{ $credito['credito']->interes }}" readonly />
                                                                    </div>
                                                                </div>

                                                                <div class="row g-6">
                                                                    <div class="col-6 mb-0">
                                                                        <label for="nombre"
                                                                            class="form-label">Fecha de pago</label>
                                                                        <input type="text" class="form-control"
                                                                            value="el {{ \Carbon\Carbon::parse($credito['credito']->fecha_compra)->format('d') }} de cada mes" readonly />
                                                                    </div>
                                                                    <div class="col-6 mb-0">
                                                                        <label for="nombre"
                                                                            class="form-label">Interés por la Mora ($ USD)</label>
                                                                        <input type="number" name="mora" max="10000" min="0" class="form-control"
                                                                            value="" required />
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" name="credito_id" value="{{ $credito['credito']->id }}">

                                                            </div>

                                                            <div class="modal-footer d-flex justify-content-center">
                                                                <button type="submit" class="btn btn-outline-secondary" title="Al guardar, se actualizará el monto a pagar por el cliente."
                                                                    data-bs-dismiss="modal">
                                                                    Guardar cambios
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>

                                            {{-- <div class="modal fade" id="editarCliente{{ $cliente->id }}" tabindex="-1" aria-hidden="true">
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
                                            </div> --}}
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="alert alert-warning text-center" role="alert">
                                    No se encontraron creditos vigentes.
                                </div>
                            @endif
                        </div>

                        <h5 class="card-header text-center">Creditos Finalizados del cliente {{ $cliente->nombre }}</h5>
                        {{-- <div class="my-3" style="display:flex; justify-content:center; align-items:center;">
                            <a class="btn btn-success" data-bs-target="#registrarCliente" data-bs-toggle="modal"
                                href="#">Agregar</a>
                        </div> --}}

                        <div class="table-responsive text-nowrap">
                            @if (count($creditosFinalizados) > 0)
                                <table id="example2" class="stripe row-border order-column nowrap" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>#Venta</th>
                                            <th>Total</th>
                                            <th>Saldo Pendiente</th>
                                            <th>Cuota</th>
                                            <th>Pagos</th>
                                            <th>Fecha de Compra</th>
                                            <th>Próximo Pago</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($creditosFinalizados as $key => $credito)
                                            <tr style="background-color: {{ $credito['estado_credito'] == 'Atrasado' ? '#f7dc6f' : '#82e0aa' }};">
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $credito['credito']->monto_total }}</td>
                                                <td>{{ $credito['credito']->saldo_pendiente }}</td>
                                                <td>{{ $credito['credito']->monto_cuota }}</td>
                                                <td>{{ $credito['pagos'] }}</td>
                                                <td>{{ $credito['credito']->fecha_compra }}</td>
                                                <td>{{ $credito['proximo_pago'] }}</td>
                                                <td>{{ $credito['estado_credito'] }}</td>
                                                <td>
                                                    <a title="Ver Detalle" href="{{ route('cliente.detalleCredito',['id'=>$credito['credito']->id]) }}"
                                                    class="btn btn-info"><i class="fa-solid fa-circle-info"></i></a>
                                                </td>
                                            </tr>



                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="alert alert-warning text-center" role="alert">
                                    No se encontraron creditos finalizados.
                                </div>
                            @endif
                        </div>

                    </div>
                    <!--/ Bootstrap Dark Table -->
                </div>
            </div>
        </div>
        <!-- / Content -->

        {{-- <div class="modal fade" id="registrarCliente" tabindex="-1" aria-hidden="true">
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
        </div> --}}


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

            const table2 = $('#example2').DataTable({
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
