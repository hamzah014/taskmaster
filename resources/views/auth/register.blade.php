
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Collab Model</title>
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
						<div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 full-opacity">
							<form class="ajax-form w-100" id="login-form" action="{{ route('register.create') }}" method="POST">
								@csrf
								<div class="text-center mb-1">
									<img src="{{ asset('assets/images/logo/logo.png') }}" alt="logo-dcms" class="w-50">
								</div>
								<div class="fv-row mb-3">
									<label for="email" class="form-label">Name</label>
									<div class="input-group mb-5">
										<span class="input-group-text bg-white border-none border-end-white" id="basic-addon3">
										<i class="text-dark ki-duotone ki-badge fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
										</span>
										<input type="text" class="form-control border-start-white" name="name" id="name"
										  placeholder="Enter your name" aria-describedby="basic-addon3"/>
									</div>
								</div>
								<div class="fv-row mb-3">
									<label for="email" class="form-label">Email</label>
									<div class="input-group mb-5">
										<span class="input-group-text bg-white border-none border-end-white" id="basic-addon3">
										<i class="text-dark ki-duotone ki-badge fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
										</span>
										<input type="text" class="form-control border-start-white" name="email" id="email"
										  placeholder="Enter email" aria-describedby="basic-addon3"/>
									</div>
								</div>
								<div class="fv-row mb-3">
									<label for="password" class="form-label">Password</label>
									<div class="input-group mb-5">
										<span class="input-group-text bg-white border-none border-end-white" id="basic-addon3">
										<i class="text-dark ki-duotone ki-lock fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
										</span>
										<input type="password" class="form-control border-start-white" name="password"
										id="password"  placeholder="Enter password" aria-describedby="basic-addon3"/>
									</div>
								</div>
								<div class="fv-row mb-8">
									<label for="password" class="form-label">Re-enter Password</label>
									<div class="input-group mb-5">
										<span class="input-group-text bg-white border-none border-end-white" id="basic-addon3">
										<i class="text-dark ki-duotone ki-lock fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
										</span>
										<input type="password" class="form-control border-start-white" name="password_confirmation"
										id="password_confirmation"  placeholder="Re-enter password" aria-describedby="basic-addon3"/>
									</div>
								</div>
								<div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-10">
									<div></div>
									<label class="text-light">
                                        Already register? <a href="{{ route('login.index') }}" class="link-primary text-light text-underline">Login Here</a>
                                    </label>
								</div>
								<div class="fv-row mb-3 d-flex flex-center">
									<button type="submit" class="btn btn-primary text-light w-50" >Register</button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" tabindex="-1" id="modal-forgot">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header p-2">
						<h3 class="modal-title ps-5">Reset your Password</h3>
						<div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
							<i class="fas fa-close fs-1"></i>
						</div>
					</div>

					<form class="ajax-form w-100" id="forgot-form" action="{{ route('forgotPassword.sendLink') }}" method="POST">
						@csrf
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<label>Enter the email address associated with your account and we'll send you a link to reset your password.</label>
								</div>
							</div>
							<div class="row mt-5">
								<div class="col-md-12">
									<input type="text" id="email" name="email" class="form-control" placeholder="Email"/>
								</div>
							</div>
							<div class="row mt-5">
								<div class="col-md-12 text-center">
									<button class="btn btn-dcms" type="submit">Continue</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>

    	@include('layouts.preloader')

		@include('layouts._partials.scripts')

		<script>

			var userCode;

		</script>


	</body>
</html>
