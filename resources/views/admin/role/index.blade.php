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

                <div class="row flex-row mb-5">
                    <div class="col-md-12">
                        <h2>User Role</h2>
                    </div>
                </div>

                <div class="row flex-row mt-10 card p-5 bg-section card-no-border">
                    <div class="col-md-12 mb-5">
                        <h4>List of Users</h4>
                    </div>
                    <div class="col-md-12 ">
                        <div class="responsive-table">
                            <table class="tm-table" id="role-tab">
                                <thead class="table-head text-light">
                                    <tr>
                                        <th>No.</th>
                                        <th>Name</th>
                                        <th>Role</th>
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

@push('modals')

    <div class="modal fade" id="modal-user"  data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered justify-content-center">
            <div class="modal-content w-80">
                <div class="modal-header">
                    <h3 class="modal-title">View User Information</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-close fs-1"></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="resultForm" class="ajax-form" method="POST" action="{{ route('admin.role.saveUserRole') }}" enctype="multipart/form-data">
                        <input type="hidden" name="resultID" id="resultID" value="0">
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input readonly type="text" class="form-control" name="resultName" id="resultName" placeholder="Result name"/>
                                    <label for="resultName">Name</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="form-floating">
                                    {!! Form::select('resultRole', $userRole , null, [
                                        'id' => 'resultRole',
                                        'class' => 'form-select form-control',
                                        'placeholder' => 'Choose role',
                                    ]) !!}
                                    <label for="resultRole">User Role</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-info btn-sm w-25">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


@endpush

@push('script')
    <script>
        (function ($) {

            var table = $('#role-tab').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:false,
                ajax:  {
                    "url" :"{{ route('admin.role.userDatatable') }}",
                    "method": 'POST',
                },
                order: [[1, 'desc']],
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'USName', data: 'USName', class: 'text-center' },
                    { name: 'role', data: 'role', class: 'text-center' },
                    { name: 'action', data: 'action', class: 'text-center' },

                ]
            });
            table.buttons().container().appendTo('.button-table-export');

        })(jQuery);

    </script>

    <script>

        function viewUser(id){

            var formData = new FormData();
            formData.append('userID', id);

            toggleLoader();

            $.ajax({
                url: "{{ route('admin.role.checkUser') }}",
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

                    console.log('Server response:', resp);
                    if(resp.success == true){

                        user = resp.user;
                        console.log(user.USName);
                        $('#resultName').val(user.USName);
                        $('#resultRole').val(user.US_RLCode);
                        $('#resultID').val(user.USCode);

                    }

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
