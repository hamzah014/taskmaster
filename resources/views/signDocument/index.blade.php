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
                                Signed Document List
                            </h4>
                        </div>
                        <form id="searchForm" action="#">
                            <div class="fs-6 ms-1 p-10" style="">
                                <div class="row">
                                    <div class="col-md-4 mb-5">
                                        <label for="search_docno" class="form-label">Sign Document No : </label>
                                        <input type="text" name="search_docno" class="form-control" id="search_docno" placeholder="Write sign Document No."/>
                                    </div>
                                    <div class="col-md-4 mb-5">
                                        <label for="search_certno" class="form-label">Certificate No : </label>
                                        <input type="text" name="search_certno" class="form-control" id="search_certno" placeholder="Write certificate No."/>
                                    </div>
                                    <div class="col-md-4 mb-5">
                                        <label for="search_ic" class="form-label">Identification/Register No : </label>
                                        <input type="text" name="search_ic" class="form-control" id="search_ic" placeholder="Write identification/register"/>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 text-end">
                                        <a href="#" onclick="searchSignDocLog()" class="btn btn-sm btn-dcms">Search</a>
                                    </div>
                                </div>

                            </div>
                        </form>
					</div>
				</div>

                <div class="row flex-row mt-10 card p-5 bg-section card-no-border">
                    <div class="col-md-12 ">
                        <div class="responsive-table">
                            <table class="table table-sm bg-light table-striped" id="signDoc-tab">
                                <thead class="table-head text-light">
                                    <tr>
                                        <th>No.</th>
                                        <th>Date Document Signed</th>
                                        <th>Identification/Register No.</th>
                                        <th>Sign Document No.</th>
                                        <th>Certificate No.</th>
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
            var table = $('#signDoc-tab').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:true,
                ajax:  {
                    "url" :"{{ route('signDocument.signDocDatatable') }}",
                    "method": 'POST',
                    "headers": {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                },
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'SDMD', data: 'SDMD', class: 'text-center' },
                    { name: 'CEIDNo', data: 'CEIDNo', class: 'text-center' },
                    { name: 'SDNo', data: 'SDNo', class: 'text-center' },
                    { name: 'SD_CENo', data: 'SD_CENo', class: 'text-center'},
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

        function searchSignDocLog(){
            var search_docno = $('#search_docno').val();
            var search_certno = $('#search_certno').val();
            var search_ic = $('#search_ic').val();

            var formData = new FormData();
            // Append data to the FormData object
            formData.append('search_docno', search_docno);
            formData.append('search_certno', search_certno);
            formData.append('search_ic', search_ic);

            console.log("Form data before AJAX request:", formData);

            if ($.fn.DataTable.isDataTable('#signDoc-tab')) {
                // If it exists, destroy it first
                $('#signDoc-tab').DataTable().destroy();
            }
            toggleLoader();

            $.ajax({
                url: "{{ route('signDocument.searchFilter') }}",
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
                    var table = $('#signDoc-tab').DataTable({
                        dom: 'lfrtip',
                        @include('layouts._partials.lengthMenu')
                        processing: true,
                        serverSide: false,
                        data: resp.data,
                        columns: [
                            { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                            { name: 'SDMD', data: 'SDMD', class: 'text-center' },
                            { name: 'CEIDNo', data: 'CEIDNo', class: 'text-center' },
                            { name: 'SDNo', data: 'SDNo', class: 'text-center' },
                            { name: 'SD_CENo', data: 'SD_CENo', class: 'text-center'},
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
