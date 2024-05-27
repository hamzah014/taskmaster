<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="Materialize is a Material Design Admin Template,It's modern, responsive and based on Material Design by Google.">
    <meta name="keywords" content="materialize, admin template, dashboard template, flat admin template, responsive admin template, eCommerce dashboard, analytic dashboard">
    <meta name="author" content="ThemeSelect">
    <title>SPEED</title>
    <link rel="apple-touch-icon" href="{{asset('assets/images/favicon/logo-152x152.png')}}">
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('assets/images/favicon/logo-32x32.png')}}">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/vendors.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/vertical-modern-menu-template/materialize.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/vertical-modern-menu-template/style.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/sweetalert/sweetalert.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/pages/login.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/custom/custom.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('asset/css/pages/register.css')}}">
    <!-- Datatable -->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/data-tables/css/jquery.dataTables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/data-tables/Responsive-2.3.0/css/responsive.dataTables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/data-tables/Select-1.4.0/css/select.dataTables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/data-tables/Buttons-2.2.3/css/buttons.dataTables.min.css')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="vertical-layout vertical-menu-collapsible page-header-dark vertical-modern-menu preload-transitions 1-column login-bg   blank-page blank-page" data-open="click" data-menu="vertical-modern-menu" data-col="1-column">
<div class="row">
    <div class="col s12">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col s12">
                    <div class="card border-radius-6 login-card bg-opacity-8" style="margin: 5rem 0 5rem 0 !important;">
                        <div class="card-content">
                            <h4 class="card-title">{{ __('Maklumat Tuntutan') }}</h4>

                            <div class="row" >
                                <div class="col s12">
                                    <div class="card">
                                        <div class="card-content">
                                            <div class="row">
                                                <div class="col s12">
                                                    <h4>Tuntutan</h4>
                                                    <div class="row">
                                                        <input type="hidden" id="idClaim" name="idClaim" value="{{$claim->PCNo}}">
                                                        <div class="col s6">
                                                            <p>No. Rujukan Tuntutan: {{$claim->PCNo}}</p>
                                                        </div>
                                                    </div>
                                                    <table id="invoice-table" class="speed_mini">
                                                        <thead>
                                                            <tr>
                                                                <th><b>No.</b></th>
                                                                <th><b>No. Invois</b></th>
                                                                <th><b>Tarikh</b></th>
                                                                <th><b>Jumlah</b></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>

                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <th></th>
                                                                <th></th>
                                                                <th></th>
                                                                <th class="text-right" name="total"><b>Total:</b></th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row" >
                                <div class="col s12">
                                    <div class="card">
                                        <div class="card-content">
                                            <div class="row">
                                                <div class="col s12">
                                                    <h4>Senarai Semak Lampiran Tuntutan</h4>
                                                    <div style="text-align:right; padding:5px">
                                                    </div>
                                                    <table id="checklist-table" class="speed">
                                                        <thead>
                                                        <tr>
                                                            <th><b>No.</b></th>
                                                            <th><b>Keterangan</b></th>
                                                            <th><b>Tindakan</b></th>
                                                        </tr>
                                                        </thead>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-overlay"></div>
    </div>
</div>
<script src="{{asset('assets/js/vendors.min.js')}}"></script>
<script src="{{asset('assets/js/plugins.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{asset('js/ajaxSubmit.js')}}" type="text/javascript"></script>
{{--<script src="{{asset('assets/js/scripts/intro.js')}}"></script>--}}
<script src="{{asset('assets/js/scripts/form-validation.js')}}"></script>
<script src="{{asset('assets/js/scripts/data-tables.js')}}"></script>
<script src="{{asset('assets/js/scripts/advance-ui-modals.js')}}"></script>

<script src="{{asset('js/datatables-buttons/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('js/datatables-buttons/dataTables.buttons.min.js')}}"></script>
<script src="{{asset('js/datatables-buttons/buttons.jqueryui.min.js')}}"></script>
<script src="{{asset('js/datatables-buttons/jszip.min.js')}}"></script>
<script src="{{asset('js/datatables-buttons/pdfmake.min.js')}}"></script>
<script src="{{asset('js/datatables-buttons/vfs_fonts.js')}}"></script>
<script src="{{asset('js/datatables-buttons/buttons.html5.min.js')}}"></script>
<script src="{{asset('js/datatables-buttons/buttons.print.min.js')}}"></script>
<script src="{{asset('js/datatables-buttons/buttons.colVis.min.js')}}"></script>

    <script>

        (function ($) {
            var idClaim = $('#idClaim').val();

            var table = $('#invoice-table').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:true,
                ajax:  {
                    "url" :"{{ route('tuntutan.index.invoiceDatatable') }}",
                    "method": 'POST',
                    "headers": {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    "data": {
                        idClaim: idClaim // Include the idClaim value in the data object
                    }
                },
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'PCIInvNo', data: 'PCIInvNo', class: 'text-center' },
                    { name: 'PCIInvDate', data: 'PCIInvDate', class: 'text-center' },
                    { name: 'PCIInvAmt', data: 'PCIInvAmt', class: 'text-right' }

                ],
                footerCallback: function (tfoot, data, start, end, display) {
                    var total = data.reduce(function (sum, row) {
                        return sum + parseFloat(row.PCIInvAmt);
                    }, 0);

                    $(tfoot).find('th[name="total"]').text(total.toFixed(2));
                }
            });
            table.buttons().container().appendTo('.button-table-export');
        })(jQuery);

        (function ($) {
            var idClaim = $('#idClaim').val();

            var table = $('#checklist-table').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:true,
                ajax:  {
                    "url" :"{{ route('tuntutan.index.checklistDatatable') }}",
                    "method": 'POST',
                    "headers": {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    "data": {
                        idClaim: idClaim // Include the idClaim value in the data object
                    }
                },
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'PCDDesc', data: 'PCDDesc', class: 'dt-body-left' },
                    { name: 'action', data: 'action', class: 'text-center'  },

                ],
            });
            table.buttons().container().appendTo('.button-table-export');
        })(jQuery);

    </script>

</body>

</html>


