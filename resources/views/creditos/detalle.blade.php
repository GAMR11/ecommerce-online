@extends('layout')
@section('content')
    <div class="content-wrapper">
        <!-- Content -->

        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-12 d-flex justify-content-center">
                <!-- Form controls -->
                <div class="col-md-12">
                    {{-- <form method="POST" id="formRegisterProduct" action="{{ route('producto.store') }}" enctype="multipart/form-data">
                        @csrf --}}
                        <div class="card">
                            <h5 class="card-header">Detalle del credito</h5>



                            <div class="card-body">
                                <div class="my-3">

                                    <a class="btn btn-secondary" onclick="history.back()"><i class="fa-regular fa-circle-left"></i></a> &nbsp; Regresar

                                </div>


                            <div class="row">
                                <div class="col-6" style="border: dashed; border-width:0.5px; border-radius: 40px; padding:2em 1em;">
                                    <div class="row g-6 my-1">
                                        <div class="texto-titulo" style="display:flex; justify-content:center; align-items:center;"><h5>Datos del Comprador</h5></div>
                                    </div>

                                    <div class="row g-6 my-2">
                                        <div class="col-6 mb-0">
                                            <label for="nombre" class="form-label">Comprador</label>
                                            <input class="form-control" type="text" value="{{ $cliente->nombre }} {{ $cliente->apellidos }} "
                                                name="nombre" id="nombre" readonly  />
                                        </div>
                                        <div class="col-6 mb-0">
                                            <label for="identificacion" class="form-label">Identificación</label>
                                            <input class="form-control" type="text"
                                                name="identificacion" id="identificacion" value="{{ $cliente->identificacion }}" readonly />
                                        </div>
                                        <div class="col-6 mb-0">
                                            <label for="direccion" class="form-label">Dirección</label>
                                            <input class="form-control" type="text" readonly
                                                name="direccion" id="direccion" value="{{ $cliente->direccion }}" />
                                        </div>
                                        <div class="col-6 mb-0">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input class="form-control" type="text" readonly
                                                name="telefono" id="telefono" value="{{ $cliente->telefono }}"  />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6" style="border: dashed; border-width:0.5px; border-radius: 40px; padding:2em 1em;">

                                <div class="row g-6 my-1">
                                    <div class="texto-titulo" style="display:flex; justify-content:center; align-items:center;"><h5>Acuerdo de Pago</h5></div>
                                </div>

                                <div class="row g-6 my-2">
                                    <div class="col-3 mb-0">
                                        <label for="monto_total" class="form-label">Total de Compra</label>
                                        <input class="form-control" type="text" value="{{ $credito->monto_total }} "
                                            name="monto_total" id="monto_total" readonly  />
                                    </div>
                                    <div class="col-3 mb-0">
                                        <label for="saldo_pendiente" class="form-label">Saldo Pendiente</label>
                                        <input class="form-control" type="text"
                                            name="saldo_pendiente" id="saldo_pendiente" value="{{ $credito->saldo_pendiente }}"  readonly/>
                                    </div>
                                    <div class="col-3 mb-0">
                                        <label for="fecha_compra" class="form-label">Fecha de Compra</label>
                                        <input class="form-control" type="text"
                                            name="fecha_compra" id="fecha_compra" value="{{ $credito->fecha_compra }}" readonly />
                                    </div>
                                    <div class="col-3 mb-0">
                                        <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                                        <input class="form-control" type="text"
                                            name="fecha_vencimiento" id="fecha_vencimiento" value="{{ $credito->fecha_vencimiento }}"  readonly/>
                                    </div>
                                </div>

                                <div class="row g-6 my-2">
                                    <div class="col-3 mb-0">
                                        <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                                        <input class="form-control" type="text" style="font-size:0.8em;" value="{{ $fechaPago }} "
                                            name="fecha_pago" id="fecha_pago" readonly  />
                                    </div>
                                    <div class="col-3 mb-0">
                                        <label for="num_cuotas" class="form-label">Número de Cuotas</label>
                                        <input class="form-control" type="text"
                                            name="num_cuotas" id="num_cuotas" value="{{ $credito->num_cuotas }}" readonly />
                                    </div>
                                    <div class="col-3 mb-0">
                                        <label for="estado" class="form-label">Estado del Crédito</label>
                                        <input class="form-control" type="text" readonly
                                            name="estado" id="estado" value="{{ $credito->estado }}" />
                                    </div>
                                    <div class="col-3 mb-0">
                                        <label for="interes" class="form-label">Interes</label>
                                        <input class="form-control" type="text" readonly
                                            name="interes" id="interes" value="{{ $credito->interes }}" />
                                    </div>
                                </div>
                                </div>
                            </div>




                                <div class="row g-6 my-1">
                                    <div class="texto-titulo" style="display:flex; justify-content:center; align-items:center;"><h5>Pagos Realizados</h5></div>
                                </div>

                                <div class="opciones_pagos">
                                    <a data-bs-toggle="modal" data-bs-target="#infoModalPagar" class="btn btn-success"> <i class="fa-solid fa-money-bill-1"></i></a> &nbsp; Registrar Pago 
                                </div>

                                <div class="modal fade" id="infoModalPagar" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel1">Realizar Pago</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form id="formRegistrarPago" action="{{ route('pago.store') }}" method="POST" enctype="multipart/form-data">
                                                <div class="modal-body" id="modalBody">
                                                    @csrf
                                                    <div class="text-center my-3">
                                                        <h5>Datos del Pago</h5>
                                                    </div>
                                                    <div class="row g-6">
                                                        <div class="col mb-0">
                                                            <label for="cuota" class="form-label">Cuota del credito</label>
                                                            <input type="number" class="form-control" id="cuota" value="{{ $credito->monto_cuota }}" readonly />
                                                        </div>
                                                        <div class="col mb-0">
                                                            <label for="codigo_comprobante" class="form-label">Código del comprobante
                                                                (Opcional)</label>
                                                            <input type="text" min='1' max='50' name="codigo_comprobante"
                                                                class="form-control" id="codigo_comprobante" />
                                                        </div>
                                                    </div>

                                                    <div class="row g-6">
                                                        <div class="col mb-0">
                                                            <label for="fecha" class="form-label">Fecha</label>
                                                            <input type="text" class="form-control" value="{{ now()->format('d/m/Y') }}"
                                                                readonly />
                                                        </div>
                                                        <div class="col mb-0">
                                                            <label for="monto" class="form-label">Monto a Pagar</label>
                                                            <input type="number" oninput="validarMontoPagar(this)"  onkeydown="bloquearMenos(event)"  name="monto" class="form-control" min='1' step="0.01"
                                                                value="0" required />
                                                        </div>
                                                    </div>
                                                    <div class="row g-6">
                                                        <div class="col mb-0">
                                                            <label for="fecha" class="form-label">Método de Pago</label>
                                                            <select class="form-control" name="formapago" id="formapago" required>
                                                                <option selected disabled>Seleccionar..</option>
                                                                <option value="Efectivo">Efectivo</option>
                                                                <option value="Transferencia">Transferencia</option>
                                                                <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                                                                <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                                                                <option value="Otro">Otro</option>
                                                            </select>
                                                        </div>
                                                        <div class="col mb-0">
                                                            <label for="fecha" class="form-label">Imagen o Comprobante del Pago</label>
                                                            <input type="file" accept="image/*" class="form-control" name="abonoimagen"
                                                                required />
                                                        </div>

                                                    </div>

                                                    <div class="row g-6">
                                                        <div class="col mb-0">
                                                            <label for="fecha" class="form-label">Comentarios</label>
                                                            <textarea class="form-control" name="comentario" id="comentario" cols="30" rows="3"></textarea>
                                                        </div>
                                                    </div>

                                                    <input type="hidden" name="kardex_cliente_id" id="kardex_cliente_id" value="{{ $credito->id }}">

                                                    <div class="modal-footer d-flex justify-content-center my-3">
                                                        <button type="submit"
                                                        {{-- id="btnSubmitFormPagar" --}}
                                                         class="btn btn-outline-secondary">
                                                            Pagar
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>



                                </div>

                                <div class="table-responsive text-nowrap my-5">
                                    {{-- @if (count($infoCreditos) > 0) --}}
                                        <table id="example2" class="stripe row-border order-column nowrap" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>#Pago</th>
                                                    <th>Cobrador</th>
                                                    <th>Fecha</th>
                                                    <th>Monto</th>
                                                    <th>Saldo</th>
                                                    <th>Tipo</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($historialPagos as $key => $infoPago)
                                                    <tr>
                                                        <td>{{ $key + 1 }}</td>
                                                        <td>{{ $infoPago->usuario->name }}</td>
                                                        <td>{{ $infoPago->fecha_pago }}</td>
                                                        <td>{{ $infoPago->monto_pagado }}</td>
                                                        <td>{{ $infoPago->saldo_restante }}</td>
                                                        <td>{{ $infoPago->metodo_pago }}</td>
                                                        <td>{{ $infoPago->estado_pago }}</td>
                                                        <td>
                                                            @if($infoPago->estado_pago == 'Recibido' || $infoPago->estado_pago == 'Completado')
                                                            <a data-bs-toggle="modal" title="Ver Imagen del Pago"
                                                                data-bs-target="#infoModal{{ $infoPago->id }}"
                                                            class="btn btn-info"><i class="fa-solid fa-money-bill-transfer"></i></a>
                                                            @else
                                                            <span><strong>Sin comprobante</strong></span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <div class="modal fade" id="infoModal{{ $infoPago->id }}" tabindex="-1"
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
                                                                        @if (isset($infoPago->imagen))
                                                                            @if(isset($infoPago->imagen->pluck('url')[0]))
                                                                            <img src="{{ $infoPago->imagen->pluck('url')[0] }}" alt="Imagen"
                                                                                class="img-fluid rounded-start"
                                                                                style="max-width: 100%;">
                                                                            @else
                                                                            <img src="" alt="Imagen"
                                                                            class="img-fluid rounded-start"
                                                                            style="max-width: 100%;">
                                                                            @endif
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
                                                @endforeach
                                            </tbody>
                                        </table>
                                    {{-- @else
                                        <div class="alert alert-warning text-center" role="alert">
                                            No se encontraron compras realizadas por el cliente.
                                        </div>
                                    @endif --}}
                                </div>




                                <div class="row g-6 my-1">
                                    <div class="texto-titulo" style="display:flex; justify-content:center; align-items:center;"><h5>Artículos Comprados</h5></div>
                                </div>


                                <div class="table-responsive text-nowrap my-5">
                                    {{-- @if (count($infoCreditos) > 0) --}}
                                        <table id="example" class="stripe row-border order-column nowrap" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>#Venta</th>
                                                    <th>Artículo</th>
                                                    <th>Marca</th>
                                                    <th>Modelo</th>
                                                    <th>Cantidad</th>
                                                    <th>Precio</th>
                                                    <th>Subtotal</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($venta->detalles as $key => $detalle)
                                                    <tr>
                                                        <td>{{ $key + 1 }}</td>
                                                        <td>{{ $detalle->inventario->producto->nombre }}</td>
                                                        <td>{{ $detalle->inventario->producto->marca }}</td>
                                                        <td>{{ $detalle->inventario->producto->modelo }}</td>
                                                        <td>{{ $detalle->cantidad }}</td>
                                                        <td>{{ $detalle->precio_unitario }}</td>
                                                        <td>{{ $detalle->cantidad * $detalle->precio_unitario }}</td>
                                                        <td>
                                                            <a
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#infoModal{{ $detalle->inventario->producto->id }}"
                                                            title="Ver Detalle"
                                                            class="btn btn-info"><i class="fa-regular fa-newspaper"></i></a>
                                                        </td>
                                                    </tr>

                                                     {{-- MODAL DE DETALLES DEL PRODUCTO --}}
                                            <div class="modal fade" id="infoModal{{ $detalle->inventario->producto->id }}" tabindex="-1"
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
                                                                    <input type="text" class="form-control" readonly
                                                                        value="{{ $detalle->inventario->producto->nombre }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="marca" class="form-label">Marca</label>
                                                                    <input type="text" class="form-control" readonly
                                                                        value="{{ $detalle->inventario->producto->marca }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="modelo" class="form-label">Modelo</label>
                                                                    <input type="text" class="form-control" readonly
                                                                        value="{{ $detalle->inventario->producto->modelo }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="color" class="form-label">Color</label>
                                                                    <input type="text" class="form-control" readonly
                                                                        value="{{ $detalle->inventario->producto->color }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="modelo"
                                                                        class="form-label">Series/Código</label>

                                                                    <select class="form-control" readonly>
                                                                        @foreach ($detalle->inventario->producto->inventarios as $inventario)
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
                                                                        value="{{ $detalle->inventario->producto->categoria->nombre }}"
                                                                        readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="precio_original" class="form-label">Precio
                                                                        Original</label>

                                                                    <input type="number" name="precio_original"
                                                                        class="form-control"
                                                                        value="{{ $detalle->inventario->producto->precio_original }}" readonly />
                                                                </div>
                                                                <div class="col mb-0">
                                                                    <label for="precio_contado" class="form-label">Precio
                                                                        Contado</label>

                                                                    <input type="number" name="precio_contado"
                                                                        class="form-control"
                                                                        value="{{ $detalle->inventario->producto->precio_contado }}" readonly />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="precio_credito" class="form-label">Precio
                                                                        Credito</label>
                                                                    <input type="number" class="form-control" readonly
                                                                        value="{{ $detalle->inventario->producto->precio_credito }}" />
                                                                </div>
                                                            </div>
                                                            <div class="row g-6">
                                                                <div class="col mb-0">
                                                                    <label for="descripcion"
                                                                        class="form-label">Descripción</label>
                                                                    <textarea class="form-control" cols="30" rows="6" readonly>{{ $detalle->inventario->producto->descripcion }}</textarea>
                                                                </div>
                                                            </div>
                                                            <div class="row g-6 my-2">
                                                                <div class="container-imagenes"
                                                                    style="align-items: center;display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;gap:1em;">
                                                                    @if ($detalle->inventario->producto->imagenes()->count() > 0)
                                                                        @for ($i = 0; $i < count($detalle->inventario->producto->imagenes); $i++)
                                                                            <div class="col-md-4">
                                                                                <img src="{{ $detalle->inventario->producto->imagenes[$i]->url }}"
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

                                                @endforeach
                                            </tbody>
                                        </table>
                                    {{-- @else
                                        <div class="alert alert-warning text-center" role="alert">
                                            No se encontraron compras realizadas por el cliente.
                                        </div>
                                    @endif --}}
                                </div>
{{--
                                <div class="mb-4 my-1">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" name="descripcion" id="descripcion" rows="6"></textarea>
                                </div> --}}


                                {{-- <div class="mb-4">
                                    <label for="preciocredito" class="form-label">Cargar Imagenes</label>
                                    <input class="form-control" accept="image/*" type="file" name="imagenes[]"
                                        multiple id="imagenes" />
                                </div> --}}

                                {{-- <div class="d-flex justify-content-center align-items-center">
                                    <button id="submit-btn" class="btn btn-primary my-2 mx-2" onclick="enviarForm()" disabled>Enviar</button>
                                </div> --}}

                                <div class="my-3">

                                    <a class="btn btn-secondary" onclick="history.back()"><i class="fa-regular fa-circle-left"></i></a> &nbsp; Regresar

                                </div>

                            </div>



                        </div>



                    {{-- </form> --}}
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


        document.getElementById('btnSubmitFormPagar').addEventListener('click',function()
        {
            document.getElementById('formRegistrarPago').submit();
        })


        });

        function validarMontoPagar(input)
        {
            if (input.value < 0) {
                input.value = 0; // Si el usuario ingresa un número negativo, lo cambia a 0
            }
        }
        function bloquearMenos(event) {
    if (event.key === '-' || event.key === 'e' || event.key === '+') {
        event.preventDefault(); // Evita que el usuario escriba "-"
    }
}
    </script>
@endsection
