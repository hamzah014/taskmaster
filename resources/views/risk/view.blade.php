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
                            <a class="btn btn-secondary btn-sm" href="{{ route('risk.index') }}"><i class="fa fa-chevron-left"></i> Back</a>
                        </div>
                    </div>

                    <div class="row flex-row mb-5">
                        <div class="col-md-12">
                            <h2>Project Risk Analysis</h2>
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
                                        <a id="risk_tab" class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab" role="tab" href="#risk_content">
                                            <span class="badge badge-dark me-2">2</span> Risk Analysis
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div id="info_content" class="card-body p-0 tab-pane fade" role="tabpanel" aria-labelledby="info_content">
                                    <div class="w-100">

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

                                    </div>
                                </div>
                                <div id="risk_content" class="card-body p-0 tab-pane fade show active" role="tabpanel" aria-labelledby="risk_content">
                                    <div class="w-100">

                                        <div class="fv-row text-center">
                                            <h4 class="">Let's defined risk for the app</h4>
                                        </div>

                                        <form id="riskForm" class="ajax-form-register" method="POST" enctype="multipart/form-data">
                                            @csrf

                                            <input type="hidden" name="projectCode" id="projectCode" value="{{ $project->PJCode }}">
                                            <input type="hidden" name="riskCode" id="riskCode" value="{{ $projectRisk ? $project->projectRisk->PRCode : 0 }}">

                                            <div class="row flex-row mb-5 mt-8">
                                                <div class="col-md-12">
                                                    <table class="table table-bordered border-dark" id="functional-tab">
                                                        <thead class="text-center bg-gray">
                                                            <tr>
                                                                <th class="text-center" rowspan="2">Dimension</th>
                                                                <th class="text-center" rowspan="2">Question</th>
                                                                <th class="text-center" colspan="3">Risk Severity
                                                                    <span class="ms-1" data-bs-toggle="tooltip" title="50 = High, Medium = 5, Low = 0">
                                                                        <i class="ki-duotone ki-information-5 text-primary fs-3">
                                                                            <span class="path1"></span>
                                                                            <span class="path2"></span>
                                                                            <span class="path3"></span>
                                                                        </i>
                                                                    </span>
                                                                </th>
                                                            </tr>
                                                            <tr>
                                                                <th class="text-center">Yes</th>
                                                                <th class="text-center">Neutral</th>
                                                                <th class="text-center">No</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>

                                                            <tr>
                                                                <td rowspan="2" class="text-center">Security</td>
                                                                <td>
                                                                    Will the application contain any possible
                                                                    confidential or sensitive data?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="security1" @if($projectRisk && $projectRisk->PR_Security1 == 'H') checked @endif >
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="security1" @if($projectRisk && $projectRisk->PR_Security1 == 'M') checked @endif >
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="security1" @if($projectRisk && $projectRisk->PR_Security1 == 'L') checked @endif >
                                                                    </center>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td>
                                                                    Will the application need high amount of user access?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="security2" @if($projectRisk && $projectRisk->PR_Security2 == 'H') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="security2" @if($projectRisk && $projectRisk->PR_Security2 == 'M') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="security2" @if($projectRisk && $projectRisk->PR_Security2 == 'L') checked @endif />
                                                                    </center>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td rowspan="2" class="text-center">Operational IT</td>
                                                                <td>
                                                                    Will any dedicated developer be available if further developments are required for this application?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="operational1" @if($projectRisk && $projectRisk->PR_Operational1 == 'H') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="operational1" @if($projectRisk && $projectRisk->PR_Operational1 == 'M') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="operational1" @if($projectRisk && $projectRisk->PR_Operational1 == 'L') checked @endif />
                                                                    </center>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    Will any technical IT support be available for maintenance?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="operational2" @if($projectRisk && $projectRisk->PR_Operational2 == 'H') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="operational2" @if($projectRisk && $projectRisk->PR_Operational2 == 'M') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="operational2" @if($projectRisk && $projectRisk->PR_Operational2 == 'L') checked @endif />
                                                                    </center>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td class="text-center">IT Architecture</td>
                                                                <td>
                                                                    Will this application affect any other existing systems within your organisation?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="architect" @if($projectRisk && $projectRisk->PR_Architect == 'H') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="architect" @if($projectRisk && $projectRisk->PR_Architect == 'M') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="architect" @if($projectRisk && $projectRisk->PR_Architect == 'L') checked @endif />
                                                                    </center>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td class="text-center">Regulatory</td>
                                                                <td>
                                                                    Are there any compliance requirements from your organisation?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="regulatory" @if($projectRisk && $projectRisk->PR_Regulatory == 'H') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="regulatory" @if($projectRisk && $projectRisk->PR_Regulatory == 'M') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="regulatory" @if($projectRisk && $projectRisk->PR_Regulatory == 'L') checked @endif />
                                                                    </center>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td rowspan="2" class="text-center">Reputational</td>
                                                                <td>
                                                                    Will the application interact with your customer or external stakeholders?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="reputation1" @if($projectRisk && $projectRisk->PR_Reputation1 == 'H') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="reputation1" @if($projectRisk && $projectRisk->PR_Reputation1 == 'M') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="reputation1" @if($projectRisk && $projectRisk->PR_Reputation1 == 'L') checked @endif />
                                                                    </center>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    Will the application be used only for internal usage?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="reputation2" @if($projectRisk && $projectRisk->PR_Reputation2 == 'H') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="reputation2" @if($projectRisk && $projectRisk->PR_Reputation2 == 'M') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="reputation2" @if($projectRisk && $projectRisk->PR_Reputation2 == 'L') checked @endif />
                                                                    </center>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td class="text-center">Financial</td>
                                                                <td>
                                                                    Will the application have any possible financial impact on your organisation's revenue if anything goes wrong?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="financial" @if($projectRisk && $projectRisk->PR_Financial == 'H') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="financial" @if($projectRisk && $projectRisk->PR_Financial == 'M') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="financial" @if($projectRisk && $projectRisk->PR_Financial == 'L') checked @endif />
                                                                    </center>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td class="text-center">Building the Application</td>
                                                                <td>
                                                                    Do you think it is straightforward to define what the application should do and how users will interact with it (during the Requirements Analysis)?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="buildApp" @if($projectRisk && $projectRisk->PR_BuildApp == 'H') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="buildApp" @if($projectRisk && $projectRisk->PR_BuildApp == 'M') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="buildApp" @if($projectRisk && $projectRisk->PR_BuildApp == 'L') checked @endif />
                                                                    </center>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td class="text-center">Integration with IT Landscape</td>
                                                                <td>
                                                                    Will this application be expected to integrate with any other systems?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="integrate" @if($projectRisk && $projectRisk->PR_Integrate == 'H') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="integrate" @if($projectRisk && $projectRisk->PR_Integrate == 'M') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="integrate" @if($projectRisk && $projectRisk->PR_Integrate == 'L') checked @endif />
                                                                    </center>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td class="text-center">User Interface Creation</td>
                                                                <td>
                                                                    Will the app involve heavy designing and creation of user interfaces?
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="H" name="uicreate" @if($projectRisk && $projectRisk->PR_UICreate == 'H') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="M" name="uicreate" @if($projectRisk && $projectRisk->PR_UICreate == 'M') checked @endif />
                                                                    </center>
                                                                </td>
                                                                <td>
                                                                    <center>
                                                                        <input class="form-check-input" type="radio" value="L" name="uicreate" @if($projectRisk && $projectRisk->PR_UICreate == 'L') checked @endif />
                                                                    </center>
                                                                </td>
                                                            </tr>


                                                        </tbody>
                                                    </table>


                                                </div>
                                            </div>

                                        </form>

                                        <div class="fv-row text-center">
                                            <a onclick="confirmSubmitForm()" class="btn btn-success">Submit Risk</a>
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


@endpush

@push('script')

    <script>

        var table;

		(function ($) {

            functionType = "F";
            nonfunctionType = "NF";

        })(jQuery);

    </script>

    <script>

        function confirmSubmitForm(){

            swal.fire({
                title: 'Are you sure?',
                text: "Risk analysis will submitted for this project.",
                type: 'warning',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes,submit it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitRisk();
                }
            })

        }

        function submitRisk(){

            form = $('#riskForm');
            var formData = new FormData(form[0]);

            toggleLoader();

            $.ajax({
                url: "{{ route('risk.submitRisk') }}",
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
                            text: "Risk analysis has been completely submitted.",
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
