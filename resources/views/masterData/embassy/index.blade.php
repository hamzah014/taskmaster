@extends('layouts.app')

@push('css')
    <style>
        tfoot {
            display: table-header-group;
        }
    </style>
@endpush
@section('content')
    <div class="content-wrapper-before gradient-45deg-deep-purple-purple"></div>
    <div class="breadcrumbs-inline pt-3 pb-1" id="breadcrumbs-wrapper">
        <!-- Search for small screen-->
        <div class="container">
            <div class="row">
                <div class="col s10 m6 l6 breadcrumbs-left">
                    <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down">
                        <span>{{ __('Embassy') }}</span>
                    </h5>
                    <ol class="breadcrumbs mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Embassy') }}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="section section-data-tables">
            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <div class="col m6 s6" style=" padding:5px">
                                <h4 class="card-title">{{ __('List of Embassies') }}</h4>
                            </div>
                            <div class="col s6 m6 l6" style="text-align:right; padding:5px">
                                @if (auth()->user()->USCode == 'SA')
                                    <a href="{{ route('masterData.embassy.create') }}"
                                        class="waves-effect waves-light btn gradient-45deg-indigo-light-blue">
                                        <i class="material-icons right">add_circle_outline</i>
                                        {{ __('New Embassy') }}</a>
                                @endif
                            </div>
                            <div class="row">
                                <div class="col s12">
                                    <table id="page-length-option" class="display table-responsive"
                                        style="display: block; width:100%; max-height: 1500px;overflow-x: scroll;">
                                        <thead>
                                            <tr>
                                                <th width="5px">No.</th>
                                                <th class="dt_head_center">{{ __('Code') }}</th>
                                                <th class="dt_head_center">{{ __('Embassy Name') }}</th>
                                                <th class="dt_head_center">{{ __('Embassy Country') }}</th>
                                                <th class="dt_head_center">{{ __('Active') }}</th>
                                                <th class="dt_head_center">{{ __('Created Date') }}</th>
                                                <th class="dt_head_center">{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <th width="5px">No.</th>
                                                <th class="dt_head_center">{{ __('Code') }}</th>
                                                <th class="dt_head_center">{{ __('Embassy Name') }}</th>
                                                <th class="dt_head_center">{{ __('Embassy Country') }}</th>
                                                <th class="dt_head_center">{{ __('Active') }}</th>
                                                <th class="dt_head_center">{{ __('Created Date') }}</th>
                                                <th class="dt_head_center">{{ __('Action') }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <br />
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
        (function($) {
            var dt_export_filename = 'Staff';
            var dt_export_orientation = 'landscape';
            var dt_export_pageSize = 'A4';

            $('#page-length-option tfoot th').each(function(i) {
                var title = $(this).text();
                if (i == 0) {
                    $(this).html('<span style="background-color: white"> </span>');
                } else {
                    var title = $(this).text();
                    $(this).html(
                        '<input style="text-align:center;" type="text" placeholder=" {{ __('Search') }} ' +
                        title + '" />');

                }
            });

            var table = $('#page-length-option').DataTable({
                dom: 'Blrtip',
                @include('layouts._partials.lengthMenu')
                @include('layouts._partials.dt_buttonConfigBlank')
                processing: true,
                serverSide: false,
                ordering: true,
                ajax: {
                    "url": "{{ route('masterData.embassy.datatable') }}",
                    "method": 'POST',
                    // error callback to handle error
                    "error": function(xhr, error, thrown) {
                        console.log("Error occurred!");
                        console.log(xhr, error, thrown);
                    }
                },
                columns: [{
                        name: 'DT_RowIndex',
                        data: 'DT_RowIndex',
                        orderable: true,
                        searchable: false,
                        class: 'dt-body-center'
                    },
                    {
                        name: 'EMCode',
                        data: 'EMCode',
                        class: 'dt-body-left'
                    },
                    {
                        name: 'EMName',
                        data: 'EMName',
                        class: 'dt-body-left'
                    },
                    {
                        name: 'EM_CTCode',
                        data: 'EM_CTCode',
                        class: 'dt-body-left'
                    },
                    {
                        name: 'EMActive',
                        data: 'EMActive',
                        class: 'dt-body-left'
                    },
                    {
                        name: 'EMCD.timestamp',
                        data: {
                            '_': 'EMCD.display',
                            'sort': 'EMCD.timestamp'
                        }
                    },
                    {
                        name: 'type',
                        data: 'action',
                        class: 'dt-body-center',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
            table.buttons().container().appendTo('.button-table-export');
        })(jQuery);
    </script>
@endpush
