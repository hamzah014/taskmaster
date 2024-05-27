@extends('layouts.appKewangan')

@push('css')
    <style>
    </style>
@endpush
@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Scheduler</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('kewangan.index') }}" class="text-muted text-hover-primary">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Scheduler</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body py-4">
                            <div class="pb-2 pb-lg-5">
                                <h4 class="fw-bold text-gray-900">Dokumen</h4>
                            </div>
                            <div class="fv-row mb-10">
                                <label class="form-label">{{ __('No. SSM Baru') }}</label>
                                {!! Form::text('ssmNo', null , [
                                    'id' => 'ssmNo',
                                    'class' => 'form-control',
                                    'autocomplete' => 'off',
                                    'placeholder' => '199301019087'
                                ]) !!}
                            </div>
                            <div class="fv-row mb-10">
                                <button onclick="startModel()" class="btn btn-light-primary btn-sm col-12">{{ __('Verify Register')}}</button>
                            </div>
                            <div class="fv-row mb-10">
                                <div class="col s12 center-align" id="resultView">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body py-4">
                            <div class="pb-2 pb-lg-5">
                                <h4 class="fw-bold text-gray-900">Tender</h4>
                            </div>
                            <div class="fv-row mb-10">
                                <label class="form-label">{{ __('No. Tender') }}</label>
                                {!! Form::text('tdNo', null , [
                                    'id' => 'tdNo',
                                    'class' => 'form-control',
                                    'autocomplete' => 'off',
                                    'placeholder' => 'TD00000001'
                                ]) !!}
                            </div>
                            <div class="fv-row mb-10">
                                <button onclick="closeAds()" class="btn btn-light-primary btn-sm col-12">{{ __('Closed Tender')}}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body py-4">
                            <div class="pb-2 pb-lg-5">
                                <h4 class="fw-bold text-gray-900">Milestone</h4>
                            </div>
                            <div class="fv-row mb-10 row">
                                <label class="form-label">{{ __('No. Projek') }}</label>
                                {!! Form::text('projectNo', null , [
                                    'id' => 'projectNo',
                                    'class' => 'form-control',
                                    'autocomplete' => 'off',
                                    'placeholder' => 'PT00000019'
                                ]) !!}
                            </div>

                            <div class="fv-row mb-10 row">
                                <label class="form-label">{{ __('Tarikh') }}</label>
                                {!! Form::date('tarikh', $now , [
                                    'id' => 'tarikh',
                                    'class' => 'form-control datepicker',
                                    'autocomplete' => 'off'
                                ]) !!}
                            </div>
                            <div class="fv-row mb-10">
                                <button onclick="startMilestone()" class="btn btn-light-primary btn-sm col-12">{{ __('Calculate Milestone')}}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body py-4">
                            <div class="pb-2 pb-lg-5">
                                <h4 class="fw-bold text-gray-900">Webcam</h4>
                            </div>
                            <div class="fv-row mb-10 row">
                                <label class="form-label">{{ __('Ref No.') }}</label>
                                {!! Form::text('refNo', null , [
                                    'id' => 'refNo',
                                    'class' => 'form-control',
                                    'autocomplete' => 'off',
                                    'placeholder' => 'PKJME'
                                ]) !!}
                            </div>

                            <div class="fv-row mb-10 row">
                                <label class="form-label">{{ __('Jenis') }}</label>
                                {!! Form::text('refType', null , [
                                    'id' => 'refType',
                                    'class' => 'form-control',
                                    'autocomplete' => 'off',
                                    'placeholder' => 'US-FP'
                                ]) !!}
                            </div>
                            <div class="fv-row mb-10">
                                <a class="btn btn-light-primary btn-sm col-12" data-bs-toggle="modal" data-bs-target="#uploadModal" onclick="sentx()">
                                    <i class="ki-solid ki-folder-up fs-2"></i>
                                </a>
                            </div>
                            <div class="fv-row mb-10">
                                <a href="{{ route('webcam.index') }}" class="btn btn-light-primary btn-sm col-12">
                                    {{ __('Web Cam')}}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('script')
<script>

    function sentx(){
        var refNo = $('#refNo').val();
        var refType = $('#refType').val();


        openUploadModal(refNo,'{{ Auth::user()->USCode }}',refType);
    }

    function startModel(){

        var ssmNo = $('#ssmNo').val();

        var data = {
            ssmNo: ssmNo,
            run: 0,
        }

        $.ajax({
            url: '{{ route("scheduler.startModel") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify(data),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if(response == 0){
                    content += "No Contractor Result Found";
                }else{
                    content += "<ul>";
                    response.forEach(function(value, index) {
                            content += '<li>' + value.CONo + " - " + value.COStatus  + '</li>';
                    });
                }

                $('#resultView').html(content);

            },
            error: function(error) {
                if (error.status === 500) {
                    // Handle 500 error
                    console.log('Internal Server Error (500) (ADA DD)');
                } else {
                    // Handle other errors (e.g., 404 Not Found, 401 Unauthorized, etc.)
                    console.log('Unknown error occurred:', error.status);
                }
            }
        });
    }

    function startSSM(){

        {{--$.ajax({--}}
        {{--    url: '{{ route("scheduler.startSSM") }}',--}}
        {{--    type: 'POST',--}}
        {{--    headers: {--}}
        {{--        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')--}}
        {{--    },--}}
        {{--    contentType: 'application/json',--}}
        {{--    dataType: 'json',--}}
        {{--    success: function(response) {--}}
        {{--    },--}}
        {{--    error: function(error) {--}}
        {{--        if (error.status === 500) {--}}
        {{--            // Handle 500 error--}}
        {{--            console.log('Internal Server Error (500) (ADA DD)');--}}
        {{--        } else {--}}
        {{--            // Handle other errors (e.g., 404 Not Found, 401 Unauthorized, etc.)--}}
        {{--            console.log('Unknown error occurred:', error.status);--}}
        {{--        }--}}
        {{--    }--}}
        {{--});--}}
    }

    function startMilestone(){

        // Get the value of 'tarikh' from the input field
        var tarikhValue = $('#tarikh').val();
        var projectNoValue = $('#projectNo').val();

        // Create an object to send as data in the AJAX request
        var requestData = {
            tarikh: tarikhValue,
            projectNo: projectNoValue
        };

        $.ajax({
            url: '{{ route("scheduler.startMilestone") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify(requestData), // Send 'tarikh' as data
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
            },
            error: function(error) {
                if (error.status === 500) {
                    // Handle 500 error
                    console.log('Internal Server Error (500) (ADA DD)');
                } else {
                    // Handle other errors (e.g., 404 Not Found, 401 Unauthorized, etc.)
                    console.log('Unknown error occurred:', error.status);
                }
            }
        });
    }

    function closeAds(){

        var tdNo = $('#tdNo').val();

        var data = {
            tdNo: tdNo,
        }

        $.ajax({
            url: '{{ route("scheduler.closeAds") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify(data),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if(response == 0){
                    content += "No Contractor Result Found";
                }else{
                    content += "<ul>";
                    response.forEach(function(value, index) {
                        content += '<li>' + value.CONo + " - " + value.COStatus  + '</li>';
                    });
                }

                $('#resultView').html(content);

            },
            error: function(error) {
                if (error.status === 500) {
                    // Handle 500 error
                    console.log('Internal Server Error (500) (ADA DD)');
                } else {
                    // Handle other errors (e.g., 404 Not Found, 401 Unauthorized, etc.)
                    console.log('Unknown error occurred:', error.status);
                }
            }
        });
    }

</script>
@endpush


