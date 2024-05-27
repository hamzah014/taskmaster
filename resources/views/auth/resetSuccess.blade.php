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
				
				<div class="d-flex align-items-center flex-column-fluid flex-lg-row-auto justify-content-center p-10">
					<div class="d-flex flex-column align-items-stretch flex-center rounded-4 w-50 h-50 mt-15 card p-5 bg-forgot-card">
						<div class="d-flex justify-content-center align-items-center p-10">
							<div class="form w-100">

                                <div class="text-center mb-11 mt-15">
                                    <h1 class="text-dark fw-bolder mb-3">Your successfully changed your password</h1>
                                </div>
                                <div class="text-center mb-11 mt-15">
                                    <img src="{{ asset('assets/images/icon/image/reset-success.png') }}" alt="" class="w-50">
                                </div>
                                <div class="row g-3 mb-9 d-flex justify-content-center align-items-center text-center">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-7">
                                            <a class="btn btn-dcms text-white" href="{{ route('login.index') }}">Continue Login</a>
                                        </div>
                                    </div>
                                </div>
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
    
    </script>

</html>
