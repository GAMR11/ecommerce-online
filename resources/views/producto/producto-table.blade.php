@extends('layout')
@section('content')
    <div class="content-wrapper">
        <!-- Content -->

        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-12 d-flex justify-content-center">
                <!-- Form controls -->
                <div class="col-md-12">
                    <form method="POST" id="formRegisterProduct" action="{{ route('producto.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="card">
                            <h5 class="card-header">Registrar Producto</h5>
                            <div class="card-body">
                                <div class="row g-6 my-2">
                                    <div class="col mb-0">
                                        <label for="nombre" class="form-label">Nombre del producto</label>
                                        <input class="form-control" minlength="3" maxlength="70" type="text"
                                            name="nombre" id="nombre" placeholder="ingrese el nombre..." oninput="soloTexto(event); validarFormulario()" />
                                    </div>
                                    <div class="col mb-0">
                                        <label for="marca" class="form-label">Marca del producto</label>
                                        <input class="form-control" type="text" minlength="1" maxlength="50"
                                            name="marca" id="marca" placeholder="ingrese la marca..." oninput="validarFormulario()" />
                                    </div>
                                    <div class="col mb-0">
                                        <label for="modelo" class="form-label">Modelo del producto</label>
                                        <input class="form-control" type="text" minlength="1" maxlength="50"
                                            name="modelo" id="modelo" placeholder="ingrese el modelo..." oninput="validarFormulario()" />
                                    </div>
                                </div>
                                <div class="row g-6 my-1">
                                    <div class="col mb-0">
                                        <label for="serie" class="form-label">Serie</label>
                                        <input class="form-control" type="text" minlength="1" maxlength="50"
                                            name="serie" id="serie" placeholder="ingrese la serie..." oninput="validarFormulario()" />
                                    </div>
                                    <div class="col mb-0">
                                        <label for="nombre" class="form-label">Color del producto</label>
                                        <input class="form-control" type="color" name="color" id="color" oninput="validarFormulario()"
                                            placeholder="seleccione el color..." />
                                    </div>
                                    <div class="col mb-0">
                                        <label for="preciooriginal" class="form-label">Precio Original</label>
                                        <input class="form-control" min="1" max="10000" type="number" oninput="soloNumero(event); validarFormulario()"
                                            name="preciooriginal" id="preciooriginal"
                                            placeholder="ingrese el precio original..." />
                                    </div>
                                </div>
                                <div class="row g-6 my-1">
                                </div>
                                <div class="row g-6 my-1">
                                    <div class="col mb-0">
                                        <label for="preciocontado" class="form-label">Precio Contado</label>
                                        <input class="form-control" min="1" max="10000" type="number" oninput="soloNumero(event); validarFormulario()"
                                            name="preciocontado" id="preciocontado"
                                            placeholder="ingrese el precio contado..." />
                                    </div>
                                    <div class="col mb-0">
                                        <label for="preciocredito" class="form-label">Precio Credito</label>
                                        <input class="form-control" min='1' max='10000' type="number" oninput="soloNumero(event); validarFormulario()"
                                            name="preciocredito" id="preciocredito"
                                            placeholder="ingrese el precio credito..." />
                                    </div>
                                    <div class="col mb-0">
                                        <label for="categoria" class="form-label">Categoría</label>
                                        <select class="form-select" id="categoria" name="categoria" oninput="validarFormulario()"
                                            aria-label="Default select example">
                                            <option value selected disabled>Seleccione una categoría</option>
                                            @foreach ($categorias as $key => $categoria)
                                                <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4 my-1">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" name="descripcion" id="descripcion" rows="6"></textarea>
                                </div>


                                <div class="mb-4">
                                    <label for="preciocredito" class="form-label">Cargar Imagenes</label>
                                    <input class="form-control" accept="image/*" type="file" name="imagenes[]"
                                        multiple id="imagenes" />
                                </div>

                                <div class="d-flex justify-content-center align-items-center">
                                    <button id="submit-btn" class="btn btn-primary my-2 mx-2" onclick="enviarForm()" disabled>Enviar</button>
                                </div>

                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- / Content -->

        <!-- Footer -->
        @include('templates.footer')
        <!-- / Footer -->

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
       function validarFormulario()
       {
            const campos = ['nombre', 'marca', 'modelo', 'serie', 'color', 'categoria', 'preciooriginal', 'preciocontado', 'preciocredito'];
            let formularioValido = true;
            let camposFaltantes = [];

            // Recorrer todos los campos y validar si están vacíos
            campos.forEach(id => {
                const valor = document.getElementById(id).value.trim();
                if (valor.length === 0) {
                    formularioValido = false;
                    camposFaltantes.push(id); // Guardamos los campos vacíos
                }
            });

            // Referencia al botón de envío
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = !formularioValido;

            // Si falta algún campo, mostrar alerta con SweetAlert
            return formularioValido;
        }

        function enviarForm()
        {
            if(validarFormulario()==false)
            {
                Swal.fire({
                    title: 'Atención',
                    text: 'Faltan campos por llenar',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            }else
            {
                document.getElementById('formRegisterProduct').submit();
            }
        }
    </script>
@endsection
