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
                                Certificate List
                            </h4>
                        </div>
                        <form id="searchForm" action="#">
                            <div class="fs-6 ms-1 p-10" style="">
                                <div class="row">
                                    <div class="col-md-4 mb-5">
                                        <label for="search_name" class="form-label">Project : </label>
                                        {!! Form::select('search_project', $projekCert, null, [
                                            'id' => 'search_project',
                                            'class' => 'form-control',
                                            'placeholder' => trans('message.dropdown_default'),
                                        ]) !!}
                                    </div>
                                    <div class="col-md-4 mb-5">
                                        <label for="search_certno" class="form-label">Certificate No : </label>
                                        <input type="text" name="search_certno" class="form-control" id="search_certno" placeholder="Write certificate No."/>
                                    </div>
                                    <div class="col-md-4 mb-5">
                                        <label for="search_name" class="form-label">Status</label>
                                        {!! Form::select('search_status', $statusActive, null, [
                                            'id' => 'search_status',
                                            'class' => 'form-control',
                                            'placeholder' => trans('message.dropdown_default'),
                                        ]) !!}
                                    </div>
                                    <div class="col-md-4 mb-5">
                                        <label for="search_ic" class="form-label">Identification/Register No : </label>
                                        <input type="text" name="search_ic" class="form-control" id="search_ic" placeholder="Write identification/register"/>
                                    </div>
                                    <div class="col-md-4 mb-5">
                                        <label for="search_name" class="form-label">Subscriber Name : </label>
                                        <input type="text" name="search_name" class="form-control" id="search_name" placeholder="Write subscriber name"/>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-12 text-end">
                                        <a href="#" onclick="searchCertificateLog()" class="btn btn-sm btn-dcms">Search</a>
                                    </div>
                                </div>

                            </div>
                        </form>
					</div>
				</div>

                <div class="row flex-row mt-10 card p-5 bg-section card-no-border">
                    <div class="col-md-12 ">
                        <div class="responsive-table">
                            <table class="table table-sm bg-light table-striped" id="certificate-tab">
                                <thead class="table-head text-light">
                                    <tr>
                                        <th>Bil.</th>
                                        <th>Project</th>
                                        <th>Certificate No.</th>
                                        <th>Identification/Register No.</th>
                                        <th>Subscriber Name</th>
                                        <th>Status</th>
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
            var table = $('#certificate-tab').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:true,
                ajax:  {
                    "url" :"{{ route('certificate.certDatatable') }}",
                    "method": 'POST',
                    "headers": {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                },
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'PJDesc', data: 'PJDesc', class: 'text-left' },
                    { name: 'CENo', data: 'CENo', class: 'text-center' },
                    { name: 'CEIDNo', data: 'CEIDNo', class: 'text-center' },
                    { name: 'CEName', data: 'CEName', class: 'text-left'},
                    { name: 'status', data: 'status', class: 'text-center'},
                    { name: 'action', data: 'action', class: 'text-center'},
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

        function searchCertificateLog(){
            var search_project = $('#search_project').val();
            var search_certno = $('#search_certno').val();
            var search_status = $('#search_status').val();
            var search_ic = $('#search_ic').val();
            var search_name = $('#search_name').val();

            var formData = new FormData();
            // Append data to the FormData object
            formData.append('search_project', search_project);
            formData.append('search_certno', search_certno);
            formData.append('search_status', search_status);
            formData.append('search_ic', search_ic);
            formData.append('search_name', search_name);

            console.log("Form data before AJAX request:", formData);

            if ($.fn.DataTable.isDataTable('#certificate-tab')) {
                // If it exists, destroy it first
                $('#certificate-tab').DataTable().destroy();
            }
            toggleLoader();

            $.ajax({
                url: "{{ route('certificate.searchFilter') }}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: false,
                data: formData,
                processData: false,
                cache: false,
                success: function (resp) {
                    console.log("Response from server:", resp);
                    toggleLoader();
                    var table = $('#certificate-tab').DataTable({
                        dom: 'lfrtip',
                        @include('layouts._partials.lengthMenu')
                        processing: true,
                        serverSide: false,
                        data: resp.data,
                        columns: [
                            { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                            { name: 'PJDesc', data: 'PJDesc', class: 'text-left' },
                            { name: 'CENo', data: 'CENo', class: 'text-center' },
                            { name: 'CEIDNo', data: 'CEIDNo', class: 'text-center' },
                            { name: 'CEName', data: 'CEName', class: 'text-left'},
                            { name: 'status', data: 'status', class: 'text-center'},
                            { name: 'action', data: 'action', class: 'text-center'},
                        ],
                        fnDrawCallback: function(oSettings) {
                            var api = this.api();
                            api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                                cell.innerHTML = i + 1;
                            });
                        }
                    });
                    table.buttons().container().appendTo('.button-table-export');
                },
                error: function (xhr, status) {
                    toggleLoader();
                    var response = xhr.responseJSON;
                    if ( $.isEmptyObject(response.errors) ){
                        var message = response.message;

                        if (! message.length && response.exception){
                            message = response.exception;
                        }

                        swal.fire("Warning", message, "warning");
                    }
                    else{
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
