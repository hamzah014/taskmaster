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
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/data-tables/Responsive-2.3.0/css/responsive.dataTables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/data-tables/Select-1.4.0/css/select.dataTables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('assets/vendors/data-tables/Buttons-2.2.3/css/buttons.dataTables.min.css')}}">
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
	<script src="{{asset('assets/vendors/data-tables/extensions/responsive/js/dataTables.responsive.min.js')}}"></script>
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
        body{
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
		#login
		{
		  height: 330px;
			padding: 20px;
		}
		#info
		{
			height: 600px;
			margin-left: 10px;
			padding: 5px;
		}
		.page-bg
		{
			background-image: url('../../images/gallery/bg.jpg');
			background-repeat: no-repeat;
			background-size:  100%;
		}
		#page-main
		{
			padding-left: 20px;
			padding-right: 20px;
			display: -webkit-box;
			display: -webkit-flex;
			display: -ms-flexbox;
			display: flex;
		}
		#page-main .card-panel.border-radius-6.login-card
		{
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
			width:15%;
		}
		#maintitle {
			display: inline-block;
			vertical-align: top;
			width: 75%;
			text-align: center;
		}
    </style>
</head>
<body class="vertical-layout page-header-light vertical-menu-collapsible vertical-menu-nav-dark preload-transitions 1-column login-bg   blank-page blank-page" data-open="click" data-menu="vertical-menu-nav-dark" data-col="1-column">
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
                <div id="login" class="col s3 z-depth-4 card-panel border-radius-6 bg-opacity-8" style="margin-right: 10px">
                        <h3 >{{ __('Daftar Masuk')}}</h3>
                        <div class="tab col m12 p-0 mb-2" >
                                <button class="col m6 pt-3 pb-3 tablogin active" onclick="openLoginTab(event, 'publicUser')">Pengguna Awam</button>
                                <button class="col m6 pt-3 pb-3 tablogin" onclick="openLoginTab(event, 'dbklUser')">Pengguna DBKL</button>
                        </div>
                        <div id="publicUser" class="tabLoginContent" style="display:block">
                            <form class="ajax-form" novalidate action="{{ route('login.index') }}" method="POST">
                                <div class="row ">
                                    <div class="input-field col s12 form-group">
                                        <i class="material-icons prefix">person_outline</i>
                                        {!! Form::text('loginID', '', ['id' => 'loginID', 'class' => 'form-control']) !!}
                                        <label for="loginID">{{ __('Emel')}}</label>
                                    </div>
                                </div>
                                <div class="row ">
                                    <div class="input-field col s12 form-group">
                                        <i class="material-icons prefix">lock</i>
                                        {{ Form::password('password', array('id' => 'password', "class" => "form-control")) }}
                                        <label for="password">{{ __('Kata Laluan')}}</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-field col s12">
                                        <button id="save" class="btn btn-primary waves-effect waves-light border-round col s12">{{ __('Login')}}</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col s6 m6 l6 mb-2">
                                        <p class="margin left-align medium-small"><a href="{{ route('publicUser.register.index') }}">{{ __('Register Account')}}</a></p>
                                    </div>
                                    <div class="col s6 m6 l6">
                                        <p class="margin right-align medium-small"><a href="{{ route('publicUser.forgotPassword.index') }}">{{ __('Forgot Password')}}?</a></p>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div id="dbklUser" class="tabLoginContent" style="display:none">
                            <form class="ajax-form" novalidate action="{{ route('login.validate') }}" method="POST">
                                <div class="row">
                                    <div class="input-field col s12 form-group">
                                        <i class="material-icons prefix">person_outline</i>
                                        {!! Form::text('loginID', '', ['id' => 'loginID', 'class' => 'form-control']) !!}
                                        <label for="loginID">{{ __('User ID')}}</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-field col s12 form-group">
                                        <i class="material-icons prefix">lock</i>
                                        {{ Form::password('password', array('id' => 'password', "class" => "form-control")) }}
                                        <label for="password">{{ __('Password')}}</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-field col s12">
                                        <button id="save" class="btn waves-effect waves-light border-round gradient-45deg-amber-amber col s12">{{ __('Login')}}</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col s12 mb-2">
                                        <p class="margin left-align medium-small">&nbsp;</p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <br/>
                </div>
                <div id="info" class="col s9">
                   <div class="tab" >
                        <button class="tablinks" onclick="openTab(event, 'berita')" id="defaultOpen">Pengumuman</button>
                        <button class="tablinks" onclick="openTab(event, 'iklan')">Iklan Tender/Sebutharga</button>
                        <button class="tablinks" onclick="openTab(event, 'carta')">Carta Tender/Sebutharga</button>
                        <button class="tablinks" onclick="openTab(event, 'undi')">Iklan Kerja Undi</button>
                        <button class="tablinks" onclick="openTab(event, 'keputusan')">Keputusan Kerja Undi</button>
                    </div>
                    <div id="berita" class="tabcontent">
                        <div class="row">
                            <div class="col s12">
                                <table id="berita-table" class="speed">
                                    <thead>
                                        <tr>
                                            <th width="10%">Tarikh</th>
                                            <th width="90%">Tajuk</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <a id="btnBerita" href="#modalBerita" style="display:none;" class="new modal-trigger ">Modal Berita</a>
                    </div>
                    <div id="iklan" class="tabcontent">
                        <div class="row">
							<div class="col s12">
							    <table id="TenderIklan-table" class="speed_mini">
									<thead>
										<tr>
											<th>Jenis</th>
											<th>No.Tender/ Sebutharga</th>
											<th>Tajuk</th>
											<th>Tarikh Iklan</th>
											<th>Tarikh Tutup</th>
											<th>Taklimat/ Lawatan Tapak</th>
											<th>Harga Dokumen</th>
										</tr>
									</thead>
								</table>
							</div>
						</div>
                        <a id="btnIklan" href="#modalIklan" style="display:none;" class="new modal-trigger ">Modal Iklan</a>
                    </div>
                    <div id="carta" class="tabcontent">
                        <div class="row">
                            <div class="col s12">
                                <table id="TenderCarta-table" class="speed_mini">
                                    <thead>
                                        <tr>
                                            <th>Kod</th>
                                            <th>Tajuk</th>
                                            <th>Tarikh</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <a id="btnCarta" href="#modalCarta" style="display:none;" class="new modal-trigger">Modal Carta</a>
                    </div>
                    <<div id="undi" class="tabcontent">
                        <table  id="undi-table" class="speed_mini">
                            <thead>
                            <tr>
                                <th><b>Kod Cabutan</b></th>
                                <th><b>Jenis</b></th>
                                <th><b>Lokasi Cabutan</b></th>
                                <th><b>URL</b></th>
                                <th><b>Tarikh Cabutan</b></th>
                                <th><b>Masa Cabutan</b></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>1/2023</td>
                                <td>Dalam Talian</td>
                                <td>Sesi Cabutan Kerja Undi dibuat secara 'live feed' melalui laman Facebook rasmi DBKL</td>
                                <td>https://www.facebook.com/dbkl2u</td>
                                <td>23/06/2023</td>
                                <td>10:29</td>
                            </tr>
                            <tr>
                                <td>5/2022</td>
                                <td>Dalam Talian</td>
                                <td>Sesi Cabutan Kerja Undi dibuat secara 'live feed' melalui laman Facebook rasmi DBKL</td>
                                <td>https://www.facebook.com/dbkl2u</td>
                                <td>23/06/2023</td>
                                <td>10:29</td>
                            </tr>
                            <tr>
                                <td>4/2022</td>
                                <td>Dalam Talian</td>
                                <td>Sesi Cabutan Kerja Undi dibuat secara 'live feed' melalui laman Facebook rasmi DBKL</td>
                                <td>https://www.facebook.com/dbkl2u</td>
                                <td>23/06/2023</td>
                                <td>10:29</td>
                            </tr>
                            <tr>
                                <td>3/2022</td>
                                <td>Dalam Talian</td>
                                <td>Sesi Cabutan Kerja Undi dibuat secara 'live feed' melalui laman Facebook rasmi DBKL</td>
                                <td>https://www.facebook.com/dbkl2u</td>
                                <td>23/06/2023</td>
                                <td>10:29</td>
                            </tr>
                            <tr>
                                <td>1/2022</td>
                                <td>Dalam Talian</td>
                                <td>Sesi Cabutan Kerja Undi dibuat secara 'live feed' melalui laman Facebook rasmi DBKL</td>
                                <td>https://www.facebook.com/dbkl2u</td>
                                <td>23/06/2023</td>
                                <td>10:29</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="keputusan" class="tabcontent">
                        <table id="keputusan-table" class="speed_mini">
                            <thead>
                            <tr>
                                <th><b>Kod Cabutan</b></th>
                                <th><b>Jenis</b></th>
                                <th><b>Lokasi Cabutan</b></th>
                                <th><b>URL</b></th>
                                <th><b>Tarikh Cabutan</b></th>
                                <th><b>Masa Cabutan</b></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>1/2023</td>
                                <td>Dalam Talian</td>
                                <td>Sesi Cabutan Kerja Undi dibuat secara 'live feed' melalui laman Facebook rasmi DBKL</td>
                                <td>https://www.facebook.com/dbkl2u</td>
                                <td>23/06/2023</td>
                                <td>10:29</td>
                            </tr>
                            <tr>
                                <td>5/2022</td>
                                <td>Dalam Talian</td>
                                <td>Sesi Cabutan Kerja Undi dibuat secara 'live feed' melalui laman Facebook rasmi DBKL</td>
                                <td>https://www.facebook.com/dbkl2u</td>
                                <td>23/06/2023</td>
                                <td>10:29</td>
                            </tr>
                            <tr>
                                <td>4/2022</td>
                                <td>Dalam Talian</td>
                                <td>Sesi Cabutan Kerja Undi dibuat secara 'live feed' melalui laman Facebook rasmi DBKL</td>
                                <td>https://www.facebook.com/dbkl2u</td>
                                <td>23/06/2023</td>
                                <td>10:29</td>
                            </tr>
                            <tr>
                                <td>3/2022</td>
                                <td>Dalam Talian</td>
                                <td>Sesi Cabutan Kerja Undi dibuat secara 'live feed' melalui laman Facebook rasmi DBKL</td>
                                <td>https://www.facebook.com/dbkl2u</td>
                                <td>23/06/2023</td>
                                <td>10:29</td>
                            </tr>
                            <tr>
                                <td>1/2022</td>
                                <td>Dalam Talian</td>
                                <td>Sesi Cabutan Kerja Undi dibuat secara 'live feed' melalui laman Facebook rasmi DBKL</td>
                                <td>https://www.facebook.com/dbkl2u</td>
                                <td>23/06/2023</td>
                                <td>10:29</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-overlay"></div>
    </div>
</div>
<div id="modalBerita" class="modal modal-fixed-footer">
    <div class="modal-header">
        <div class="modal-title small">Berita</div>
    </div>
    <div class="modal-body">
        <span class="header-button">
            <div id="beritaContent">
            </div>
        </span>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-action modal-close new modal-trigger btn btn-light-primary">{{ __('Tutup')}}</a>
    </div>
</div>
<div id="modalIklan" class="modal modal-fixed-footer">
    <div class="modal-header">
        <div class="modal-title small">Iklan Sebut Harga / Tender</div>
    </div>
    <div class="modal-content">
        <span class="header-button">
            <div id="iklanContent">
            </div>
        </span>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-action modal-close new modal-trigger waves-effect waves-light btn btn-light-primary">{{ __('Tutup')}}</a>
    </div>
</div>
<div id="modalCarta" class="modal modal-fixed-footer">
    <div class="modal-header">
        <div class="modal-title small">Carta Sebut Harga / Tender</div>
    </div>
    <div class="modal-body">
        <span class="header-button">
            <div id="cartaContent">
            </div>
        </span>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-action modal-close new modal-trigger waves-effect waves-light btn btn-light-primary">{{ __('Tutup')}}</a>
    </div>
</div>
<script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    function openBeritaModal(id){
        $('.modal').modal();
        var route = "{{ route('publicUser.login.beritaModal', ['id' => '__ID__']) }}";
        route = route.replace('__ID__', id);
        $.ajax({
            url: route,
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                // Load the Blade view content into the modal content
                $('#beritaContent').html(data);
                var targetModalId = $('#btnBerita').attr('href');
                $(targetModalId).modal('open');
            },
            error: function(xhr) {
                console.log(xhr.responseText);
            }
        });
    }
    function openIklanModal(id){
        $('.modal').modal();
        var route = "{{ route('publicUser.login.iklanModal', ['id' => '__ID__']) }}";
        route = route.replace('__ID__', id);
        $.ajax({
            url: route,
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                // Load the Blade view content into the modal content
                $('#iklanContent').html(data);
                var targetModalId = $('#btnIklan').attr('href');
                $(targetModalId).modal('open');
            },
            error: function(xhr) {
                console.log(xhr.responseText);
            }
        });
    }
    function openCartaModal(id){
        $('.modal').modal();
        var route = "{{ route('publicUser.login.cartaModal', ['id' => '__ID__']) }}";
        route = route.replace('__ID__', id);
        $.ajax({
            url: route,
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                // Load the Blade view content into the modal content
                $('#cartaContent').html(data);
                var targetModalId = $('#btnCarta').attr('href');
                $(targetModalId).modal('open');
            },
            error: function(xhr) {
                console.log(xhr.responseText);
            }
        });
    }
    // {{--Working Code Datatable--}}
    (function ($) {
        var selected = [];
        //TABLE FOR BERITA
        var tableBerita = $('#berita-table').DataTable({
			dom : 'fBrtip',
			ordering : false,
			searching : true,
			processing: true,
			serverSide: true,
			autoWidth: false,
			language : {
				search : " ",
				searchPlaceholder : "Carian",
				zeroRecords: "Tiada rekod yang sepadan ditemui",
				info : 'Menunjukkan _START_ ke _END_ dari _TOTAL_ rekod',
				infoEmpty : "Menunjukkan 0 ke 0 dari 0 rekod",
				infoPostFix : "",
				infoFiltered : "",
				processing : 'Muatnaik rekod. Sila tunggu...',
                emptyTable: "Maaf, tiada berita untuk dipaparkan.",
				paginate : {
					first :    "Mula",
					last :     "Habis",
					previous : "Sebelum",
					next :     "Seterusnya"
				},
				lengthMenu :
					'<div class="form-group select2">'+
						'Tunjuk&nbsp;<select class="form-control select2">'+
							'<option value="10">10</option>'+
							'<option value="25">25</option>'+
							'<option value="100">100</option>'+
							'<option value="-1">Semua</option>'+
						'</select>'+
					'&nbsp;rekod&emsp;</div>'
			},
            ajax:  {
                "url" :"{{ route('publicUser.login.beritaDatatable') }}",
                "method": 'POST',
            },
            columns: [
                { name: 'ACDate', data: 'ACDate', class: 'text-center' },
                { name: 'ACTitle', data: 'ACTitle', class: 'text-left'},
            ],
        });
        //TABLE FOR IKLAN TENDER
        var tableTenderIklan = $('#TenderIklan-table').DataTable({
			dom : 'fBrtip',
			language : {
				search : " ",
				searchPlaceholder : "Carian",
				zeroRecords: "Tiada rekod yang sepadan ditemui",
				info : 'Menunjukkan _START_ ke _END_ dari _TOTAL_ rekod',
				infoEmpty : "Menunjukkan 0 ke 0 dari 0 rekod",
				infoPostFix : "",
				infoFiltered : "",
				processing : 'Muatnaik rekod. Sila tunggu...',
                emptyTable: "Maaf, tiada iklan tender untuk dipaparkan.",
				paginate : {
					first :    "Mula",
					last :     "Habis",
					previous : "Sebelum",
					next :     "Seterusnya"
				},
				lengthMenu :
					'<div class="form-group select2">'+
						'Tunjuk&nbsp;<select class="form-control select2">'+
							'<option value="10">10</option>'+
							'<option value="25">25</option>'+
							'<option value="100">100</option>'+
							'<option value="-1">Semua</option>'+
						'</select>'+
					'&nbsp;rekod&emsp;</div>'
			},
            sortable  : true,
			ordering : false,
			searching : true,
			processing: true,
			serverSide: true,
			autoWidth: false,
            ajax:  {
                url : "{{ route('publicUser.login.iklanDatatable') }}",
                method: "POST",
                data: {
                },
                error: function(xhr, error, thrown) {
                    console.log("Error occurred!");
                    console.log(xhr, error, thrown);
                }
            },
            columns: [
                { name: 'TD_TCCode', data: 'TD_TCCode', class: 'text-center' },
                { name: 'TDNo', data: 'TDNo', class: 'text-center'},
                { name: 'TDTitle', data: 'TDTitle', class: 'text-left'},
                { name: 'TDPublishDate', data: 'TDPublishDate', class: 'text-center'},
                { name: 'TDClosingDate', data: 'TDClosingDate', class: 'text-center'},
                { name: 'TDSiteBrief', data: 'TDSiteBrief', class: 'text-center'},
                { name: 'TDDocAmt', data: 'TDDocAmt', class: 'text-center'},
            ],
            order: [[3, 'desc'], [1, 'desc']],
        });
        //TABLE FOR CARTA TENDER
        var tableTenderCarta = $('#TenderCarta-table').DataTable({
			dom : 'fBrtip',
			language : {
				search : " ",
				searchPlaceholder : "Carian",
				zeroRecords: "Tiada rekod yang sepadan ditemui",
				info : 'Menunjukkan _START_ ke _END_ dari _TOTAL_ rekod',
				infoEmpty : "Menunjukkan 0 ke 0 dari 0 rekod",
				infoPostFix : "",
				infoFiltered : "",
				processing : 'Muatnaik rekod. Sila tunggu...',
                emptyTable: "Maaf, tiada carta tender untuk dipaparkan.",
				paginate : {
					first :    "Mula",
					last :     "Habis",
					previous : "Sebelum",
					next :     "Seterusnya"
				},
				lengthMenu :
					'<div class="form-group select2">'+
						'Tunjuk&nbsp;<select class="form-control select2">'+
							'<option value="10">10</option>'+
							'<option value="25">25</option>'+
							'<option value="100">100</option>'+
							'<option value="-1">Semua</option>'+
						'</select>'+
					'&nbsp;rekod&emsp;</div>'
			},
			ordering : false,
			searching : true,
			processing: true,
			serverSide: true,
			autoWidth: false,
            ajax:  {
                "url" :"{{ route('publicUser.login.cartaDatatable') }}",
                "method": 'POST',
            },
            columns: [
                { name: 'TDNo', data: 'TDNo', class: 'text-center'},
                { name: 'TDTitle', data: 'TDTitle', class: 'text-left'},
                { name: 'TDClosingDate', data: 'TDClosingDate', class: 'text-center'},
            ],
            order: [[2, 'desc'], [0, 'desc']],
        });
    })(jQuery);
    (function ($) {
        ajaxSubmitForm('form.ajax-form');
    })(jQuery);
    function ajaxSubmitForm(form, callback) {
        $(form).on("submit", function (e) {
            e.preventDefault();
            urlAction = $(this).attr("action");
            var formData = new FormData(this);
            $(".form-group").removeClass("has-error");
            $(".form-control").removeClass("was-validated invalid is-invalid custom-select.is-invalid valid is-valid custom-select.is-valid");
            $(".form-group").children("span.help-block").remove();
            toggleLoader();
            ajaxFormXHR = $.ajax({
                url: urlAction,
                type: 'POST',
                contentType: false,
                data: formData,
                processData: false,
                cache: false,
                success: function (resp) {
                    location.href = resp.redirect;
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
                        swal.fire("{{ __('Warning')}}", message, "warning");
                    }
                    else
                    {
                        var errors = '<p  id="fontSize" style="margin-top:2%; margin-bottom:1%; font-size: 25px;"><i>{{ __('Invalid Information')}}</i></p>';
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
                            $('[name="' + key + '"]').closest('.form-group').addClass("has-error");
                            $('[name="' + key + '"]').addClass("was-validated is-invalid invalid custom-select.is-invalid");
                            $('#Valid'+key).empty();
                            $('[name="' + key + '"]').closest('.form-group').append("<span id='Valid"+key+"' class=\"help-block\" style='color:red; font-family:Nunito, sans-serif;'>" + message[0] + "</span>");
                        });
                        swal.fire('{{ __('Warning')}}', errors, 'warning',{html:true});
                        $('html, body').animate({
                            scrollTop: ($(".has-error").first().offset().top) - 200
                        }, 500);
                    }
                }
            })
        });
    }
    $(".card-alert .close").click(function () {
        $(this)
            .closest(".card-alert")
            .fadeOut("slow");
    });
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }

    function openLoginTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabLoginContent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tablogin");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    // Get the element with id="defaultOpen" and click on it
    document.getElementById("defaultOpen").click();
</script>
</body>
</html>
