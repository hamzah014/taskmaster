@extends('layouts.app')

@push('css')
    <style>
    </style>
@endpush
@section('content')

<div id="kt_app_content_container" class="app-container d-flex justify-content-center align-items-center">
	<div class=" mb-5 mb-xl-10 w-100">
		<div id="kt_account_settings_profile_details">
			<div class="card-body p-9 card bg-section card-no-border">

                <div class="row mb-5">
                    <div class="col-md-12">
                        <a href="{{ route('management.user.index') }}" class="text-dark">
                            <i class="fas fa-chevron-left fs-2 text-dark"></i><b class="ps-3">Back</b>
                        </a>
                    </div>
                </div>

                <div class="row mb-5 d-flex flex-center">

                    <div class="col-md-6 card bg-content card-no-border p-5">

                        <div class="row flex-row mb-4 text-center">
                            <div class="col">
                                <h3 class="text-light">View User</h3>
                            </div>
                        </div>

                        <form class="ajax-form" method="POST" action="{{ route('management.user.update',[$user->USCode]) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" value="{{ $user->USCode }}" id="userCode" name="userCode">
                            <input type="hidden" value="{{ $user->USEmail }}" id="userEmail" name="userEmail">
                            <div class="row p-5">
                                <div class="col-md-12">
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="name" class="form-label text-light">Name :</label>
                                            <input {{ $inputDisabled }} value="{{ $user->USName }}" type="text" name="name" id="name" class="form-control" placeholder="Enter name">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="email" class="form-label text-light">Email :</label>
                                            <input {{ $inputDisabled }} value="{{ $user->USEmail }}" type="text" name="email" id="email" class="form-control" placeholder="Enter email">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="phoneNo" class="form-label text-light">Phone Number :</label>
                                            <input {{ $inputDisabled }} value="{{ $user->USPhoneNo }}" type="text" name="phoneNo" id="phoneNo" class="form-control" placeholder="Enter phone number">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="staffDepart" class="form-label text-light">Role :</label>
                                            {!! Form::select('role', $userRole , $user->US_RLCode, [
                                                'id' => 'role',
                                                'class' => 'form-select form-control',
                                                'placeholder' => 'Choose Role',
                                                $inputDisabled
                                            ]) !!}
                                        </div>
                                    </div>
                                    @if($user->USActive == 0)
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="phoneNo" class="form-label text-light">Deactive Reason :</label>
                                            <input {{ $inputDisabled }} value="{{ $user->USDeactivateReason }}" type="text" name="deactiveReason" id="deactiveReason" class="form-control" placeholder="Enter reason">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="phoneNo" class="form-label text-light">Deactive Date :</label>
                                            <input {{ $inputDisabled }} value="{{ \Carbon\Carbon::parse($user->USDeactivateDate)->format('Y-m-d') }}" type="date" name="deactiveDate" id="deactiveDate" class="form-control" placeholder="Enter reason">
                                        </div>
                                    </div>
                                    @endif
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="staffDepart" class="form-label text-light">Status :</label>

                                            @if($user->USActive == 0)
                                                <span class="badge badge-outline badge-danger">Inactive</span>
                                            @else
                                                <span class="badge badge-outline badge-success">Active</span>

                                            @endif
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row flex-row mt-5 p-5 d-flex flex-center">
                                @if($user->USActive == 1)
                                <div class="col-md-4 text-center">
                                    <a href="#" class="btn-sm btn btn-secondary w-100" onclick="sendLinkReset()">Reset Password</a>
                                </div>
                                <div class="col-md-4 text-center">
                                    <a href="#" class="btn-sm btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#modal-deactive">Block</a>
                                </div>
                                <div class="col-md-4 text-center">
                                    <button type="submit" class="btn-sm btn btn-primary w-100">Save</button>
                                </div>
                                @else
                                <div class="col-md-4 text-center">
                                    <a href="#" class="btn-sm btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#modal-active">Activate</a>
                                </div>

                                @endif
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

    <div class="modal fade" tabindex="-1" id="modal-active">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header p-2 text-end" style="justify-content: flex-end;">
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-close fs-1"></i>
                    </div>
                </div>

                <div class="modal-body">

                    <form id="daftarForm" class="ajax-form" method="POST" action="{{ route('management.user.activeAccount',[$user->USCode]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 text-center d-flex align-items-center justify-content-center">
                                <div class="h-100px w-100px text-center d-flex align-items-center justify-content-center" style="border-radius:100%;background-color:red;">
                                    <i class="fas text-white fa-triangle-exclamation" style="font-size: 3rem;"></i>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-5">
                            <div class="col-md-12 text-center">
                                <h1>Are you sure?</h1>
                            </div>
                        </div>

                        <div class="row mt-5">
                            <div class="col-md-12 text-center">
                                <p>
                                This account will be reactivated. 
                                The user of this account will be able to access it once it is reactivated.
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div>
                                    <button type="submit" class="w-100px btn btn-lg btn-danger text-white mx-10">Yes</button>
                                    <a data-bs-dismiss="modal" aria-label="Close" href="#" class="w-100px btn btn-lg btn-dark text-white mx-10">No</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

	<div class="modal fade" tabindex="-1" id="modal-deactive">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header p-2 text-end" style="justify-content: flex-end;">
					<div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
						<i class="fas fa-close fs-1"></i>
					</div>
				</div>

				<div class="modal-body">

					<div class="row">
						<div class="col-md-12 text-center d-flex align-items-center justify-content-center">
							<div class="h-100px w-100px text-center d-flex align-items-center justify-content-center" style="border-radius:100%;background-color:red;">
								<i class="fas text-white fa-triangle-exclamation" style="font-size: 3rem;"></i>
							</div>
						</div>
					</div>

					<div class="row mt-5">
						<div class="col-md-12 text-center">
							<h1>Are you sure?</h1>
						</div>
					</div>

					<div class="row mt-5">
						<div class="col-md-12 text-center">
							<p>
                            This account will not be accessible after the account is blocked. 
                            Users will need to register again to use our services.
							</p>
						</div>
					</div>

					<div class="row">
						<div class="col-md-12 text-center">
							<div>
								<a data-bs-dismiss="modal" aria-label="Close" data-bs-toggle="modal" data-bs-target="#modal-deactiveReason" href="#" class="w-100px btn btn-lg btn-danger text-white mx-10">Yes</a>
								<a data-bs-dismiss="modal" aria-label="Close" href="#" class="w-100px btn btn-lg btn-dark text-white mx-10">No</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" tabindex="-1" id="modal-deactiveReason">
		
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header p-2">
					<h3 class="modal-title ps-5">Block Account</h3>
					<div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
						<i class="fas fa-close fs-1"></i>
					</div>
				</div>

				<div class="modal-body">
					
					<form id="daftarForm" class="ajax-form" method="POST" action="{{ route('management.user.deactivateAccount',[$user->USCode]) }}" enctype="multipart/form-data">
						@csrf
						<div class="row mb-7">
							<div class="col-md-12">
								<label for="reason">Please describe the reason for blocking the account.:</label>
								<div class="form-floating mb-2">
									<input name="reason" id="reason" type="text" class="form-control">
									<label for="reason">Reason</label>
								</div>
							</div>
						</div>
						<div class="row mt-5">
							<div class="col-md-12 text-center">
								<a class="btn vksb-btn btn-danger text-white" onclick="deactiveAccount()">Submit</a>
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
        
        function sendLinkReset(){
            
            var email = $('#userEmail').val();

            var formData = new FormData();

            formData.append('email',email);

			console.log(formData);
            toggleLoader();

            $.ajax({
                url: "{{ route('forgotPassword.sendLink') }}",
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

                    if (typeof callback == 'function') {
                        callback(resp);
                    } else {

                        if (typeof resp.datatables != "undefined") {
                            resp.datatables.forEach(function(element) {
                                $('#'+element).DataTable().ajax.reload();
                            });
                        }

                        if (resp.message) {
                            var is_html = false;

                            if (resp.html)
                            {
                                is_html = true;
                            }

                    
							swal.fire({
								title: "Success",
								text: resp.message,
								icon: "success",
								showCancelButton: false,
								confirmButtonText: "Done",
								customClass: {
									popup: 'swal-popup'
								}
							}).then((result) => {

								location.reload();

							});
                            

                        }
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

        function deactiveAccount(){
            
            var reason = $('#reason').val();

            var formData = new FormData();

            formData.append('reason',reason);

			console.log(formData);
            toggleLoader();

            $.ajax({
                url: "{{ route('management.user.deactivateAccount',[$user->USCode]) }}",
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

                    if (typeof callback == 'function') {
                        callback(resp);
                    } else {

                        if (typeof resp.datatables != "undefined") {
                            resp.datatables.forEach(function(element) {
                                $('#'+element).DataTable().ajax.reload();
                            });
                        }

                        if (resp.message) {
                            var is_html = false;

                            if (resp.html)
                            {
                                is_html = true;
                            }

                    
							swal.fire({
								title: "Success",
								text: resp.message,
								icon: "success",
								showCancelButton: false,
								confirmButtonText: "Done",
								customClass: {
									popup: 'swal-popup'
								}
							}).then((result) => {

								location.reload();

							});
                            

                        }
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
