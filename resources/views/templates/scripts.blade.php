<!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->

    <script>
        function soloTexto(event)
        {
            // Solo permite letras y espacios
            let input = event.target;
            input.value = input.value.replace(/[^A-Za-zÁáÉéÍíÓóÚúÑñ\s]/g, '');
        }
        function soloNumero(event)
        {
            let input = event.target;
            input.value = input.value.replace(/[^0-9]/g, ''); // Elimina cualquier carácter que no sea número
        }

    </script>

    {{-- <script src="{{ asset('assets/vendor/libs/jquery/jquery.js')}}"></script> --}}
    <script src="{{ asset('assets/vendor/libs/popper/popper.js')}}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js')}}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')}}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js')}}"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>

    <!-- Main JS -->
    <script src="{{ asset('assets/js/main.js')}}"></script>

    <!-- Page JS -->
    <script src="{{ asset('assets/js/dashboards-analytics.js')}}"></script>

    <!-- Place this tag before closing body tag for github widget button. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>




    <!-- JavaScript de DataTables -->
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
<!-- JavaScript de FixedColumns -->
<script src="https://cdn.datatables.net/fixedcolumns/5.0.3/js/dataTables.fixedColumns.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/5.0.3/js/fixedColumns.dataTables.js"></script>
<!-- JavaScript de Select -->
<script src="https://cdn.datatables.net/select/2.1.0/js/dataTables.select.js"></script>
<script src="https://cdn.datatables.net/select/2.1.0/js/select.dataTables.js"></script>


@include('sweetalert::alert')


