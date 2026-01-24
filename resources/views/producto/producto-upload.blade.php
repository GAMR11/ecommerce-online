@extends('layout')
@section('content')
    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-6 d-flex justify-content-center">
                <!-- Form controls -->
                <div class="col-md-6">
                    <form method="POST" action="{{ route('producto.uploadFile') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="card">
                            <h5 class="card-header">Registrar Productos</h5>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label for="archivo" class="form-label">Archivo excel con productos</label>
                                    <input class="form-control" type="file" accept=".xlsx" name="archivo" id="archivo"
                                        onchange="validarFormulario()" />
                                </div>

                                <div class="d-flex justify-content-center align-items-center">
                                    <button type="submit" class="btn btn-primary my-2 mx-2" id="submit-btn"
                                        disabled>Enviar</button>
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

    <script>
        function validarFormulario() {
            const archivoInput = document.getElementById('archivo');
            const submitBtn = document.getElementById('submit-btn');

            // Habilitar el botón si hay un archivo seleccionado
            if (archivoInput.files.length > 0) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
    </script>
@endsection
