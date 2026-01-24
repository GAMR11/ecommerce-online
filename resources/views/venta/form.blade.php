@extends('layout')
@section('content')
    <div class="content-wrapper">
        <div class='my-5'>
            <center>
                Seleccione los artículos que se van a despachar
            </center>
        </div>
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-6">
                <div class="col-md-12">
                    <table id="example" class="stripe row-border order-column nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th style="visibility:hidden;"></th>
                                <th>Nombre</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Color</th>
                                <th>Stock</th>
                                <th>PContado</th>
                                <th>PCredito</th>
                                <th>Categoría</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($productos as $producto)
                                <tr id="id-producto-{{ $producto->id }}" data-id="{{ $producto->id }}"
                                    data-product="{{ json_encode($producto) }}">
                                    <td><input type="checkbox" class="select-checkbox"></td>
                                    <td>{{ $producto->nombre }}</td>
                                    <td>{{ $producto->marca }}</td>
                                    <td>{{ $producto->modelo }}</td>
                                    <td>{{ $producto->color }}</td>
                                    <td>{{ $producto->cantidad }}</td>
                                    <td>{{ $producto->precio_contado }}</td>
                                    <td>{{ $producto->precio_credito }}</td>
                                    <td>{{ $producto->categoria->nombre }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <form id="formGuardarVenta" method="POST" action="{{ route('venta.guardarVenta') }}" enctype="multipart/form-data">
                @csrf
                <div id="selected-products-container" style="display:none;" class="my-1">
                    <h5>Productos a Despachar</h5>
                    <div id="selected-products">
                        <table id="productosseleccionados" class="table table-striped table-light" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Color</th>
                                    <th>Tipo de Precio</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="bodySeleccionados">

                            </tbody>
                        </table>
                        <!-- Aquí se mostrarán los productos seleccionados -->
                        {{-- <button class="btn btn-primary" id="btnGuardar">Continuar</button> --}}
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:1em;">
                        <div style="display:flex; flex-direction:row; align-items:center; justify-content:space-between; gap:1em;">
                            <div>
                                <label for="estado">Forma de Pago</label>
                                <select class="form-control" name="formapago" id="formapago">
                                    <option selected disabled>Seleccionar..</option>
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Transferencia">Transferencia</option>
                                    <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                                    <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div>
                                <label for="estado">Estado del Pago</label>
                                <select class="form-control" name="estado" id="estado">
                                    <option selected disabled>Seleccionar..</option>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Recibido">Recibido</option>
                                </select>
                            </div>
                            <div>
                                <label for="estadoentrega">Estado de la entrega</label>
                                <select class="form-control" name="estadoentrega" id="estadoentrega">
                                    <option selected disabled>Seleccionar..</option>
                                    <option value="Por entregar">Por Entregar</option>
                                    <option value="Entregado">Entregado</option>
                                </select>
                            </div>

                            <div id="content-imagen-pago" >
                                <label for="pago">Cargue la imagen del pago realizado</label>
                                <input class="form-control" type="file" name="abono" id="abono" accept="image/*">
                            </div>

                        </div>

                        <div style="display:flex; flex-direction:row; align-items:center; justify-content:space-between; gap:1em;">
                            <div>
                                <label for="comentario">Observación o Comentario de la venta</label>
                                <textarea class="form-control" name="comentario" cols="75" id="comentario" rows="4"></textarea>
                            </div>

                        </div>


                    </div>


                    <div style="text-align:center; margin-top:1em;">
                        <a class="btn btn-outline-primary" id="continuarProductosDespachar">Continuar</a>
                    </div>
                </div>

                 {{-- BUSCAR CLIENTE --}}
                 {{-- <div style="display:flex; justify-content:start; align-items:center;flex-direction:row;gap:1rem;">
                    <a class="btn btn-outline-primary">Buscar Cliente</a>
                    <a class="btn btn-outline-secondary">Agregar Cliente</a>
                 </div> --}}

                 <div id="selected-products-buscar-cliente" style="display:none;" class="my-1">
                    <h5>Datos del Cliente</h5>

                    <div class="row g-6 my-2">
                            <div class="col-4" style="display:flex; justify-content:start; align-items:center;flex-direction:row;gap:1rem;">
                                <input class="form-control" type="text" minlength="10" maxlength="13" name="identificacionbuscar" id="identificacionbuscar" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    placeholder="ingrese la identificación del cliente..." />
                                    <a class="btn btn-info" id="btn-buscar" ><i class="fas fa-search"></i></a>
                            </div>

                    </div>


                    <div class="mensaje-cliente-no-encontrado" id="mensaje-cliente-no-encontrado" style="display: none;">
                        <p>Cliente no encontrado.</p>
                    </div>

                    <div class="datos-cliente-buscar" id="datos-cliente-buscar" style="display: none;">
                        <div class="row g-6 my-2">
                            <div class="col-3">
                                <label for="nombre" class="form-label">Nombres</label>
                                <input class="form-control" type="text" name="nombre" id="nombre" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                                    placeholder="ingrese el nombre..." />
                            </div>
                            <div class="col-3">
                                <label for="marca" class="form-label">Apellidos</label>
                                <input class="form-control" type="text" name="apellidos" id="apellido" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                                    placeholder="ingrese el apellido..." />
                            </div>
                            <div class="col-3">
                                <label for="identificacion" class="form-label">N° de Identificación</label>
                                <input class="form-control"
                                type="text"
                                minlength="10"
                                maxlength="13"
                                name="identificacion"
                                id="identificacion"
                                placeholder="Ingrese la identificación..."
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                            </div>
                        </div>
                        <div class="row g-6 my-2">
                            <div class="col-3">
                                <label for="nombre" class="form-label">Dirección</label>
                                <input class="form-control" type="text" name="direccion" id="direccion"
                                    placeholder="ingrese el nombre..." />
                            </div>
                            <div class="col-3">
                                <label for="marca" class="form-label">Teléfono</label>
                                <input class="form-control" type="text" minlength="10" maxlength="10" name="telefono" id="telefono" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    placeholder="ingrese la marca..." />
                            </div>
                            <div class="col-3">
                                <label for="marca" class="form-label">¿Necesita Garante?</label>
                                <select class="form-control" name="showGarante" id="showGarante">
                                    <option value="1">NO</option>
                                    <option value="2">SI</option>
                                </select>
                            </div>
                        </div>
                        <div style="text-align:center; margin-top:1em;">
                            <a class="btn btn-outline-primary" id="continuarDatosCliente">Continuar</a>
                        </div>
                    </div>
                </div>

                {{-- DATOS DEL GARANTE --}}
                <div id="selected-products-garante" style="display:none;" class="my-3">
                    <h5>Datos del Garante</h5>

                    <div class="row g-6 my-2">
                        <div class="col-4" style="display:flex; justify-content:start; align-items:center;flex-direction:row;gap:1rem;">
                            <input class="form-control" type="text" minlength="10" maxlength="13" name="identificacionbuscargarante" id="identificacionbuscargarante" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                placeholder="ingrese la identificación del garante..." />
                                <a class="btn btn-info" id="btn-buscar-garante" ><i class="fas fa-search"></i></a>
                        </div>
                    </div>


                    <div class="mensaje-garante-no-encontrado" id="mensaje-garante-no-encontrado" style="display: none;">
                        <p>Garante no encontrado.</p>
                    </div>

                    <div class="datos-garante-buscar" id="datos-garante-buscar" style="display: none;">
                        <div class="row g-6 my-2">
                            <div class="col-3">
                                <label for="nombre" class="form-label">Nombres</label>
                                <input class="form-control" type="text" name="nombre_garante" id="nombregarante"
                                    placeholder="ingrese el nombre..." />
                            </div>
                            <div class="col-3">
                                <label for="marca" class="form-label">Apellidos</label>
                                <input class="form-control" type="text" name="apellido_garante" id="apellidogarante"
                                    placeholder="ingrese el apellido..." />
                            </div>
                            <div class="col-3">
                                <label for="identificacion" class="form-label">N° de Identificación</label>
                                <input class="form-control" type="text" name="identificacion_garante" id="identificaciongarante"
                                    placeholder="ingrese la identificacion..." />
                            </div>
                        </div>
                        <div class="row g-6 my-2">
                            <div class="col-3">
                                <label for="nombre" class="form-label">Dirección</label>
                                <input class="form-control" type="text" name="direccion_garante" id="direcciongarante"
                                    placeholder="ingrese la dirección..." />
                            </div>
                            <div class="col-3">
                                <label for="marca" class="form-label">Teléfono</label>
                                <input class="form-control" type="text" name="telefono_garante" id="telefonogarante"
                                    placeholder="ingrese el teléfono..." />
                            </div>
                        </div>
                        <div style="text-align:center; margin-top:1em;">
                            <a class="btn btn-outline-primary" id="continuarDatosGerente">Continuar</a>
                        </div>
                    </div>

                </div>

                {{-- DATOS DE LA VENTA --}}
                <div id="selected-products-venta" style="display:none;" class="my-3">

                    <h5>Datos de la Venta</h5>

                    <div>
                        {{-- <label>Calcular total</label> --}}
                        <a id="actualizarTotal" class="btn btn-outline-info">Refrescar &nbsp; <i class="fas fa-sync-alt"></i></a>
                        <a class="btn btn-outline-primary my-2"
                        id="calcularCuotas">Calcular Cuotas &nbsp;<i class="fas fa-calculator"></i></a>
                    </div>

                    <div class="row g-6 my-2">
                        <div class="col-3">
                            <label for="nombre" class="form-label">Monto</label>
                            <input class="form-control" type="number" name="montoPagar" id="montoPagar" readonly
                               />
                        </div>
                        <div class="col-3">
                            <label for="marca" class="form-label">Entrada</label>
                            <input class="form-control" type="number" name="entrada" min='0' max='5000' id="entrada" oninput="validarEntrada()"
                                />
                        </div>
                        <div class="col-3">
                            <label for="identificacion" class="form-label">Saldo</label>
                            <input class="form-control" type="number" name="saldo" id="saldo" readonly
                                />
                        </div>
                    </div>

                    <div class="row g-6 my-2">
                        <div class="col-3">
                            <label for="nombre" class="form-label">Meses</label>
                            <input class="form-control" type="number" name="meses" id="meses" min='0' max='24' oninput="validarMeses()" />
                        </div>
                        <div class="col-3">
                            <label for="marca" class="form-label">Interes</label>
                            <input class="form-control" type="number" min='0' max='20' name="interes" id="interes" />
                        </div>
                        <div class="col-3" id="divcuotas" style="display: none;">
                            <label for="marca" class="form-label">Cuotas</label>
                            <input class="form-control" type="number" name="cuotas" id="cuotas" />
                        </div>
                    </div>

                    <div style="display:flex; justify-content:center; align-items:center;">
                        <a class="btn btn-outline-primary my-2"
                        id="btnGuardar">Despachar &nbsp;<i class="fas fa-truck"></i></a>
                    </div>
                </div>

                <input type="hidden" name="productos" id="productos">
            </form>

        </div>

    </div>

    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/2.1.8/js/jquery.dataTables.min.js"></script>


<script>
$(document).ready(function()
{

    const selectedProducts = {}; // Objeto para almacenar los productos seleccionados por su ID

    // Inicializar DataTable
    const table = $('#example').DataTable({
        columnDefs: [{
            orderable: false,
            targets: 0
        }],
        order: [[1, 'asc']],
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

    document.getElementById('btn-buscar').addEventListener('click', function()
    {
        let identificacion = document.getElementById('identificacionbuscar').value;
        if(identificacion)
        {
            $.ajax({
                url: 'buscar-by-identificacion',
                        method: 'GET',
                        data: {
                            identificacion: identificacion,
                        },
                        success: function(data) {
                            console.log('info:', data)
                            if(data.cliente == null)
                            {
                                $('#mensaje-cliente-no-encontrado').show();
                                $('#datos-cliente-buscar').show();
                                $('#nombre').val('');
                                $('#apellido').val('');
                                $('#identificacion').val('');
                                $('#direccion').val('');
                                $('#telefono').val('');
                            }else
                            {
                                $('#mensaje-cliente-no-encontrado').hide();
                                $('#datos-cliente-buscar').show();
                                $('#nombre').val(data.cliente.nombre);
                                $('#apellido').val(data.cliente.apellidos);
                                $('#identificacion').val(data.cliente.identificacion);
                                $('#direccion').val(data.cliente.direccion);
                                $('#telefono').val(data.cliente.telefono);
                            }
                        }

            });
        }
    });

    document.getElementById('btn-buscar-garante').addEventListener('click', function()
    {
        let identificacion = document.getElementById('identificacionbuscargarante').value;
        if(identificacion)
        {
            $.ajax({
                url: 'buscar-garante-by-identificacion',
                        method: 'GET',
                        data: {
                            identificacion: identificacion,
                        },
                        success: function(data) {
                            console.log('info:', data)
                            if(data.garante == null)
                            {
                                $('#mensaje-garante-no-encontrado').show();
                                $('#datos-garante-buscar').show();
                                $('#nombregarante').val('');
                                $('#apellidogarante').val('');
                                $('#identificaciongarante').val('');
                                $('#direcciongarante').val('');
                                $('#telefonogarante').val('');
                            }else
                            {
                                $('#mensaje-garante-no-encontrado').hide();
                                $('#datos-garante-buscar').show();
                                $('#nombregarante').val(data.garante.nombre);
                                $('#apellidogarante').val(data.garante.apellido);
                                $('#identificaciongarante').val(data.garante.identificacion);
                                $('#direcciongarante').val(data.garante.direccion);
                                $('#telefonogarante').val(data.garante.telefono);
                            }
                        }

            });
        }
    });

    // Función para renderizar el listado de productos seleccionados
    function renderSelectedProducts()
    {
        var totalVenta = 0;
        $('#bodySeleccionados').empty();
        $.each(selectedProducts, function(id, product) {
            console.log('data prodct:',product)
            totalVenta += parseFloat(product.precio_contado) || 0;
           $('#bodySeleccionados').append(`
               <tr class="selected-product">
                   <td>${product.nombre}</td>
                   <td>${product.marca}</td>
                   <td>${product.modelo}</td>
                   <td>${product.color}</td>
                   <td> <select style="width:100px;" class="form-control select-tipo-precio" data-precio-credito="${product.precio_credito}" data-precio-contado="${product.precio_contado}" data-select-tipo="${id}" name="tipo_precio[]" id="tipo_precio${id}">
                             <option value="1">Contado</option>
                             <option value="2">Credito</option>
                             <option value="3 ">Otro</option>
                         </select></td>
                   <td><input type="number" class="form-control cantidades" name="cantidades[]" value="${product.cantidad}" min="1" max="${product.cantidad}"></td>
                   <td><input type="number" readonly  name="precios[]" id="precios${product.id}" class="form-control precios" value="${product.precio_contado}" min="1"></td>
                   <td>
                    <button class="btn btn-danger remove-product" data-id="${id}">Eliminar</button>
                     <input class="preciosContado" type="hidden" name="preciosContado[]" value="${product.precio_contado}">
                     <input class="precioCredito" type="hidden" name="preciosCredito[]" value="${product.precio_credito}">
                   </td>
               </tr>
           `);

            /** Logica para ir sumando los articulos seleccionados **/


            document.querySelectorAll('.select-tipo-precio').forEach((select, i) => {
                select.addEventListener('change', function() {
                    let idSelect = $(this).attr('data-select-tipo');
                    let precioContado = $(this).attr('data-precio-contado');
                    let precioCredito = $(this).attr('data-precio-credito');
                    console.log('valor:', $(this).val());
                    let valor = $(this).val();
                    if (valor == 3) {//otro
                        document.getElementById('precios' + idSelect).value = 0;
                        $('#precios'+idSelect).prop('readonly', false);
                    } else if (valor == 2) {//credito
                        document.getElementById('precios' + idSelect).value = precioCredito;
                    }else //contado
                    {
                        document.getElementById('precios' + idSelect).value = precioContado;
                    }
                });
            });
        });

         // Actualiza el input o label con el monto total
        $('#montoPagar').val(totalVenta.toFixed(2)); // Suponiendo que tienes un input con id 'monto-a-pagar'


        $('#selected-products-container').show();
    }

    // Evento para manejar la selección de filas
    $('#example tbody').on('change', 'input.select-checkbox', function()
    {
        const row = $(this).closest('tr');
        const productId = row.data('id');
        const productData = row.data('product');

        if (this.checked) {
            selectedProducts[productId] = productData; // Agregar producto al objeto
        } else {
            delete selectedProducts[productId]; // Eliminar producto del objeto
        }
        console.log('contenido de selectedProducts',selectedProducts)

        renderSelectedProducts(); // Actualizar el div con productos seleccionados
    });

    // Evento para eliminar producto al hacer clic en el botón de "Eliminar" en el div de productos seleccionados
    $('#selected-products').on('click', '.remove-product', function() {
        const productId = $(this).data('id');

        // Desmarcar checkbox correspondiente en la tabla
        $(`#id-producto-${productId} .select-checkbox`).prop('checked', false);

        delete selectedProducts[productId]; // Eliminar producto del objeto
        renderSelectedProducts(); // Actualizar el div con productos seleccionados
    });

    document.getElementById('btnGuardar').addEventListener('click', function()
    {
        document.getElementById('productos').value = JSON.stringify(selectedProducts);
        document.getElementById('formGuardarVenta').submit();
    });

    document.getElementById('continuarProductosDespachar').addEventListener('click', function()
    {
        let cantidadRegistros = document.querySelectorAll('.selected-product').length;
        let estado = document.getElementById('estado').value;

        if(cantidadRegistros > 0 )
        {
            console.log('formapago:',$('#formapago').val());
            if ($('#formapago').val() == null)
            {
                alert('Debe seleccionar un tipo de pago para continuar.');
            }
            else if ($('#estado').val() == null)
            {
                alert('Debe seleccionar un estado para continuar.');
            }
            else if(estado == 2 && ($('#abono').val() == null || $('#abono').val() == ''))
            {
                alert('Adjunte la imagen del pago realizado.')
            }
            else
            {
                document.getElementById('selected-products-buscar-cliente').style.display = 'block';
                $('#continuarProductosDespachar').remove();
            }

        }
        else if(estado == 2 && ($('#abono').val() == null || $('#abono').val() == ''))
        {
            alert('Adjunte la imagen del pago realizado.')
        }
        else
        {
                alert('Debe seleccionar al menos un producto para continuar.');
        }

    });

    document.getElementById('showGarante').addEventListener('change', function()
    {
        needGarante = $(this).val();
        if(needGarante == 2)
        {
            document.getElementById('selected-products-garante').style.display = 'block';
        }else
        {
            document.getElementById('selected-products-garante').style.display = 'none';
        }
    });

    document.getElementById('continuarDatosCliente').addEventListener('click', function()
    {

        if($('#nombre').val() == '' || $('#apellidos').val() == '' || $('#identificacion').val() == '' || $('#direccion').val() == '' || $('#telefono').val() == '')
        {
            alert('Complete los datos del cliente para poder continuar.');
            return;
        }
        if(needGarante == 2)
        {
            document.getElementById('selected-products-garante').style.display = 'block';
            // document.getElementById('selected-products-venta').style.display = 'block';
        }else
        {
            document.getElementById('selected-products-garante').style.display = 'none';
            document.getElementById('selected-products-venta').style.display = 'block';
        }
        calcularTotalVenta();

    });

    var needGarante = 1;

    document.getElementById('continuarDatosGerente').addEventListener('click', function()
    {
        document.getElementById('selected-products-venta').style.display = 'block';
    });

    function calcularTotalVenta()
    {
        let totalVenta = 0;

        let precios = document.querySelectorAll('.precios');
        let cantidades = document.querySelectorAll('.cantidades');
        for(let i = 0; i < precios.length; i++)
        {
            let precio = precios[i].value;
            if(precio)
            {
                totalVenta += parseFloat(precio) * parseFloat(cantidades[i].value);
            }
        }
        $('#montoPagar').val(totalVenta.toFixed(2));

    }

    document.getElementById('actualizarTotal').addEventListener('click', function()
    {
        calcularTotalVenta();
    });

    document.getElementById('entrada').addEventListener('keyup', function() {
        let totalVenta = parseFloat(document.getElementById('montoPagar').value) || 0; // Obtiene el monto total
        let entrada = parseFloat(this.value) || 0; // Obtiene la entrada y la convierte a número
        // Si el valor es negativo, lo cambia a 0 o una cadena vacía
        if (entrada < 0) {
            this.value = '';
        }

        let saldoRestante = totalVenta - entrada; // Calcula el saldo restante

        // Muestra el saldo restante en la consola (puedes mostrarlo en otro elemento de la página)
        console.log('Saldo restante:', saldoRestante);

        // Opcional: muestra el saldo en un elemento específico
        document.getElementById('saldo').value = saldoRestante.toFixed(2);
    });

    document.getElementById('entrada').addEventListener('keydown', function(event)
    {
        // Obtiene el código de la tecla presionada
        let key = event.key;

        // Si la tecla es "-", la bloquea
        if (key === '-') {
            event.preventDefault();
        }
    });

    document.getElementById('calcularCuotas').addEventListener('click', function()
    {
        let saldo = parseFloat(document.getElementById('saldo').value);
        let interes = parseFloat(document.getElementById('interes').value);
        let meses = parseInt(document.getElementById('meses').value);
        let cuotas = document.getElementById('cuotas');

        // Validar que 'saldo' sea un número mayor a 0, y que 'interes' y 'meses' no sean NaN y sean >= 0
        if (!isNaN(saldo) && saldo >= 0 && !isNaN(interes) && interes >= 0 && !isNaN(meses) && meses >= 0) {
            let cuotasTotal;

            if (meses === 0) {
                // Si meses es 0, el pago es al contado
                cuotasTotal = saldo;
            } else if (meses <= 3) {
                // Si el plazo es de 3 meses o menos, no se aplica interés
                cuotasTotal = saldo / meses;
            } else {
                // Si el plazo es mayor a 3 meses, se aplica interés
                let interesTotal = saldo * (interes / 100) * meses; // Interés total acumulado
                let saldoConInteres = saldo + interesTotal; // Saldo con el interés total incluido
                cuotasTotal = saldoConInteres / meses; // Cuota mensual
            }

            cuotas.value = cuotasTotal.toFixed(2);
            // cuotas.value = Math.round(cuotasTotal);
            console.log('Cuota calculada:', cuotasTotal);
            document.getElementById('divcuotas').style.display = 'block';
        } else {
            alert('Por favor, asegúrese de ingresar todos los valores correctamente.');
        }
    });

    document.getElementById('estado').addEventListener('change', function()
    {
        let estado = document.getElementById('estado').value;
        if(estado == 'Recibido')//recibido
        {
            document.getElementById('content-imagen-pago').style.visibility = 'visible';
        }else
        {
            document.getElementById('content-imagen-pago').style.visibility = 'hidden';
        }
    });
});

function validarEntrada()
{
    const montoPagar = parseFloat(document.getElementById('montoPagar').value);
    const entradaInput = document.getElementById('entrada');
    const saldoInput = document.getElementById('saldo');
    let entrada = parseFloat(entradaInput.value);

    // Si la entrada está vacía, no hacer nada
    if (isNaN(entrada)) {
        saldoInput.value = '';
        entradaInput.value = '0';
        return;
    }

    // Validar que la entrada no sea negativa
    if (entrada < 0) {
        entradaInput.value = '';
        alert("La entrada no puede ser un número negativo.");
        return;
    }

    // Validar que la entrada no supere el monto
    if (entrada > montoPagar) {
        entradaInput.value = montoPagar;
        alert("La entrada no puede ser mayor que el monto.");
    }

    // Validar que la entrada no supere los 10,000
    if (entrada > 10000) {
        entradaInput.value = 10000;
        alert("La entrada no puede superar los 10,000.");
    }

    // Calcular el saldo
    entrada = parseFloat(entradaInput.value);
    const saldo = montoPagar - entrada;
    saldoInput.value = saldo.toFixed(2);
}

function validarMeses()
{
    const mesInput = document.getElementById('meses');
    let mes = parseFloat(mesInput.value);
     // Si la entrada está vacía, no hacer nada
     if (isNaN(mes)) {
        mesInput.value = 0;
        return;
    }

    // Validar que la entrada no sea negativa
    if (mes < 0) {
        mesInput.value = '';
        alert("El mes no puede ser un número negativo.");
        return;
    }

    // Validar que el mes no sea mayor que 24
    if (mes > 24) {
        entradaInput.value = 24;
        alert("El mes no puede ser mayor que 24.");
    }
    if (mes < 0) {
        entradaInput.value = 0;
        alert("El mes no debe ser menor que 0.");
    }


}
</script>

@endsection
