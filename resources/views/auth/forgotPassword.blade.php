<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="Materialize is a Material Design Admin Template,It's modern, responsive and based on Material Design by Google.">
    <meta name="keywords" content="materialize, admin template, dashboard template, flat admin template, responsive admin template, eCommerce dashboard, analytic dashboard">
    <meta name="author" content="ThemeSelect">
    <title>EMOFA</title>
    <link rel="apple-touch-icon" href="{{'asset(assets/images/favicon/apple-touch-icon-152x152.png)'}}">
    <link rel="shortcut icon" type="image/x-icon" href="{{'asset(assets/images/favicon/favicon-32x32.png)'}}">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/vendors.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/vertical-menu-nav-dark-template/materialize.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/vertical-menu-nav-dark-template/style.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/pages/login.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/custom/custom.css')}}">

</head>

<body class="vertical-layout page-header-light vertical-menu-collapsible vertical-menu-nav-dark preload-transitions 1-column login-bg   blank-page blank-page" data-open="click" data-menu="vertical-menu-nav-dark" data-col="1-column">
<div class="container ">
    <div class="row justify-content-center">
        <div class="col s8" style="margin-left: 15% !important;">
            <div class="card bg-opacity-8" style="margin: 10rem 0 10rem 0 !important;">
                <div class="card-content">
                    <h4 class="card-title">{{ __('Reset Password') }}</h4>
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('forgotPassword.email') }}">
                        @csrf

                        <div class="row"><div class="input-field col m6 s6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label>

                                @if($message != null)
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            <div class="col s12 m12 l12" style="text-align:right;">
                                <button type="submit" class="btn waves-effect waves-light border-round gradient-45deg-brown-brown">
                                    {{ __('Send Password Reset Link') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="content-overlay"></div>
    </div>
</div>

<script src="{{asset('assets/js/vendors.min.js')}}"></script>
<script src="{{asset('assets/js/plugins.js')}}"></script>

</body>

</html>

