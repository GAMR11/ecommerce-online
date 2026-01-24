@extends('layout')
@section('content')
    <div class="content-wrapper">

        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-6">
                <div class="col-md-12">
                    <div class="card overflow-hidden">
                        <h5 class="card-header">Sección de Consulta y Registro de Pagos</h5>
                        <label for="nombre" class="form-label mx-5">Ingrese la identificación del cliente que va a pagar o consultar</label>

                        <div class="buscador-cliente w-25 mx-auto my-3" style="display:flex; flex-direction:row; align-items:center; justify-content:center;gap:1em;">
                            <div class="contenedor-buscador-cliente">
                                {{-- <label for="identificacion">Buscar por Identificación</label> --}}
                                <input type="search" class="form-control" id="identificacion" placeholder="Ingresar la identificación">
                            </div>
                            <div>
                                <a id="btn-buscar-cliente" class="btn btn-outline-info"><i class="fas fa-search"></i></a>
                            </div>
                        </div>

                        {{-- <table id="example" class="stripe row-border order-column nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="visibility:hidden;"></th>
                                    <th>Identificación</th>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Teléfono</th>
                                    <th>Dirección</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($clientes as $cliente)
                                    <tr id="id-cliente-{{ $cliente->id }}" data-id="{{ $cliente->id }}"
                                        data-product="{{ json_encode($cliente) }}">
                                        <td><input type="checkbox" class="select-checkbox"></td>
                                        <td>{{ $cliente->identificacion }}</td>
                                        <td>{{ $cliente->nombre }}</td>
                                        <td>{{ $cliente->apellidos }}</td>
                                        <td>{{ $cliente->telefono }}</td>
                                        <td>{{ $cliente->direccion }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table> --}}

                        <div id="selected-products">
                            <!-- Aquí se mostrarán los productos seleccionados -->
                            {{-- <button class="btn btn-primary" id="btnGuardar">Continuar</button> --}}
                        </div>

                        <div id="compras" style="display: none;">
                            <table id="comprascliente" class="table table-striped table-dark" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#Venta</th>
                                        <th>Total</th>
                                        <th>Saldo Pendiente</th>
                                        <th>Articulos Comprados</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="bodycompras">

                                </tbody>
                            </table>
                        </div>

                        <div id="texto-no-encontrado" style="display:none;" class="alert alert-warning text-center" role="alert">
                            No hay ventas registradas para este cliente.
                        </div>

                    </div>
                </div>
            </div>








        </div>




        <div class="modal fade" id="infoModalPagos" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel1">Historial de Pago</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="modalBody">

                    </div>
                    <div class="modal-footer d-flex justify-content-center">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="infoModalPagar" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel1">Realizar Pago</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('pago.store') }}" method="POST" enctype="multipart/form-data">
                        <div class="modal-body" id="modalBody">
                            @csrf
                            <div class="text-center my-3">
                                <h5>Datos del Pago</h5>
                            </div>
                            <div class="row g-6">
                                <div class="col mb-0">
                                    <label for="cuota" class="form-label">Cuota</label>
                                    <input type="number" class="form-control" id="cuota" readonly />
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
                                    <label for="monto" class="form-label">Monto</label>
                                    <input type="number" name="monto" class="form-control" min='1' step="0.01"
                                        value="0" required />
                                </div>
                            </div>
                            <div class="row g-6">
                                <div class="col mb-0">
                                    <label for="fecha" class="form-label">Método de Pago</label>
                                    <select class="form-control" name="formapago" id="formapago" required>
                                        {{-- <option selected disabled>Seleccionar..</option> --}}
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
                                    <textarea class="form-control" name="comentario" id="comentario" cols="30" rows="6"></textarea>
                                </div>
                            </div>

                            <input type="hidden" name="venta_id" id="venta_id" value="">
                            <input type="hidden" name="kardex_cliente_id" id="kardex_cliente_id" value="">

                            <div class="modal-footer d-flex justify-content-center my-3">
                                <button type="submit" class="btn btn-outline-secondary">
                                    Pagar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>



        </div>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/2.1.8/js/jquery.dataTables.min.js"></script>


        <script>
            $(document).ready(function() {

                const selectedProducts = {}; // Objeto para almacenar los productos seleccionados por su ID

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
                    pageLength: 5,
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

                document.getElementById('btn-buscar-cliente').addEventListener('click', buscarCliente);

                function buscarCliente() {
                    const input = document.getElementById('identificacion');
                    console.log('input:',input)
                    const valor = input.value;
                    console.log('valor',valor);

                    $('#compras').css('display','block');

                    $.ajax({
                        url: 'compras-by-cliente',
                        method: 'GET',
                        data: {
                            identificacion: valor,
                        },
                        success: function(data)
                        {
                            // console.log('info:', data)
                            if(data.status)
                            {
                                $('#texto-no-encontrado').css('display','none');
                                // alert('Cliente encontrado');
                                $('#bodycompras').empty();

                                var infoVenta = data.kardexVentas;

                                infoVenta.forEach(function(venta,i) {
                                    var detalleVentas = '';

                                    // Generar los detalles de los productos en la venta
                                    venta.detalles.forEach(detalle => {
                                        detalleVentas +=
                                            `${detalle.cantidad} ${detalle.producto} \n`;
                                    });

                                    var urlPagosByArticulo =
                                        "{{ route('pago.pagosByArticulo', ['client_id' => ':clientId', 'kardex_id' => ':kardexId']) }}";
                                    urlPagosByArticulo = urlPagosByArticulo.replace(':clientId',
                                            data.cliente.id)
                                        .replace(':kardexId', venta.kardex_cliente.id);
                                    console.log('urlPagosByArticulo', urlPagosByArticulo)

                                    // Crear la fila para cada venta
                                    var fila = `<tr>
                                        <td>${i+1}</td>
                                        <td>${venta.venta.total}</td>
                                        <td>${venta.kardex_cliente.saldo_pendiente}</td>
                                        <td>${detalleVentas}</td>
                                        <td>
                                            <a title="Ver Recientes" class="btn btn-info ver-pagos" data-bs-toggle="modal" data-bs-target="#infoModalPagos" data-historial='${JSON.stringify(venta.historial_pagos)}'>
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a title="Ver Todos" class="btn btn-warning" href="${urlPagosByArticulo}">
                                                <i class="fas fa-search"></i>
                                            </a>
                                            <a title="Realizar Pago" class="btn btn-success ver-pagos" data-cuota="${venta.kardex_cliente.monto_cuota}" data-venta-id="${venta.venta.id}" data-kardex-cliente-id="${venta.kardex_cliente.id}" data-bs-toggle="modal" data-bs-target="#infoModalPagar" data-historial='${JSON.stringify(venta.historial_pagos)}'>
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                        </td>
                                    </tr>`;

                                    $('#bodycompras').append(fila);
                                });

                                // Evento para manejar el clic en "Ver Pagos"
                                $('.ver-pagos').on('click', function(e) {
                                    e.preventDefault();

                                    // Obtener los datos de venta y kardex_cliente del atributo `data-*`
                                    let ventaId = $(this).data('venta-id');
                                    let kardexClienteId = $(this).data('kardex-cliente-id');
                                    let cuota = $(this).data('cuota');

                                    // Actualizar los campos ocultos con los valores seleccionados
                                    $('#venta_id').val(ventaId);
                                    $('#kardex_cliente_id').val(kardexClienteId);
                                    $('#cuota').val(cuota);

                                    // Obtener el historial de pagos del atributo `data-historial`
                                    let historialPagos = $(this).data('historial');

                                    // Limpiar el contenido previo del modal
                                    $('#modalBody').empty();

                                    // Generar el contenido del historial de pagos en el modal
                                    if (historialPagos && historialPagos.length > 0) {
                                        historialPagos.forEach((pago, index) => {
                                            let pagoHTML = `
                                    <div class="text-center my-1">
                                        <h5>Pago #${index + 1}</h5>
                                    </div>
                                    <div class="row g-6">
                                        <div class="col mb-0">
                                            <label for="fecha" class="form-label">Fecha</label>
                                            <input type="text" class="form-control" value="${pago.fecha_pago}" readonly />
                                        </div>
                                        <div class="col mb-0">
                                            <label for="monto" class="form-label">Monto</label>
                                            <input type="text" class="form-control" value="${pago.monto_pagado}" readonly />
                                        </div>
                                    </div>
                                    <div class="row g-6">
                                        <div class="col mb-0">
                                            <label for="metodo_pago" class="form-label">Forma de Pago</label>
                                            <input type="text" class="form-control" value="${pago.metodo_pago}" readonly />
                                        </div>
                                        <div class="col mb-0">
                                            <label for="comentarios" class="form-label">Comentarios</label>
                                            <input type="text" class="form-control" value="${pago.comentarios}" readonly />
                                        </div>
                                    </div>
                                    <hr />`;

                                                    $('#modalBody').append(pagoHTML);
                                                });
                                            } else {
                                                $('#modalBody').html(
                                                    '<p>No hay pagos registrados para esta venta.</p>'
                                                );
                                            }
                                });
                            }else
                            {
                                $('#texto-no-encontrado').css('display','block');
                                $('#bodycompras').empty();
                            }
                        },
                        error:function(data)
                        {
                            console.log('error:',data)
                            $('#texto-no-encontrado').css('display','block');
                            $('#bodycompras').empty();
                        }
                    });

                }

                // Función para renderizar el listado de productos seleccionados
                function renderSelectedProducts() {
                    var totalVenta = 0;
                    $('#selected-products').empty();
                    $.each(selectedProducts, function(id, product) {

                    });

                    // Actualiza el input o label con el monto total
                    $('#montoPagar').val(totalVenta.toFixed(
                        2)); // Suponiendo que tienes un input con id 'monto-a-pagar'


                    $('#selected-products-container').show();
                }




            });
        </script>
    @endsection
