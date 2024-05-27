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
                            <h4 class="text-dark fw-bold cursor-pointer fs-2x mb-0">
                                Document Signs Report
                            </h4>
                        </div>
                        <form id="searchSign" action="#">
                            <div class="fs-6 ms-1 p-10" style="">
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label for="search_signNo" class="form-label">Document Sign No.:</label>
                                        <input type="text" name="search_signNo" class="form-control" id="search_signNo" placeholder="Enter document sign no:"/>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="search_certno" class="form-label">Certificate No.:</label>
                                        <input type="text" name="search_certno" class="form-control" id="search_certno" placeholder="Enter certificate no:"/>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="search_id" class="form-label">Identification/Register No:</label>
                                        <input type="text" name="search_id" class="form-control" id="search_id" placeholder="Enter Identification/Register No:"/>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="search_dateFrom" class="form-label">Document Signed From:</label>
                                        <input type="date" name="search_dateFrom" class="form-control" id="search_dateFrom" placeholder="Enter date"/>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="search_dateTo" class="form-label">Document Signed To:</label>
                                        <input type="date" name="search_dateTo" class="form-control" id="search_dateTo" placeholder="Enter date"/>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 text-end">
                                        <a href="#" onclick="searchReportSign()" class="btn btn-sm btn-dcms">Search</a>
                                    </div>
                                </div>

                            </div>
                        </form>
					</div>
				</div>

                <div class="row flex-row mt-10 card p-5 bg-section card-no-border d-none" id="resultReportSign">

                    <div class="col-md-12 text-end mb-5">
                        <a class="btn btn-primary btn-sm text-white" id="btnGenerateSign" onclick="janaReport(this)">
                            Generate
                        </a>
                    </div>

                    <div class="col-md-12 ">
                        <div class="responsive-table">
                            <table class="dcms-table" id="sign-tab">
                                <thead class="table-head text-light">
                                    <tr>
                                        <th>No.</th>
                                        <th>Document Sign No.</th>
                                        <th>Document Signed Date</th>
                                        <th>Certificate No.</th>
                                        <th>ID/Register No.</th>
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

            var table = $('#sign-tab').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:false,
                ajax:  {
                    "url" :"{{ route('report.sign.reportSignDatatable') }}",
                    "method": 'POST',
                },
                order: [[1, 'desc']],
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'SDNo', data: 'SDNo', class: 'text-center' },
                    { name: 'SDCD', data: 'SDCD', class: '' },
                    { name: 'CENo', data: 'CENo', class: 'text-center' },
                    { name: 'CEIDNo', data: 'CEIDNo', class: 'text-center' },

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

        
        function searchReportSign(){

            toggleLoader();
            form = $('#searchSign');
            var formData = new FormData(form[0]);
            
            if ($.fn.DataTable.isDataTable('#sign-tab')) {
                $('#sign-tab').DataTable().destroy();
            }

            $.ajax({
                url: "{{ route('report.sign.searchReportSign') }}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: false,
                data: formData,
                processData: false,
                cache: false,
                success: function (resp) {
                    console.log(resp, 'heheh');
                    toggleLoader();
                    
                    var table = $('#sign-tab').DataTable({
                        dom: 'lfrtip',
                        @include('layouts._partials.lengthMenu')
                        processing: true,
                        serverSide: false,
                        ordering:false,
                        data: resp.dataTable.original.data,
                        order: [[1, 'desc']],
                        columns: [
                            { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                            { name: 'SDNo', data: 'SDNo', class: 'text-center' },
                            { name: 'SDCD', data: 'SDCD', class: '' },
                            { name: 'CENo', data: 'CENo', class: 'text-center' },
                            { name: 'CEIDNo', data: 'CEIDNo', class: 'text-center' },

                        ],
                        fnDrawCallback: function(oSettings) {
                            var api = this.api();
                            api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                                cell.innerHTML = i + 1;
                            });
                        }
                    });
                    table.buttons().container().appendTo('.button-table-export');

                    $('#resultReportSign').removeClass('d-none');
                    
                    if (resp.redirectGenerate) {
                        $('#btnGenerateSign').attr('value', resp.redirectGenerate);
                    }
                    
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
     
        function janaReport(button){

            href = $(button).attr('value');
            console.log(href);
            toggleLoader();
            
            $.ajax({
                url: href,
                type: 'GET',
                contentType: false,
                processData: false,
                cache: false,
                success: function (resp) {
                    console.log(resp);   
                    toggleLoader();
                    
                    swal.fire({
                        title: "Success",
                        text: resp.message,
                        icon: "success",
                        showCancelButton: false,
                        confirmButtonText: "Okay",
                        customClass: {
                            popup: 'swal-popup'
                        }
                    });
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
