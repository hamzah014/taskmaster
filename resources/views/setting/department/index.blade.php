@extends('layouts.appSetting')

@push('css')
@endpush
@section('content')
    <div id="breadcrumbs-wrapper" class="pt-0" >
        <div class="col s12 breadcrumbs-left">
            <ol class="breadcrumbs mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home')}}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('Senarai Jabatan')}}
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
                                        <h4 class="card-title">{{ __('Senarai Jabatan')}}</h4>
                                    </span>
                                    <table class="speed_mini table_clickable" id="department-table">
                                        <thead>
                                            <tr>
                                                <th>Kod Jabatan</th>
                                                <th>Nama Jabatan</th>
                                                <th>Emel Jabatan</th>
                                                <th>Ketua Jabatan</th>
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
            var table = $('#department-table').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:false,
                ajax:  {
                    "url" :"{{ route('setting.department.departmentDatatable') }}",
                    "method": 'POST',
                },
                columns: [
                    { name: 'DPTCode', data: 'DPTCode', class: 'text-center'},
                    { name: 'DPTDesc', data: 'DPTDesc', class: 'text-left' },
                    { name: 'DPTEmail', data: 'DPTEmail', class: 'text-center' },
                    { name: 'DPTHead_USCode', data: 'DPTHead_USCode', class: 'text-center' },
                    { name: 'DPTActive', data: 'DPTActive', class: 'text-center' },
                ]
            });
            table.buttons().container().appendTo('.button-table-export');

        })(jQuery);
    </script>
@endpush
