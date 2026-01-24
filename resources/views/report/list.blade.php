@extends('layout')
@section('content')
    <div class="content-wrapper">
        <!-- Content -->

        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-6">
                <!-- Form controls -->

                {{-- TABLA DE CATEGORIAS EXISTENTES --}}
                <div class="col-md-12">
                    <div class="card overflow-hidden">
                        <h5 class="card-header">Reportes</h5>
                        <div class="table-responsive text-nowrap">
                            @if($reportes->count() > 0)

                            @php
                                $frecuencias = [
                                    1 => 'Cada hora',
                                    2 => 'Cada día',
                                    3 => 'Cada semana',
                                    4 => 'Cada mes',
                                ];
                                $estados = [
                                    'A' => 'Disponible',
                                    'I' => 'No Disponible',
                                ];
                            @endphp
                            <table class="table table-dark">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Asunto</th>
                                        <th>Para</th>
                                        <th>Copia</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Hora</th>
                                        <th>Frecuencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    @foreach ($reportes as $key => $reporte)
                                        <tr>
                                            <td>
                                                {{ $reporte->name }}
                                            </td>
                                            <td>
                                                {{ $reporte->subject }}</td>

                                            <td>
                                                {{ $reporte->to }}</td>

                                            <td>
                                                {{ $reporte->cc }}</td>

                                            <td>
                                                {{ $reporte->startDate }}</td>

                                            <td>
                                                {{ $reporte->endDate }}</td>

                                            <td>
                                                {{ $reporte->time }}</td>

                                            <td>
                                                {{ $frecuencias[$reporte->period] ?? 'Frecuencia no definida' }}
                                            </td>

                                            <td>
                                                {{ $estados[$reporte->status] ?? 'Estado no definido' }}
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                        data-bs-toggle="dropdown">
                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" type="button" data-bs-toggle="modal"
                                                            data-bs-target="#editarreporte{{ $key }}"><i
                                                                class="bx bx-edit-alt me-1"></i> Editar</a>

                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        <form method="POST" action="{{ route('reporte.update',$reporte->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal fade" id="editarreporte{{ $key }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel1">Editar Reporte
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col mb-6">
                                                                    <label for="nombre" class="form-label">Nombre</label>
                                                                    <input readonly type="text" oninput="soloTexto(event)" id="nombre" name="nombre"
                                                                        class="form-control" value="{{ $reporte->name }}" minlength="3" maxlength="50"
                                                                        placeholder="Ingrese el nombre..." />
                                                                </div>

                                                            </div>
                                                            <div class="row">
                                                                <div class="col mb-6">
                                                                    <label for="asunto" class="form-label">Asunto</label>
                                                                    <input type="text" oninput="soloTexto(event)" id="asunto" name="subject"
                                                                        class="form-control" value="{{ $reporte->subject }}" minlength="3" maxlength="50"
                                                                        placeholder="Ingrese el asunto..." />
                                                                </div>
                                                                <div class="col mb-6">
                                                                    <label for="to" class="form-label">Para</label>
                                                                    <input type="text"  id="to" name="to"
                                                                        class="form-control" value="{{ $reporte->to }}" minlength="3" maxlength="50"
                                                                        placeholder="Ingrese para quien..." />
                                                                </div>

                                                            </div>
                                                            <div class="row">
                                                                <div class="col mb-6">
                                                                    <label for="startDate" class="form-label">Fecha Inicio</label>
                                                                    <input type="date"  id="startDate" name="startDate"
                                                                        class="form-control" value="{{ $reporte->startDate }}" minlength="3" maxlength="50"
                                                                        placeholder="Ingrese la fecha de inicio..." />
                                                                </div>
                                                                <div class="col mb-6">
                                                                    <label for="endDate" class="form-label">Fecha Fin</label>
                                                                    <input type="date"  id="endDate" name="endDate"
                                                                        class="form-control" value="{{ $reporte->endDate }}" minlength="3" maxlength="50"
                                                                        placeholder="Ingrese la fecha fin..." />
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col mb-6">
                                                                    <label for="hora" class="form-label">Hora</label>
                                                                    <input type="time"  id="hora" name="time"
                                                                        class="form-control" value="{{ $reporte->time }}" minlength="3" maxlength="50"
                                                                        placeholder="Ingrese la hora..." />
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col mb-6">
                                                                    <label for="frecuencia" class="form-label">Frecuencia</label>
                                                                    <select class="form-select" id="frecuencia" name="period"
                                                                    aria-label="Default select example">
                                                                        <option value="1" {{ $reporte->period == '1' ? 'selected' : '' }}>Cada hora</option>
                                                                        <option value="2" {{ $reporte->period == '2' ? 'selected' : '' }}>Cada día</option>
                                                                        <option value="3" {{ $reporte->period == '3' ? 'selected' : '' }}>Cada semana</option>
                                                                        <option value="4" {{ $reporte->period == '4' ? 'selected' : '' }}>Cada mes</option>
                                                                </select>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col mb-6">
                                                                    <label for="estado" class="form-label">Estado</label>
                                                                    <select class="form-select" id="estado" name="status"
                                                                    aria-label="Default select example">
                                                                        <option value="A" {{ $reporte->status =='A' ?? 'selected' }}>Disponible</option>
                                                                        <option value="I" {{ $reporte->status == 'I' ?? 'selected' }}>No disponible</option>
                                                                </select>
                                                                </div>
                                                            </div>

                                                        </div>
                                                        <div
                                                            class="modal-footer d-flex flex-direction-row justify-content-center align-items-center">
                                                            <button type="button" class="btn btn-outline-secondary my-2 mx-2"
                                                                data-bs-dismiss="modal">
                                                                Cancelar
                                                            </button>
                                                            <button type="submit" class="btn btn-primary  my-2 mx-2">Guardar
                                                                cambios</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>


                                    @endforeach

                                </tbody>
                            </table>
                            @else
                            <div class="alert alert-warning text-center" role="alert">
                                No hay reportes existentes.
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

    <script>
        function validarFormulario() {
            const nombre = document.getElementById('nombre').value.trim();
            const submitBtn = document.getElementById('submit-btn');

            // Habilitar el botón si hay al menos 1 carácter en el campo nombre
            if (nombre.length > 0) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
    </script>

@endsection
