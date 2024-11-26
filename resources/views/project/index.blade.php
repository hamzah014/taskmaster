@extends('layouts.app')

@push('css')
    <style>
    </style>
@endpush
@section('content')


<div id="kt_app_content_container" class="app-container d-flex justify-content-center align-items-center">
	<div class="card mb-5 mb-xl-10 w-100 mt-5">
		<div id="kt_account_settings_profile_details">
			<div class="card-body p-9">

                <div class="row flex-row mb-5">
                    <div class="col-md-9">
                        <h2>Project Builder</h2>
                    </div>
                    <div class="col-md-3 text-end">
                        <a href="{{ route('project.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Create</a>
                    </div>
                </div>

                <div class="row flex-row mb-5">
                    <div class="col-md-12">
                        <h5>List of Projects :</h5>
                    </div>
                    <div class="col-md-12">
                        <table class="table table-bordered text-center border-dark" id="project-tab">
                            <thead class="text-center bg-gray">
                                <th class="text-center">No.</th>
                                <th class="text-center">Code</th>
                                <th class="text-center">Name</th>
                                <th class="text-center">Description</th>
                                <th class="text-center">Start Date</th>
                                <th class="text-center">End Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </thead>
                        </table>
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

            var table = $('#project-tab').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:false,
                ajax:  {
                    "url" :"{{ route('project.projectDatatable') }}",
                    "method": 'POST',
                },
                order: [[1, 'desc']],
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'PJCode', data: 'PJCode', class: 'text-center' },
                    { name: 'PJName', data: 'PJName', class: 'text-center' },
                    { name: 'PJDesc', data: 'PJDesc', class: 'text-start' },
                    { name: 'PJStartDate', data: 'PJStartDate', class: 'text-center' },
                    { name: 'PJEndDate', data: 'PJEndDate', class: 'text-center' },
                    { name: 'PJStatus', data: 'PJStatus', class: 'text-center' },
                    { name: 'action', data: 'action', class: 'text-center' },

                ]
            });
            table.buttons().container().appendTo('.button-table-export');

        })(jQuery);


        function projectDelete(code)
        {

            swal.fire({
                title: 'Are you sure?',
                text: "This project will be deleted.",
                type: 'warning',
                icon: "warning",
                showCancelButton: false,
                showCloseButton: true,
                showDenyButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                denyButtonText: `Cancel`,
                confirmButtonText: 'Yes,delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitProjectDelete(code);
                    }
            });


        }

        function submitProjectDelete(code){

            projectCode = code;

            var formData = new FormData();

            formData.append('projectCode',projectCode);;
            toggleLoader();

            $.ajax({
                url: "{{ route('project.deleteProject') }}",
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

                    swal.fire({
                        title: "Success",
                        text: resp.message,
                        icon: "success",
                        showCancelButton: false,
                        confirmButtonText: "Okay",
                        customClass: {
                            popup: 'swal-popup'
                        }
                    }).then((result) => {

                        routeHref = resp.redirect;

                        window.location.href = routeHref;

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
