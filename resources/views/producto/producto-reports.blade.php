@extends('layout')
@section('content')
    <div class="content-wrapper">
        <!-- Content -->

        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-12 d-flex justify-content-center">
                <!-- Form controls -->
                <div class="col-md-12">
                    <form method="POST" id="formGenerarReporte" action="{{ route('producto.generarReporte') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="card">
                            <h5 class="card-header">Especifique los parametros para el reporte</h5>
                            <div class="card-body">
                                <div class="row gy-6">
                                    <div class="col-md">
                                        <small class="text-light fw-medium">Seleccione las categorías que desea incluir en
                                            el reporte</small>
                                        <div class="row">
                                            @foreach ($categorias->chunk(10) as $grupo)
                                                <div class="col-md-6">
                                                    @foreach ($grupo->take(5) as $categoria)
                                                        <div class="form-check mt-3">
                                                            <input class="form-check-input" name="categorias[]"
                                                                type="checkbox" value="{{ $categoria->id }}"
                                                                id="categoria{{ $categoria->id }}" />
                                                            <label class="form-check-label"
                                                                for="categoria{{ $categoria->id }}">{{ $categoria->nombre }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="col-md-6">
                                                    @foreach ($grupo->slice(5) as $categoria)
                                                        <div class="form-check mt-3">
                                                            <input class="form-check-input" name="categorias[]"
                                                                type="checkbox" value="{{ $categoria->id }}"
                                                                id="categoria{{ $categoria->id }}" />
                                                            <label class="form-check-label"
                                                                for="categoria{{ $categoria->id }}">{{ $categoria->nombre }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>

                                    </div>
                                </div>
                                <div class="row gy-6 my-3">
                                    <div class="col-md">
                                        <small class="text-light fw-medium">Seleccione la fecha de inicio y fin del
                                            reporte</small>
                                        <div class="row my-2"
                                          >
                                            <div class="col-md-6">
                                                <div class="mb-4 row">
                                                    <label for="html5-date-input" class="col-md-2 col-form-label mx-2">Fecha de
                                                        inicio</label>
                                                    <div class="col-md-10">
                                                        <input class="form-control" type="date" name="fechaInicio"
                                                            id="fechaInicio" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-4 row">
                                                    <label for="html5-date-input" class="col-md-2 col-form-label mx-2">Fecha de
                                                        fin</label>
                                                    <div class="col-md-10">
                                                        <input class="form-control" type="date" name="fechaFin"
                                                            id="fechaFin" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="row gy-6 my-3">
                                    <div class="col-md">
                                        <small class="text-light fw-medium">Seleccione el tipo de formato del reporte (PDF o
                                            EXCEL)</small>
                                        <div class="row my-2"
                                            style="display:flex; flex-direction:column; justify-content:center; align-items:center;">
                                            <div class="col-md-6">
                                                <div class="form-check form-check-inline mt-3">
                                                    <input
                                                      class="form-check-input"
                                                      type="radio"
                                                      name="tipoFormato"
                                                      id="inlineRadio2"
                                                      value="xlsx" checked/>
                                                    <label class="form-check-label" for="inlineRadio2"><i class="fa fa-file-excel"></i></label>
                                                  </div>
                                                <div class="form-check form-check-inline mt-3">
                                                    <input
                                                      class="form-check-input"
                                                      type="radio"
                                                      name="tipoFormato"
                                                      id="inlineRadio1"
                                                      value="pdf" />
                                                    <label class="form-check-label" for="inlineRadio1"><i class="fa fa-file-pdf"></i></label>
                                                  </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="col-md">
                                        <small class="text-light fw-medium">Marque esta opción si desea agregar al reporte los productos que se encuentran ya eliminados.</small>
                                        <div class="row my-2"
                                            style="display:flex; flex-direction:column; justify-content:start; align-items:start;">
                                            <div class="form-check form-switch mb-2">
                                              <input class="form-check-input" name="agregarEliminados" type="checkbox" id="flexSwitchCheckDefault" checked />
                                              <label class="form-check-label" for="flexSwitchCheckDefault"
                                                >¿Desea agregar al reporte los productos que se encuentran ya eliminados?

                                            </div>
                                        </div>

                                    </div>

                                </div>


                            </div>

                            <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 20px;">
                                <a class="btn btn-primary" id="generarReporte">Generar Reporte</a>
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

     <!-- jQuery -->
     <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        document.getElementById('generarReporte').addEventListener('click', function() {
            $.ajax({
                url: '{{ route('producto.generarReporte') }}',
                type: 'GET',
                data: $('#formGenerarReporte').serialize(),
                success: function(data)
                {
                    if(data.status =='success')
                    {
                        window.location.href = data.url;
                    }else
                    {
                        alert('Error al generar el reporte');
                    }
                }
            });
        });
    </script>
@endsection
