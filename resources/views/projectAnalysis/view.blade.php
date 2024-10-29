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
                            <a class="btn btn-secondary btn-sm" href="{{ route('project.analysis.index') }}"><i class="fa fa-chevron-left"></i> Back</a>
                        </div>
                    </div>

                    <div class="row flex-row mb-5">
                        <div class="col-md-12">
                            <h2>Project Idea Detail</h2>
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
                                        <a id="analysis_tab" class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab" role="tab" href="#analysis_content">
                                            <span class="badge badge-dark me-2">2</span> Requirements Acceptance
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
                                <div id="analysis_content" class="card-body p-0 tab-pane fade show active" role="tabpanel" aria-labelledby="analysis_content">
                                    <div class="w-100">

                                        <div class="fv-row text-center">
                                            <h4 class="">Let's review the requirements for this app</h4>
                                        </div>

                                        <div class="row flex-row mb-5 mt-8">
                                            <div class="col-md-12">
                                                <h5>Functional Requirements:</h5>
                                            </div>
                                            <div class="col-md-12">
                                                <table class="table table-bordered text-center border-dark" id="functional-tab">
                                                    <thead class="text-center bg-gray">
                                                        <th class="text-center">No.</th>
                                                        <th class="text-center">Feature</th>
                                                        <th class="text-center">Requirement</th>
                                                        <th class="text-center">Importance Score(/100)</th>
                                                        <th class="text-center">
                                                            MoSCoW Rating
                                                            <span class="ms-1" data-bs-toggle="tooltip" title="{{ $infoMoscow }}">
                                                                <i class="ki-duotone ki-information-5 text-info fs-3">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                    <span class="path3"></span>
                                                                </i>
                                                            </span>
                                                        </th>
                                                    </thead>
                                                </table>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row flex-row mb-5 mt-8">
                                            <div class="col-md-12">
                                                <h5>Non-Functional Requirements:</h5>
                                            </div>
                                            <div class="col-md-12">
                                                <table class="table table-bordered text-center border-dark" id="nonfunctional-tab">
                                                    <thead class="text-center bg-gray">
                                                        <th class="text-center">No.</th>
                                                        <th class="text-center">Feature</th>
                                                        <th class="text-center">Requirement</th>
                                                        <th class="text-center">Importance Score(/100)</th>
                                                        <th class="text-center">
                                                            MoSCoW Rating
                                                            <span class="ms-1" data-bs-toggle="tooltip" title="{{ $infoMoscow }}">
                                                                <i class="ki-duotone ki-information-5 text-info fs-3">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                    <span class="path3"></span>
                                                                </i>
                                                            </span>
                                                        </th>
                                                    </thead>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="fv-row text-center">
                                            <a onclick="confirmSubmitAnalysis()" class="btn btn-success">Accept Requirements</a>
                                        </div>

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

    <div class="modal fade" id="modal-idea"  data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered justify-content-center modal-dialog-scrollable w-100">
            <div class="modal-content w-80">
                <div class="modal-header">
                    <h3 class="modal-title">View Feature</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-close fs-1"></i>
                    </div>
                </div>

                <div class="modal-body">
                    <div id="ideaArea" class="w-100">
                    </div>
                </div>
            </div>
        </div>
    </div>

@endpush

@push('script')

    <script>

        var table;

		(function ($) {

            functionType = "F";
            nonfunctionType = "NF";

            projectCode = $('#projectCode').val();

            table = $('#functional-tab').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:false,
                ajax:  {
                    "url" :"{{ route('project.analysis.projectAnalysisDatatable') }}",
                    "method": 'POST',
                    "data": function(d) {
                        d.projectCode = projectCode;
                        d.type = functionType;
                    }
                },
                order: [[1, 'desc']],
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'PIDesc', data: 'PIDesc', class: 'text-start' },
                    { name: 'PIS_ReqDesc', data: 'PIS_ReqDesc', class: 'text-start' },
                    { name: 'PISRate', data: 'PISRate', class: 'text-center' },
                    { name: 'PIS_MoscowType', data: 'PIS_MoscowType', class: 'text-center' },

                ]
            });
            table.buttons().container().appendTo('.button-table-export');

            tableNF = $('#nonfunctional-tab').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:false,
                ajax:  {
                    "url" :"{{ route('project.analysis.projectAnalysisDatatable') }}",
                    "method": 'POST',
                    "data": function(d) {
                        d.projectCode = projectCode;
                        d.type = nonfunctionType;
                    }
                },
                order: [[1, 'desc']],
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'PIDesc', data: 'PIDesc', class: 'text-start' },
                    { name: 'PIS_ReqDesc', data: 'PIS_ReqDesc', class: 'text-start' },
                    { name: 'PISRate', data: 'PISRate', class: 'text-center' },
                    { name: 'PIS_MoscowType', data: 'PIS_MoscowType', class: 'text-center' },

                ]
            });
            tableNF.buttons().container().appendTo('.button-table-export');

        })(jQuery);

        function viewIdea(ideaScoreCode){

            $('#ideaArea').html("");

            var formData = new FormData();

            formData.append('ideaScoreCode',ideaScoreCode);
            toggleLoader();

            $.ajax({
                url: "{{ route('project.analysis.viewIdea') }}",
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

                    $('#ideaArea').html(resp);

                    $('#modal-idea').modal('show');


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

    <script>

        function confirmSubmitAnalysis(){

            swal.fire({
                title: 'Are you sure?',
                text: "Acceptance Requirement will submitted for this project.",
                type: 'warning',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes,submit it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitAnalysis();
                    }
            })

        }

        function submitAnalysis(){

            projectCode = $('#projectCode').val();

            var formData = new FormData();

            formData.append('projectCode',projectCode);
            toggleLoader();

            $.ajax({
                url: "{{ route('project.analysis.submitAnalysis') }}",
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

                    console.log('Server response:', resp);
                    if(resp.success == true){

                        swal.fire({
                            title: "Success",
                            text: "Requirements Acceptance has been completely submitted.",
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

                    }


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
