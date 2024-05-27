<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <title>Login to SPEED</title>
    <link rel="apple-touch-icon" href="{{asset('assets/images/logo/logo.png')}}">
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('assets/images/logo/logo.png')}}">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/vendors.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/speed-template/materialize.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/speed-template/style.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/pages/login.css')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- BEGIN: VENDOR CSS-->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/vendors.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/animate-css/animate.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/flag-icon/css/flag-icon.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/chartist-js/chartist.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/chartist-js/chartist-plugin-tooltip.css')}}">
    <!-- Sweetalert -->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/sweetalert/sweetalert.css')}}">
    <!-- End Sweetalert -->
    <!-- FileUploads -->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/dropify/css/dropify.min.css')}}">
    <!-- End FileUploads -->
    <!-- Select2 -->
    <link rel="stylesheet" href="{{asset('assets/vendors/select2/select2.min.css')}}" type="text/css">
    <link rel="stylesheet" href="{{asset('assets/vendors/select2/select2-materialize.css')}}" type="text/css">
    <!-- End Select2 -->
    <!-- Datatable -->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/data-tables/css/jquery.dataTables.min.css')}}">
    <link rel="stylesheet" type="text/css"
        href="{{asset('assets/vendors/data-tables/Responsive-2.3.0/css/responsive.dataTables.min.css')}}">
    <link rel="stylesheet" type="text/css"
        href="{{asset('assets/vendors/data-tables/Select-1.4.0/css/select.dataTables.min.css')}}">
    <link rel="stylesheet" type="text/css"
        href="{{asset('assets/vendors/data-tables/Buttons-2.2.3/css/buttons.dataTables.min.css')}}">
    <!-- End Datatable -->
    <!-- END: VENDOR CSS-->
    <!-- BEGIN: Page Level CSS-->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/speed-template/materialize.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/themes/speed-template/style.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/pages/dashboard-modern.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/pages/form-select2.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/pages/data-tables.css')}}">
    <!-- END: Page Level CSS-->
    <!-- BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/custom/custom.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/css/custom/style.css')}}">
    <!-- BEGIN VENDOR JS-->
    <script src="{{asset('assets/js/vendors.min.js')}}"></script>
    <!-- BEGIN VENDOR JS-->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{asset('assets/js/scripts/ui-alerts.js')}}"></script>
    <!-- BEGIN PAGE VENDOR JS-->
    <script src="{{asset('assets/vendors/jquery-validation/jquery.validate.min.js')}}"></script>
    <script src="{{asset('assets/vendors/chartjs/chart.min.js')}}"></script>
    <script src="{{asset('assets/vendors/chartist-js/chartist.min.js')}}"></script>
    <script src="{{asset('assets/vendors/data-tables/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('assets/vendors/data-tables/extensions/responsive/js/dataTables.responsive.min.js')}}">
    </script>
    <script src="{{asset('assets/vendors/data-tables/js/dataTables.select.min.js')}}"></script>
    <script src="{{asset('assets/vendors/select2/select2.full.min.js')}}"></script>
    <script src="{{asset('assets/vendors/dropify/js/dropify.min.js')}}"></script>
    <script src="{{asset('assets/vendors/formatter/jquery.formatter.min.js')}}"></script>
    <script src="{{asset('assets/vendors/chartist-js/chartist-plugin-tooltip.js')}}"></script>
    <script src="{{asset('assets/vendors/chartist-js/chartist-plugin-fill-donut.min.js')}}"></script>
    <!-- END PAGE VENDOR JS-->
    <!-- BEGIN THEME  JS-->
    <script src="{{asset('assets/js/plugins.js')}}"></script>
    <script src="{{asset('assets/js/search.js')}}"></script>
    <!-- END THEME  JS-->
    <!-- BEGIN PAGE LEVEL JS-->
    <script src="{{asset('assets/js/scripts/form-validation.js')}}"></script>
    <script src="{{asset('assets/js/scripts/data-tables.js')}}"></script>
    <script src="{{asset('assets/js/scripts/advance-ui-modals.js')}}"></script>
    <!-- END PAGE LEVEL JS-->
    <script src="{{asset('js/custom.js')}}" type="text/javascript"></script>
    <script src="{{asset('js/ajaxSubmit.js')}}" type="text/javascript"></script>
    <style>
    body {
        font-size: 13px;
    }

    /* Style the tab */
    .tab {
        display: inline-flex;
    }

    /* Style the tab content */
    .tabcontent {
        background-color: white;
    }

    #login {
        max-height: 330px;
        padding: 20px;
    }

    #info1 {
        height: 600px;
        margin-left: 10px;
        padding: 5px;
    }

    .page-bg {
        background-image: url('../../images/gallery/bg.jpg');
        background-repeat: no-repeat;
        background-size: 100%;
    }

    #page-main {
        padding-left: 20px;
        padding-right: 20px;
        display: -webkit-box;
        display: -webkit-flex;
        display: -ms-flexbox;
        display: flex;
    }

    #page-main .card-panel.border-radius-6.login-card {
        margin-left: 0 !important;
    }

    .containerImg {
        display: flex;
        align-items: center;
    }

    #Logo {
        display: inline-block;
        margin-left: 25px;
        margin-top: 25px;
        width: 15%;
    }

    #maintitle {
        display: inline-block;
        vertical-align: top;
        width: 75%;
        text-align: center;
    }
    </style>
</head>

<body
    class="vertical-layout page-header-light vertical-menu-collapsible vertical-menu-nav-dark preload-transitions 1-column login-bg   blank-page blank-page"
    data-open="click" data-menu="vertical-menu-nav-dark" data-col="1-column">
    <div class="row">
        <div class="col s12 pt-1">
            {{ csrf_field() }}
            <div class="container ">
                <div class="containerImg">
                    <div class="Logo" style="margin-left: 10px; text-align:right">
                        <img src="{{asset('assets/images/logo/logo.png')}}" alt="" style="width:80px; height:80px; ">
                    </div>
                    <div class="maintitle">
                        <h1 style="color:#ddd;">&nbsp;&nbsp;&nbsp;Selamat Datang Ke Sistem Perolehan DBKL</h1>
                    </div>
                </div>
                <div id="page-main" class="row">
                  <div class="col l6 m6 s6 text-center offset-s6">
                    <a class="btn btn-primary" href="#" style="">
                      DOWNLOAD NOW
                    </a>
                  </div>
                </div>
            </div>
            <div class="content-overlay"></div>
        </div>
    </div>

    <script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    </script>
</body>

</html>