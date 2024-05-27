@extends('layouts.app')

@push('css')
    <style>
    </style>
@endpush
@section('content')


<div id="kt_app_content_container" class="app-container d-flex justify-content-center align-items-center">
	<div class=" mb-5 mb-xl-10 bg-content-card card-no-border bg-cert w-100">
		<div id="kt_account_settings_profile_details">
			<div class="p-9">

                <div class="card mb-5 mb-xl-10">
                    <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_profile_details" aria-expanded="true" aria-controls="kt_account_profile_details">
                        <div class="card-title m-0">
                            <h3 class="fw-bold m-0">Profile Details</h3>
                        </div>
                    </div>
                    <div id="kt_account_settings_profile_details" class="collapse show">
                        <form id="profileForm" class="ajax-form" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body border-top p-9">
                                <div class="row mb-6">
                                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Profile Photo</label>
                                    <div class="col-lg-8">
                                        <div class="image-input image-input-outline" data-kt-image-input="true" style="background-image: url('assets/media/svg/avatars/blank.svg')">
                                            <div class="image-input-wrapper w-125px h-125px" style="background-image: url({{ isset($profilePhotoURL) && $profilePhotoURL != '' ? $profilePhotoURL : asset('assets/images/avatar/avatar-0.png')}})"></div>
                                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                                <i class="ki-duotone ki-pencil fs-7">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                                <input type="file" name="avatar" accept=".png, .jpg, .jpeg" id="avatar"/>
                                                <input type="hidden" name="avatar_remove" />
                                            </label>
                                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                                                <i class="ki-duotone ki-cross fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </span>
                                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                                                <i class="ki-duotone ki-cross fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </span>
                                        </div>
                                        <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <label class="col-md-4 col-form-label required fw-semibold fs-6">Full Name</label>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-12 fv-row">
                                                <input type="text" name="name" id="name" class="form-control mb-3 mb-lg-0" placeholder="Enter full name." value="{{ $user->USName }}" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <label class="col-md-4 col-form-label required fw-semibold fs-6">Staff ID</label>
                                    <div class="col-md-8 fv-row">
                                        <input type="text" name="staffID" id="staffID" class="form-control" placeholder="Enter staff ID" value="{{ $user->US_StaffID }}" />
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <label class="col-md-4 col-form-label required fw-semibold fs-6">Department</label>
                                    <div class="col-md-8 fv-row">
                                        {!! Form::select('department', $department , $user->USDepartment , [
                                            'id' => 'department',
                                            'class' => 'form-select form-control',
                                            'placeholder' => 'Choose department',
                                        ]) !!}

                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-6">
                                    <label class="col-md-4 col-form-label required fw-semibold fs-6">Email</label>
                                    <div class="col-md-8 fv-row">
                                        <input type="text" name="email" id="email" class="form-control" placeholder="Enter user email" value="{{ $user->USEmail }}" readonly/>
                                    </div>
                                </div>
                                <div class="row mb-6">
                                    <label class="col-md-4 col-form-label required fw-semibold fs-6">Password</label>
                                    <div class="col-md-8 fv-row">
                                        <div class="input-group mb-5">
                                            <input readonly type="text" class="form-control" placeholder="Change password" aria-label="Change password" aria-describedby="basic-addon2"/>
                                            <span class="input-group-text cursor-pointer" id="basic-addon2" data-bs-toggle="modal" data-bs-target="#modal-password">
                                                <i class="fa fa-pen fs-4"><span class="path1"></span><span class="path2"></span></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-end py-6 px-9">
                                <a onclick="updateInfo()" class="btn btn-primary btn-sm">Save Changes</a>
                            </div>
                        </form>
                    </div>
                </div>

			</div>
		</div>
	</div>
</div>
@endsection

@push('modals')


    <div class="modal fade" id="modal-password" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered justify-content-center modal-dialog-scrollable w-100">
            <div class="modal-content w-80">
                <div class="modal-header">
                    <h3 class="modal-title">Reset Password</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-close fs-1"></i>
                    </div>
                </div>

                <div class="modal-body">
                    <div class="w-100">

                        <form id="passwordForm" class="form">
                            @csrf

                            <div class="row mb-6">
                                <label class="col-md-4 col-form-label fw-semibold fs-6">New Password</label>
                                <div class="col-md-8 fv-row">
                                    <input type="password" name="newPassword" id="newPassword" class="form-control" placeholder="Enter new password">
                                </div>
                            </div>

                            <div class="row mb-6">
                                <label class="col-md-4 col-form-label fw-semibold fs-6">Confirm Password</label>
                                <div class="col-md-8 fv-row">
                                    <input type="password" name="confirmPassword" id="confirmPassword" class="form-control" placeholder="Confirm new password">
                                </div>
                            </div>

                            <div class="fv-row text-end">
                                <a onclick="updatePassword()" class="btn btn-sm btn-success">Submit</a>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endpush

@push('script')

    <script>

        userCode = '{{ $user->USCode }}';

        function updatePassword(){

            form = $('#passwordForm');
            var formData = new FormData(form[0]);

            formData.append('userCode',userCode);
            toggleLoader();

            $.ajax({
                url: "{{ route('profile.resetPassword') }}",
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

        function updateInfo(){

            form = $('#profileForm');
            var formData = new FormData(form[0]);

            formData.append('userCode',userCode);
            toggleLoader();

            $.ajax({
                url: "{{ route('profile.update') }}",
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
