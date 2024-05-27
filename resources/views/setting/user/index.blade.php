@extends('layouts.app')

@push('css')
@endpush
@section('content')
    <div id="breadcrumbs-wrapper" class="pt-0" >
        <div class="col s12 breadcrumbs-left">
            <ol class="breadcrumbs mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home')}}</a>
                </li>
                    <li class="breadcrumb-item active">{{ __('Senarai Pengguna')}}
                </li>
            </ol>
        </div>
    </div>
    <div class="section">
        <div class="section">
            <div class="row" >
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <div class="row">
                                <div class="col s12">
                                    <span class="header-button">
                                        <h4 class="card-title">{{ __('Senarai Pengguna')}}</h4>
                                        <div style="text-align:right;">
                                            <a href="{{ route('setting.user.create.director') }}" class="btn waves-effect waves-light btn btn-light-primary">
                                                <i class="material-icons left">add</i>Director
                                            </a>
                                            <a href="{{ route('setting.user.create') }}" class="btn waves-effect waves-light btn btn-light-primary">
                                                <i class="material-icons left">add</i>Pengguna
                                            </a>
                                        </div>
                                    </span>
                                    <table class="speed_mini table_clickable" id="userMU-table">
                                        <thead>
                                        <tr>
                                            <th>Kod Pengguna</th>
                                            <th>Nama Pengguna</th>
                                            <th>No. Tel Pengguna</th>
                                            <th>Emel Pengguna</th>
                                            <th>Status</th>
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
@endsection
@push('script')
    <script>

        (function ($) {
            var table = $('#userMU-table').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:false,
                ajax:  {
                    "url" :"{{ route('setting.user.userMUDatatable') }}",
                    "method": 'POST',
                },
                columns: [
                    { name: 'USCode', data: 'USCode', class: 'text-center'},
                    { name: 'USName', data: 'USName', class: 'text-left' },
                    { name: 'USPhoneNo', data: 'USPhoneNo', class: 'text-center' },
                    { name: 'USEmail', data: 'USEmail', class: 'text-center' },
                    { name: 'USActive', data: 'USActive', class: 'text-center' },
                ]
            });
            table.buttons().container().appendTo('.button-table-export');

        })(jQuery);
    </script>
@endpush
