
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>TaskMaster</title>
		@include('layouts._partials.head')

		<style>
			.login-card {
				position: relative;
				background-repeat: no-repeat;
				background-size: cover;
				background: linear-gradient(345.08deg, rgba(255, 81, 47, 0.6) 10.64%, rgba(221, 36, 118, 0.6) 79.94%) !important;
				box-shadow: 0px 4px 120px 0px #91BA831A;

			}

			.login-card::before {
				content: '';
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				opacity: 0.8;
			}

			.login-card .full-opacity {
				position: relative;
				z-index: 1; /* Ensure content stays above the overlay */
			}
		</style>

	</head>

	<body id="kt_body" class="app-blank" style="background-image: url('{{ asset('assets/images/background/background-orange.png') }}');
  background-repeat: no-repeat;
  background-size: cover;">

		<div class="d-flex flex-column flex-root" id="kt_app_root">

			<div class="d-flex flex-column flex-column-fluid flex-lg-row">
				<div class="d-flex flex-column-fluid align-items-center justify-content-center p-10">
					<div class="h-85 bg-body d-flex flex-column align-items-stretch align-items-center rounded-4 w-md-600px p-20 bg-dark">
						<div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20 full-opacity">
							<form class="ajax-form w-100" id="login-form" action="{{ route('admin.login.validate') }}" method="POST">
								@csrf
								<div class="text-center mb-11">
									<img src="{{ asset('assets/images/logo/logo.png') }}" alt="logo-dcms" class="w-50">
                                    <h1 class="text-light">ADMIN LOG-IN</h1>
								</div>
								<div class="fv-row mb-3">
									<label for="email" class="form-label">Email</label>
									<div class="input-group mb-5">
										<span class="input-group-text bg-white border-none border-end-white" id="basic-addon3">
										<i class="text-dark ki-duotone ki-badge fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
										</span>
										<input type="text" class="form-control border-start-white" name="email" id="email" value="{{ Cache::get('login.email') ?: old('email') }}"
										  placeholder="Enter email" aria-describedby="basic-addon3"/>
									</div>
								</div>
								<div class="fv-row mb-3">
									<label for="password" class="form-label">Password</label>
									<div class="input-group mb-5">
										<span class="input-group-text bg-white border-none border-end-white" id="basic-addon3">
										<i class="text-dark ki-duotone ki-lock fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
										</span>
										<input type="password" class="form-control border-start-white" name="password"  value="{{ Cache::get('login.password') ?: old('password') }}"
										id="password"  placeholder="Enter password" aria-describedby="basic-addon3"/>
									</div>
								</div>
								<div class="d-grid mb-3 d-flex justify-content-center align-items-center">
									<button type="submit" id="kt_sign_in_submit" class="btn btn-primary text-light w-50">
										<span class="indicator-label">Login</span>
										<span class="indicator-progress">Please wait...
										<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
									</button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>

    	@include('layouts.preloader')

		@include('layouts._partials.scripts')

		<script>

		</script>


	</body>
</html>
