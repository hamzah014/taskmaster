@extends('layouts.app')

@push('css')
    <style>
        .card-register{
            border-radius: 25px;
            background: #FFFFFFAD;
        }
        .step-icon-number{
            border-radius: 100% !important;
        }
        .stepper.stepper-pills .stepper-item.current .stepper-icon {
            background-color: black;
        }
        .stepper.stepper-pills .stepper-item .stepper-icon .stepper-number {
            color: black;
        }
        .border-circle{
            border: 2px solid #1BB1E6;
            border-radius: 100%;
            padding: 10px;
            color:black;
        }
        .register-info{
            border: 2px solid #1BB1E6;
            color:black !important;
            background-color: #ECFEFF;

        }
    </style>
@endpush
@section('content')

    <div id="kt_app_content_container" class="app-container d-flex justify-content-center align-items-center">
        <div class="card mb-5 mb-xl-10 bg-content-card card-no-border" style="width: 100%;">
            <div id="kt_account_settings_profile_details">
                <div class="card-body p-9">

                    <div class="row mb-5">
                        <div class="col-md-12 text-start">
                            <a class="btn btn-secondary btn-sm" href="{{ url()->previous() }}"><i class="fa fa-chevron-left"></i> Back</a>
                        </div>
                    </div>

                    <div class="row flex-row mb-5">
                        <div class="col-md-12">
                            <h2>Project Idea</h2>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header card-header-stretch min-height-none">
                            <div class="card-toolbar m-0">
                                <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a id="info_tab" class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab" role="tab" href="#info_content">
                                            <span class="badge badge-dark me-2">1</span> Project Information
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a id="scoring_tab" class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab" role="tab" href="#scoring_content">
                                            <span class="badge badge-dark me-2">2</span> Requirement Scoring
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div id="info_content" class="card-body p-0 tab-pane fade" role="tabpanel" aria-labelledby="info_content">
                                    <div class="w-100">
                                        <form id="daftarForm" class="ajax-form-register" method="POST" enctype="multipart/form-data">
                                            @csrf

                                            <input type="hidden" name="projectCode" id="projectCode" value="{{ $project->PJCode }}">

                                            <h4 class="">Details Project</h4>
                                            <h5>Project information:</h5>

                                            <div class="fv-row mb-10 mt-5">
                                                <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                    <span class="required">Project Name</span>
                                                    <span class="ms-1" data-bs-toggle="tooltip" title="Specify your unique project name">
                                                        <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                            <span class="path3"></span>
                                                        </i>
                                                    </span>
                                                </label>
                                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter project name" value="{{ $project->PJName }}" readonly>
                                            </div>
                                            <div class="fv-row">
                                                <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                    <span class="required">Description</span>
                                                    <span class="ms-1" data-bs-toggle="tooltip" title="Project description">
                                                        <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                            <span class="path3"></span>
                                                        </i>
                                                    </span>
                                                </label>
                                                <textarea id="description" name="description" class="form-control" data-kt-autosize="true" placeholder="Project description" readonly>{{ $project->PJDesc }}</textarea>
                                            </div>
                                            <div class="fv-row row mt-4">
                                                <div class="col-md-6">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold my-4">
                                                        <span class="required">Start Date</span>
                                                        <span class="ms-1" data-bs-toggle="tooltip" title="Enter project start date">
                                                            <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                            </i>
                                                        </span>
                                                    </label>
                                                    <input type="date" class="form-control" id="startDate" name="startDate" placeholder="Start date" value="{{ $project->PJStartDate }}" readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold my-4">
                                                        <span class="required">End Date</span>
                                                        <span class="ms-1" data-bs-toggle="tooltip" title="Enter project end date">
                                                            <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                            </i>
                                                        </span>
                                                    </label>
                                                    <input type="date" class="form-control" id="endDate" name="endDate" placeholder="End date" value="{{ $project->PJEndDate }}" readonly>
                                                </div>
                                            </div>
                                            <div class="fv-row row mt-4">
                                                <div class="col-md-6">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold my-4">
                                                        <span class="required">Budget</span>
                                                        <span class="ms-1" data-bs-toggle="tooltip" title="Enter budget">
                                                            <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                            </i>
                                                        </span>
                                                    </label>
                                                    <div class="input-group mb-5">
                                                        <span class="input-group-text" id="basic-addon1">RM</span>
                                                        <input type="number" step="0.01" id="budget" name="budget" class="form-control" placeholder="Budget project"
                                                        aria-label="Budget project" aria-describedby="basic-addon1" value="{{ $project->PJBudget }}" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                        </form>
                                    </div>
                                </div>
                                <div id="scoring_content" class="card-body p-0 tab-pane fade show active" role="tabpanel" aria-labelledby="scoring_content">
                                    <div class="w-100">

                                        <form id="scoringForm" class="ajax-form" method="POST" enctype="multipart/form-data">
                                            @csrf

                                            <input type="hidden" name="projectIdeaCode" id="projectIdeaCode" value="{{ $projectIdea->PICode }}">
                                            <input type="hidden" name="ideaScoreCode" id="ideaScoreCode" value="{{ $ideaScoring ? $ideaScoring->PISCode : 0 }}">

                                            <div id="ideaInfo">
                                                <div class="fv-row text-center">
                                                    <h4 class="">Let's analyse the requirement</h4>
                                                    <br>
                                                    <h5>Idea written by : {{ $projectIdea->user->USName }}</h5>
                                                </div>

                                                <div class="fv-row mb-8 mt-5">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                        <span class="">Project Idea</span>
                                                    </label>
                                                    <input type="text" class="form-control" id="projectIdea" name="projectIdea" placeholder="Enter project idea" value='" {{ $projectIdea->PIDesc }} "' readonly>
                                                </div>
                                                <hr class="mb-8">
                                                <div class="fv-row mb-8">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                        <span class="">1. User story statement</span>
                                                    </label>
                                                    <span>As a <input value="{{ $projectIdea->PIPersona }}" type="text" class="form-control w-25 d-inline" id="persona" name="persona" placeholder="<persona>" readonly> ,</span>
                                                    <span>I can <input value="{{ $projectIdea->PIAction }}" type="text" class="form-control w-25 d-inline" id="doAction" name="doAction" placeholder="<do something>" readonly> </span>
                                                    <span>so that <input value="{{ $projectIdea->PIImpact }}" type="text" class="form-control w-35 d-inline" id="impact" name="impact" placeholder="<impact/priority/value>" readonly> </span>
                                                </div>
                                                <div class="fv-row mb-8">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                        <span class="">2. Additional expectations of the idea.</span>
                                                    </label>
                                                    <textarea id="description" name="description" class="form-control" readonly
                                                    data-kt-autosize="true" placeholder="Tell about the desired flow and outcomes">{{ $projectIdea->PI_ReqDesc }}</textarea>
                                                </div>

                                                @if ($projectIdea->fileAttach)
                                                <div class="fv-row mb-8">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                        <span class="">3. Supplementary Diagram or Sketch.</span>
                                                    </label>
                                                    <a target="_blank" href="{{ route('file.view', $projectIdea->fileAttach->FAGuidID) }}" class="btn btn-info btn-sm">View</a>
                                                </div>
                                                @endif
                                            </div>

                                            <hr>

                                            <div id="scoreInfo">
                                                <div class="fv-row mb-8 mt-5">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                        <span class="">Based on the information given by {{ $projectIdea->user->USName }}, please determine the following:</span>
                                                    </label>
                                                </div>
                                                <div class="fv-row mb-8">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                        <span class="">1. Name of Requirement</span>
                                                    </label>
                                                    <input type="text" class="form-control" id="requirementName" name="requirementName" placeholder="Enter requirement name" value='{{ $ideaScoring ? $ideaScoring->PIS_ReqName : null }}'>
                                                </div>
                                                <div class="fv-row mb-8">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                        <span class="">2. Type of Requirement</span>
                                                    </label>
                                                    {!! Form::select('requirementType', $requirementType , $ideaScoring ? $ideaScoring->PIS_ReqType : null , [
                                                        'id' => 'requirementType',
                                                        'class' => 'form-select form-control',
                                                        'placeholder' => 'Choose type',
                                                    ]) !!}
                                                </div>
                                                <div class="fv-row mb-8">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                        <span class="">3. Details (if applicable)</span>
                                                    </label>
                                                    <textarea id="requirementDesc" name="requirementDesc" class="form-control"
                                                    data-kt-autosize="true" placeholder="Detailed explaination, if given.">{{ $ideaScoring ? $ideaScoring->PIS_ReqDesc : null }}</textarea>
                                                </div>
                                                <div class="fv-row mb-8">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                        <span class="">4. Rate the importance (1 to 100)</span>
                                                    </label>
                                                    <input type="number" class="form-control" id="requirementRate" name="requirementRate" max="100" min="1"
                                                    placeholder="Enter rate importance" value='{{ $ideaScoring ? $ideaScoring->PISRate : null }}'>
                                                </div>
                                                <div class="fv-row mb-8">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                        <span class="">5. Type of Importance</span>
                                                    </label>
                                                    {!! Form::select('moscowType', $moscowType , $ideaScoring ? $ideaScoring->PIS_MoscowType : null , [
                                                        'id' => 'moscowType',
                                                        'class' => 'form-select form-control',
                                                        'placeholder' => 'Choose type',
                                                    ]) !!}
                                                </div>

                                            </div>

                                            @if ($projectIdea->PI_PISComplete == 0)
                                            <div class="row">
                                                <div class="col-md-12 text-end">
                                                    <a onclick="confirmSubmitScore()" class="btn btn-primary btn-sm text-nowrap">
                                                    Submit
                                                    </a>
                                                </div>
                                            </div>
                                            @endif
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection

@push('modals')


@endpush

@push('script')

    <script>

        var table;

		(function ($) {

            projectCode = $('#projectCode').val();

            table = $('#project-tab').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:false,
                ajax:  {
                    "url" :"{{ route('project.idea.analysis.ideaProjectDatatable') }}",
                    "method": 'POST',
                    "data": function(d) {
                        d.projectCode = projectCode;
                    }
                },
                order: [[1, 'desc']],
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'PIDesc', data: 'PIDesc', class: 'text-start' },
                    { name: 'PICB', data: 'PICB', class: 'text-center' },
                    { name: 'action', data: 'action', class: 'text-center' },

                ]
            });
            table.buttons().container().appendTo('.button-table-export');

        })(jQuery);

        function reloadTable(){

            table.ajax.reload();
        }

    </script>

    <script>

        function confirmSubmitScore(){

            swal.fire({
                title: 'Are you sure?',
                text: "Requirement score of idea will submitted for this project.",
                type: 'warning',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes,submit it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitScore();
                    }
            })

        }

        function submitScore(){

            projectIdeaCode = $('#projectIdeaCode').val();
            ideaScoreCode = $('#ideaScoreCode').val();

            form = $('#scoringForm');
            var formData = new FormData(form[0]);

            formData.append('projectIdeaCode',projectIdeaCode);
            formData.append('ideaScoreCode',ideaScoreCode);
            toggleLoader();

            $.ajax({
                url: "{{ route('project.idea.scoring.submitScoring') }}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: false,
                data: formData,
                processData: false,
                cache: false,
                success: function (resp) {
                    console.log(resp);
                    toggleLoader();

                    swal.fire({
                        title: "Success",
                        text: resp.message,
                        icon: "success",
                        showCancelButton: false,
                        confirmButtonText: "Okay",
                        customClass: {
                            popup: 'swal-popup'
                        }
                    }).then((result) => {

                        routeHref = resp.redirect;

                        window.location.href = routeHref;

                    });


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

                        swal.fire("Warning", message, "warning");
                    }
                    else
                    {
                        var errors = '<p  id="fontSize" style="margin-top:2%; margin-bottom:1%; font-size: 25px;"><i>Invalid Information</i></p>';
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

                            // $('[name="' + key + '"]').closest('.form-group').addClass("has-error");
                            // $('[name="' + key + '"]').addClass("was-validated is-invalid invalid custom-select.is-invalid");
                            // $('#Valid'+key).empty();
                            // $('[name="' + key + '"]').closest('.form-group').append("<span id='Valid"+key+"' class=\"help-block\" style='color:red; font-family:Nunito, sans-serif;'>" + message[0] + "</span>");
                        });
                        swal.fire("Warning", errors, "warning",{html:true});
                        $('html, body').animate({
                            scrollTop: ($(".has-error").first().offset().top) - 200
                        }, 500);
                    }
                }
            });


        }

    </script>

@endpush
