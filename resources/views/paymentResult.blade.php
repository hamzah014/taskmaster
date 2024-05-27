<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
		<meta name="description" content="Materialize is a Material Design Admin Template,It's modern, responsive and based on Material Design by Google.">
		<meta name="keywords" content="materialize, admin template, dashboard template, flat admin template, responsive admin template, eCommerce dashboard, analytic dashboard">
		<meta name="author" content="ThemeSelect">
		<title>Payment Result</title>
		<link rel="apple-touch-icon" href="{{'asset(assets/images/favicon/apple-touch-icon-152x152.png)'}}">
		<link rel="shortcut icon" type="image/x-icon" href="{{'asset(assets/images/favicon/favicon-32x32.png)'}}">
		<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/vendors.min.css')}}">
		<link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/vertical-menu-nav-dark-template/materialize.css')}}">
		<link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/vertical-menu-nav-dark-template/style.css')}}">
		<link rel="stylesheet" type="text/css" href="{{asset('assets/css/custom/custom.css')}}">
	
	
		@if( $data['redirectURL']  != '')
		<meta http-equiv="Refresh" content="0; url='{{ $data['redirectURL'] ?? '' }}'" />	
		@endif
	</head>
	
	<body class="vertical-layout page-header-light vertical-menu-collapsible vertical-menu-nav-dark preload-transitions 1-column login-bg   blank-page blank-page" data-open="click" data-menu="vertical-menu-nav-dark" data-col="1-column">
		@if( $data['redirectURL']  != '')
			Redirect...
		@else
		<div class="row">
			<div class="col s12">
				<div class="container">
					<div class="row">
						<div class="col s12 z-depth-4 card-panel border-radius-6 login-card bg-opacity-8">
						   <div class="row">
							<div class="col s12">
								<div class="row margin">
									<br><br>
									@if($data['paymentStatusCode'] == '01')
									<center><img  style='height: 40%; width: 40%;'  src="{{ asset('assets/images/payment_success.png') }}" ></center>
									<center><div style="color:black; font-size:28px; font-family: Helvetica, Arial, sans-serif;">Payment<br>Completed</div></center><br>
									<center><div style="color:black; font-size:14px; font-family: Helvetica, Arial, sans-serif;">Thank you</div></center><br>
									@elseif($data['paymentStatusCode'] == '02')
									<center><img  style='height: 40%; width: 40%;'  src="{{ asset('assets/images/payment_fail.png') }}" ></center>
									<center><div style="color:black; font-size:28px; font-family: Helvetica, Arial, sans-serif;">Payment<br>Failed</div></center>
									<center><div style="color:black; font-size:14px; font-family: Helvetica, Arial, sans-serif;">Please retry again</div></center><br>
									@elseif($data['paymentStatusCode'] == '03')
									<center><img  style='height: 40%; width: 40%;'  src="{{ asset('assets/images/payment_fail.png') }}" ></center>
									<center><div style="color:black; font-size:28px; font-family: Helvetica, Arial, sans-serif;">Payment<br>Cancelled</div></center>
									<center><div style="color:black; font-size:14px; font-family: Helvetica, Arial, sans-serif;">Please retry again</div></center><br>
									@endif
									@if($data['redirectURL']!= '')
									<center><a class="btn gradient-45deg-purple-light-blue" target="_blank" href="{{$data['redirectURL']}}">Click to Continue</a></center><br>
									@endif
								</div>
							</div>
						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		@endif
	</body>
</html>