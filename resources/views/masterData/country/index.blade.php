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
                    <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down"><span>{{ __('Country')}}</span></h5>
                    <ol class="breadcrumbs mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home')}}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Country')}}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="col s12">
        <div class="container">
            <div class="section section-data-tables">
                <div class="row">
                    <div class="col s12">
                        <div class="card">
                            <div class="card-content">
                                <div class="col m6 s6" style=" padding:5px"> <h4 class="card-title">{{ __('List of Country')}}</h4></div>
								<div class="col s6 m6 l6" style="text-align:right; padding:5px">
									<a href="{{ route('masterData.country.create') }}" class="waves-effect waves-light btn gradient-45deg-indigo-light-blue">
										<i class="material-icons right">add_circle_outline</i> {{ __('New Country')}}</a>
								</div>
                                <div class="row">
                                    <div class="col s12">
                                        <table id="page-length-option" class="display table-responsive" style="display: block; width:100%; max-height: 1500px;overflow-x: scroll;">
                                            <thead>
                                            <tr>
                                                <th width="5px">No.</th>
                                                <th>{{ __('Code')}}</th>
                                                <th>{{ __('Country')}}</th>
                                                <th>{{ __('Active')}}</th>
                                                <th>{{ __('Created By')}}</th>
                                                <th>{{ __('Created Date')}}</th>
                                                <th>{{ __('Modified By')}}</th>
                                                <th>{{ __('Modified Date')}}</th>
                                                <th>{{ __('Action')}}</th>
                                            </tr>
                                            </thead>
                                            <tfoot>
                                            <tr>
                                                <th width="5px">No.</th>
                                                <th>{{ __('Code')}}</th>
                                                <th>{{ __('Country')}}</th>
                                                <th>{{ __('Active')}}</th>
                                                <th>{{ __('Created By')}}</th>
                                                <th>{{ __('Created Date')}}</th>
                                                <th>{{ __('Modified By')}}</th>
                                                <th>{{ __('Modified Date')}}</th>
                                                <th>{{ __('Action')}}</th>
                                            </tr>
                                            </tfoot>
                                        </table>
										</div>
										<br/>
									</div>
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
            var dt_export_filename = 'Users';
            var dt_export_orientation = 'landscape';
            var dt_export_pageSize = 'A4';

            $('#page-length-option tfoot th').each( function (i) {
                var title = $(this).text();
                if (i == 0 || i== 3 || i== 8) {
                    $(this).html('<span style="background-color: white"> </span>');
                } else {
                    var title = $(this).text();
                    $(this).html('<input style="text-align:center;" type="text" placeholder=" {{ __('Search')}} ' + title + '" />');

                }
            } );


            var table = $('#page-length-option').DataTable({
                dom: 'Blrtip',
                @include('layouts._partials.lengthMenu')
                @include('layouts._partials.dt_buttonConfigBlank')
                processing: true,
                serverSide: false,
                ordering:true,
                ajax:  {
                    "url" :"{{ route('masterData.country.datatable') }}",
                    "method": 'POST',
                    // error callback to handle error
                    "error": function(xhr, error, thrown) {
                        console.log("Error occurred!");
                        console.log(xhr, error, thrown);
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, class: 'text-center' },
                    { data: 'code', name: 'code', class: 'text-left' },
                    { data: 'desc', name: 'desc', class: 'text-left' },
                    { data: 'isActive', name: 'isActive', class: 'text-center' },
                    { data: 'createdBy', name: 'createdBy', class: 'text-center' },
                    { data: 'createdDate', name: 'createdDate', class: 'text-center' },
                    { data: 'modifyBy', name: 'modifyBy', class: 'text-center' },
                    { data: 'modifyDate', name: 'modifyDate', class: 'text-center' },
                    { data: 'action', name: 'type', class: 'dt-body-center', orderable: false, searchable: false },
                ]
            });
            table.buttons().container().appendTo('.button-table-export');
        })(jQuery);
    </script>
@endpush