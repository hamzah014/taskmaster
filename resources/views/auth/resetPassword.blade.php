<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
    
    <head>
        <title>DCMS</title>
        @include('layouts._partials.head')

        <style>
        </style>

    </head>

	
	<body id="kt_body" class="app-blank d-block" data-kt-app-page-loading-enabled="true" data-kt-app-page-loading="on"  
    style="background-image: url('{{ asset('assets/images/background/background-orange.png') }}');
  background-repeat: no-repeat;
  background-size: cover;">
		
		<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
			<div class="app-page flex-column flex-column-fluid" id="kt_app_page">
				
				<div class="d-flex align-items-center flex-column-fluid flex-lg-row-auto justify-content-center">
					<div class="d-flex flex-column align-items-stretch flex-center rounded-4 w-50 h-100 mt-15 card p-5 bg-forgot-card">
						<div class="d-flex justify-content-center align-items-center p-10">
							<div class="form w-100">
								
								<form id="daftarForm" class="ajax-form"  method="POST" action="{{ route('resetPassword.update',[$token]) }}" enctype="multipart/form-data">
                                    @csrf
									<div class="text-center mb-11 mt-15">
										<h1 class="text-dark fw-bolder mb-3">Create New Password</h1>
										<p>Please enter a new password. Your new password must be different from previous password.</p>
									</div>
									<div class="row g-3 mb-9 d-flex justify-content-center align-items-center text-center">
										<div class="col-md-6">
                                            <input type="hidden" name="userCode" id="userCode" value="{{ $user->USCode }}">
                                            <input type="hidden" name="token" id="token" value="{{ $token }}">
											<div class="form-floating mb-7 d-none">
												<input readonly value="{{ $user->USEmail }}" name="email" type="text" class="form-control" id="email" placeholder="Enter email."/>
												<label for="email">Email </label>
											</div>

											<div class="form-floating mb-7">
												<input name="password" type="password" class="form-control" id="password" placeholder="Enter new password."/>
												<label for="password">New Password</label>
											</div>

											<div class="form-floating mb-7">
												<input name="confirmPassword" type="password" class="form-control" id="confirmPassword" placeholder="Re-enter new password."/>
												<label for="confirmPassword">Confirm Password</label>
											</div>
                                            <div class="row">
                                                <div class="col-md-7">
                                                    <div class="form-floating mb-7">
                                                        <input name="otpCode" type="text" class="form-control" id="otpCode" placeholder="Enter code OTP."/>
                                                        <label for="confirmPassword">Code OTP</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <a class="btn btn-info btn-sm" onclick="requestOTPReset()">Request OTP</a>
                                                </div>
                                            </div>
											<div class="form-floating mb-7">
												<button class="btn btn-dcms text-white" type="submit">Reset Password</button>
											</div>
										
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		
        
		@include('layouts.preloader')

	</body>
    

    @include('layouts._partials.scripts')

    <script>  


        function requestOTPReset() {

            userCode = $('#userCode').val();
                    
            formData = new FormData();

            formData.append('userCode', userCode);

            toggleLoader();

            $.ajax({
                url: '{{ route("resetPassword.requestOTPReset") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: false,
                data: formData,
                processData: false,
                cache: false,
                success: function(resp) {
                    console.log('respo',resp);

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
                            toggleLoader();
                            
                            swal.fire({
                                title: "Success",
                                text: resp.message,
                                icon: "success"
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

                        swal.fire("Amaran", message, "warning");
                    }
                    else
                    {
                        var errors = '<p  id="fontSize" style="margin-top:2%; margin-bottom:1%; font-size: 25px;"><i>Maklumat Ralat</i></p>';
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
                        swal.fire("Amaran", errors, "warning",{html:true});
                        $('html, body').animate({
                            scrollTop: ($(".has-error").first().offset().top) - 200
                        }, 500);
                    }
                }
            });
        }
    
    </script>

</html>
