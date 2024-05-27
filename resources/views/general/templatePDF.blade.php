<!DOCTYPE html>
<html lang="en">
    <style>
        <?php if ($templateName == "AKUJANJI SYARIKAT"): ?>
        body {
            background: #F08080 !important;
        }

        html, body, div {
            margin: 0;
            padding: 0;
        }
        <?php elseif ($templateName == "AKUAN PENGESAHAN"): ?>
        body {
            background: yellow !important;
        }

        html, body, div {
            margin: 0;
            padding: 0;
        }
        <?php elseif ($templateName == "AKUAN PEMBIDA BERJAYA"): ?>
        body {
            background: #3498DB !important;
        }

        html, body, div {
            margin: 0;
            padding: 0;
        }
        <?php endif; ?>
    </style>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @if($template == 'SIJIL')
    <title>SIJIL</title>

    @elseif($template == "RESIT")
    <title>RESIT</title>

    @elseif($template == "PROPOSAL")
    <title>PROPOSAL</title>

    @elseif($template == "LETTER")

        @if($templateName=="INTENT")

        <title>SURAT NIAT</title>

        @elseif($templateName == "ACCEPTANCE")

        <title>SURAT SETUJU TERIMA</title>

        @elseif($templateName == "SAK")

        <title>SURAT ARAHAN KERJA</title>

        @elseif($templateName == "AKUAN PEMBIDA BERJAYA")

        <title>SURAT AKUAN PEMBIDA BERJAYA</title>

        @elseif($templateName == "AKUJANJI SYARIKAT")

        <title>SURAT AKUJANJI SYARIKAT</title>


        @endif

    @endif

    <!-- BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="{{public_path('assets/css/custom/bootstrap.css')}}">
    <link rel="stylesheet" type="text/css" href="{{public_path('assets/css/custom/bootstrap-extended.css')}}">

    <script src="{{public_path('js/vendor/bootstrap.js')}}" type="text/javascript"></script>
    <script src="{{public_path('js/vendor/bootstrap.min.js')}}" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="{{public_path('assets/vendors/vendors.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{public_path('assets/css/themes/vertical-menu-nav-dark-template/materialize.css')}}">
    <link rel="stylesheet" type="text/css" href="{{public_path('assets/css/themes/vertical-menu-nav-dark-template/style.css')}}">
    <link rel="stylesheet" type="text/css" href="{{public_path('assets/css/custom/custom.css')}}">

    <!-- BEGIN VENDOR JS-->
    	<script src="{{public_path('assets/js/vendors.min.js')}}"></script>

    <style>

        html {
            -webkit-print-color-adjust: exact;
        }

        body {
            font-family: 'Pacifico', cursive !important!important!important;
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
            font-size:12px;
            color:black;
        }

        *{
            font-family: 'Pacifico', cursive !important!important!important;
        }

        h1 {
            text-align: left;
        }

        .text-bold{
            font-weight:bold;
        }
        .text-right{
            text-align:right;
        }
        .text-left{
            text-align:left;
        }
        .text-money{
            text-align:right;
            padding:2px;
        }

        .head1{
            font-size:25px;
            font-style:bold;
            color:black;
        }

        .head2{
            font-size:20px;
            font-style:bold;
            color:black;
        }

        label,p,table,td{
            color:black;
        }
        .page-break { page-break-before: always; }

        th {
            color:black;
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid black;
        }
        td .table-item{
            padding: 8px;
            border-bottom: 1px solid white;
            border-top: 1px solid black !important;
        }
    </style>
</head>
<body style="background:white">


    @if($template == "SIJIL")

        @include('publicUser.dashboard.sijilPDF')

    @elseif($template == "RESIT")

        @if($templateName == "REGISTER")

            @include('publicUser.auth.resitPDF')

        @elseif($templateName == "TENDERAPP")

            @include('publicUser.application.resitPDF')

        @elseif($templateName == "CERTAPP")

            @include('publicUser.transaksi.resitPDF')

        @endif

    @elseif($template == "PROPOSAL")

        @include('publicUser.proposal.proposalPDF')

    @elseif($template == "LETTER")

        @if($templateName=="INTENT")
            @include('perolehan.letter.intentLetter.letterIntentPDF')

        @elseif($templateName == "ACCEPTANCE")
            @include('perolehan.letter.acceptLetter.letterAcceptPDF')

        @elseif($templateName == "AKUAN PENGESAHAN")
            @include('perolehan.letter.acceptLetter.letterAkuanPengesahanPDF')

        @elseif($templateName == "AKUAN PEMBIDA BERJAYA")
            @include('perolehan.letter.acceptLetter.letterAkuanPembidaBerjayaPDF')

        @elseif($templateName == "AKUJANJI SYARIKAT")
            @include('perolehan.letter.acceptLetter.letterAkuJanjiSyarikatPDF')

        @endif


    @elseif($template == "DOKUMEN")

        @if($templateName=="SPF")
            @include('publicUser.tender.templateSPF')

        @elseif($templateName=="BQF")
            @include('publicUser.tender.templateBQF')

        @elseif($templateName=="CF")
            @include('publicUser.tender.templateCF')
        
        @endif

    @elseif($template == "SAK")

        @if($templateName == "SAK")
            @include('pelaksana.project.letterSakPDF')
        @endif

    @elseif($template == "PROJECT")

        @if($templateName == "CLOSE")
            @include('pelaksana.project.closeProjectDF')

        @elseif($templateName == "CLOSE-RPT")
            @include('pelaksana.project.closeProjectReport')
        @endif

    @endif




    <!-- BEGIN VENDOR JS-->
    <script src="{{public_path('assets/js/vendors.min.js')}}"></script>
    <!-- END PAGE LEVEL JS-->
    <script src="{{public_path('js/custom.js')}}" type="text/javascript"></script>
</body>
</html>
