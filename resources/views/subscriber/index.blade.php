@extends('layouts.app')

@push('css')
    <style>
    </style>
@endpush
@section('content')


<div id="kt_app_content_container" class="app-container d-flex justify-content-center align-items-center">
	<div class="card mb-5 mb-xl-10 bg-content-card card-no-border bg-transparent w-100">
		<div id="kt_account_settings_profile_details">
			<div class="card-body p-9">


                <div class="row overflow-none card bg-section card-no-border">
					<div class="col-md-12 col-sm-12 mt-5">
                        <div class="d-flex align-items-center mb-0 p-5">
                            <h4 class="text-dark fw-bold cursor-pointer mb-0">
                                Subscriber
                            </h4>
                        </div>
                        <form id="searchForm" action="#">
                            <div class="fs-6 ms-1 p-10" style="">
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label for="search_no" class="form-label">Identification/Register No.</label>
                                        <input type="text" name="search_no" class="form-control" id="search_no" placeholder="Enter Identification/Register No."/>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="search_name" class="form-label">Name</label>
                                        <input type="text" name="search_name" class="form-control" id="search_name" placeholder="Enter name"/>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="search_email" class="form-label">Email</label>
                                        <input type="text" name="search_email" class="form-control" id="search_email" placeholder="Enter email"/>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-12 text-end">
                                        <a href="#" onclick="searchSubs()" class="btn btn-sm btn-dcms">Search</a>
                                    </div>
                                </div>

                            </div>
                        </form>
					</div>
				</div>

                <div class="row flex-row mt-10 card p-15 bg-section card-no-border">

                    <div class="col-md-12 ">
                        <div class="responsive-table">
                            <table class="dcms-table" id="subs-tab">
                                <thead class="">
                                    <tr>
                                        <th>No.</th>
                                        <th>Identification/Register No.</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Action</th>
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

@endsection
@push('script')
    <script>
        (function ($) {

            var table = $('#subs-tab').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering: false,
                ajax:  {
                    "url" :"{{ route('subscriber.subsDatatable') }}",
                    "method": 'POST',
                },
                order: [[1, 'desc']],
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'RGIDNo', data: 'RGIDNo', class: '' },
                    { name: 'RGName', data: 'RGName', class: '' },
                    { name: 'RGEmail', data: 'RGEmail', class: '' },
                    { name: 'action', data: 'action', class: 'text-center' },

                ],
                fnDrawCallback: function(oSettings) {
                    var api = this.api();
                    api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }
            });
            table.buttons().container().appendTo('.button-table-export');

        })(jQuery);

        
        function searchSubs(){

            toggleLoader();
            form = $('#searchForm');
            var formData = new FormData(form[0]);

            //#REINIT-DATATABLE

            if ($.fn.DataTable.isDataTable('#subs-tab')) {
                $('#subs-tab').DataTable().destroy();
            }

            $.ajax({
                url: "{{ route('subscriber.searchSubs') }}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: false,
                data: formData,
                processData: false,
                cache: false,
                success: function (resp) {
                    console.log(resp);
                    toggleLoader();
            
                    var table = $('#subs-tab').DataTable({
                        dom: 'lfrtip',
                        @include('layouts._partials.lengthMenu')
                        processing: true,
                        serverSide: false,
                        data: resp.data,
                        columns: [
                            { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                            { name: 'RGIDNo', data: 'RGIDNo', class: '' },
                            { name: 'RGName', data: 'RGName', class: '' },
                            { name: 'RGEmail', data: 'RGEmail', class: '' },
                            { name: 'action', data: 'action', class: 'text-center' },
                        ],
                        fnDrawCallback: function(oSettings) {
                            var api = this.api();
                            api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                                cell.innerHTML = i + 1;
                            });
                        }
                    });
                    table.buttons().container().appendTo('.button-table-export');
                    
                    toggleLoader();
                    

                },
                error: function (xhr, status) {
                    toggleLoader();
                    var response = xhr.responseJSON;

                    if ( $.isEmptyObject(response.errors) )
                    {
                        var message = response.message;

                        if (! message.length && response.exception)
                        {
                            message = response.exception;
                        }

                        swal.fire("Warning", message, "warning");
                    }
                    else
                    {
                        var errors = '<p  id="fontSize" style="margin-top:2%; margin-bottom:1%; font-size: 25px;"><i>Invalid Information</i></p>';
                        $.each(response.errors, function (key, message) {
                            errors = errors;
                            errors += '<p style="margin-top:2%; margin-bottom:1%">'+message;
                            errors += '</p>';

                            if (key.indexOf('.') !== -1) {

                                var splits = key.split('.');

                                key = '';

                                $.each(splits, function(i, val) {
                                    if (i === 0)
                                    {
                                        key = val;
                                    }
                                    else
                                    {
                                        key += '[' + val + ']';
                                    }
                                });
                            }

                            // $('[name="' + key + '"]').closest('.form-group').addClass("has-error");
                            // $('[name="' + key + '"]').addClass("was-validated is-invalid invalid custom-select.is-invalid");
                            // $('#Valid'+key).empty();
                            // $('[name="' + key + '"]').closest('.form-group').append("<span id='Valid"+key+"' class=\"help-block\" style='color:red; font-family:Nunito, sans-serif;'>" + message[0] + "</span>");
                        });
                        swal.fire("Warning", errors, "warning",{html:true});
                        $('html, body').animate({
                            scrollTop: ($(".has-error").first().offset().top) - 200
                        }, 500);
                    }
                }
            });

        }

    </script>
@endpush
