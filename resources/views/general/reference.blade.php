<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="Materialize is a Material Design Admin Template,It's modern, responsive and based on Material Design by Google.">
    <meta name="keywords" content="materialize, admin template, dashboard template, flat admin template, responsive admin template, eCommerce dashboard, analytic dashboard">
    <meta name="author" content="ThemeSelect">
    <title>Maklumat Pembayaran</title>
    <link rel="apple-touch-icon" href="{{asset('assets/images/logo/logo.png')}}">
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('assets/images/logo/logo.png')}}">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/vendors.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/speed-template/materialize.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/speed-template/style.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/sweetalert/sweetalert.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/pages/login.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/custom/custom.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('asset/css/pages/register.css')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>

        /* Style the tab */
        .tab {
            overflow: hidden;
            border: 1px solid #ccc;
            background-color: #f1f1f1;
        }

        /* Style the buttons inside the tab */
        .tab button {
            background-color: inherit;
            float: left;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 14px 16px;
            transition: 0.3s;
            font-size: 17px;
        }

        /* Change background color of buttons on hover */
        .tab button:hover {
            background-color: #ddd;
        }

        /* Create an active/current tablink class */
        .tab button.active {
            background-color: #ccc;
        }

        /* Style the tab content */
        .tabcontent {
            display: none;
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-top: none;
        }

        .checkbox-container {
            display: block;
            position: relative;
            padding-left: 35px;
            margin-bottom: 12px;
            cursor: pointer;
            font-size: 16px;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        /* Hide the browser's default checkbox */
        .checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        /* Create a custom checkbox */
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 25px;
            width: 25px;
        }

        /* Create the checkmark/indicator (hidden when not checked) */
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        /* Show the checkmark when checked */
        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }

        /* Style the checkmark/indicator */
        .checkbox-container .checkmark:after {
            left: 9px;
            top: 5px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 3px 3px 0;
            -webkit-transform: rotate(45deg);
            -ms-transform: rotate(45deg);
            transform: rotate(45deg);
        }
    </style>
</head>

<body class="vertical-layout vertical-menu-collapsible page-header-dark vertical-modern-menu preload-transitions 1-column login-bg   blank-page blank-page" data-open="click" data-menu="vertical-modern-menu" data-col="1-column">
<div class="row">
    <div class="col s11">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col s12" style="margin-left: 5% !important;">
                    <div class="card border-radius-12 login-card bg-opacity-8" style="">
                        <div class="card-content">
                            <h4 class="card-title">{{ __('Status Bayaran') }}</h4>
                            <div>
                                <div class="card border-radius-6 bg-opacity-8" >
                                    <div class="card-content">
                                        <h4 class="card-title">Maklumat Pembayaran bagi {{ $contractor->CORegisterRefNo }} : 
                                            @if ($contractor->COStatus == 'PAID') BAYAR @elseif ($contractor->COStatus == 'APPROVE') DITERIMA @else TIADA @endif</h4>
                                        <div class="row">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-overlay"></div>
    </div>
</div>
<script src="{{asset('assets/js/vendors.min.js')}}"></script>
<script src="{{asset('assets/js/plugins.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{asset('js/ajaxSubmit.js')}}" type="text/javascript"></script>
{{--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>--}}
<script>

</script>

</body>

</html>


