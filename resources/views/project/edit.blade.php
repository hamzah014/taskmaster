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


                    <div class="row flex-row mb-5">
                        <div class="col-md-12">
                            <h2>View Project</h2>
                        </div>
                    </div>

                    <div class="row card flex-row">
                        <div class="col-md-12 col-sm-12">
                            <div class="stepper stepper-pills stepper-column d-flex flex-column flex-xl-row flex-row-fluid my-8" id="kt_create_account_stepper">
                                <div class="d-flex justify-content-start justify-content-xl-start flex-row-auto w-100 w-xl-300px mb-10">
                                    <div class="stepper-nav ps-lg-10">
                                        <div class="stepper-item current" data-kt-stepper-element="nav" data-step="1">
                                            <div class="stepper-wrapper">
                                                <div class="stepper-icon w-40px h-40px">
                                                    <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                                    <span class="stepper-number">1</span>
                                                </div>
                                                <div class="stepper-label">
                                                    <h3 class="stepper-title">Details</h3>
                                                    <div class="stepper-desc">Details of project</div>
                                                </div>
                                            </div>
                                            <div class="stepper-line h-40px"></div>
                                        </div>
                                        <div class="stepper-item" data-kt-stepper-element="nav" data-step="2">
                                            <div class="stepper-wrapper">
                                                <div class="stepper-icon w-40px h-40px">
                                                    <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                                    <span class="stepper-number">2</span>
                                                </div>
                                                <div class="stepper-label">
                                                    <h3 class="stepper-title">Role Assignment</h3>
                                                    <div class="stepper-desc">Team line-up</div>
                                                </div>
                                            </div>
                                            <div class="stepper-line h-40px"></div>
                                        </div>
                                        <div class="stepper-item" data-kt-stepper-element="nav" data-step="3">
                                            <div class="stepper-wrapper">
                                                <div class="stepper-icon w-40px h-40px">
                                                    <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                                    <span class="stepper-number">3</span>
                                                </div>
                                                <div class="stepper-label">
                                                    <h3 class="stepper-title">Project Document</h3>
                                                    <div class="stepper-desc">Project document submission</div>
                                                </div>
                                            </div>
                                            @if( !in_array($project->PJStatus, ['PENDING']) )
                                            <div class="stepper-line h-40px"></div>
                                            @endif
                                        </div>

                                        @if( !in_array($project->PJStatus, ['PENDING']) )

                                            <div class="stepper-item" data-kt-stepper-element="nav" data-step="4">
                                                <div class="stepper-wrapper">
                                                    <div class="stepper-icon w-40px h-40px">
                                                        <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                                        <span class="stepper-number">4</span>
                                                    </div>
                                                    <div class="stepper-label">
                                                        <h3 class="stepper-title">Project Idea</h3>
                                                        <div class="stepper-desc">Project idea list</div>
                                                    </div>
                                                </div>
                                                @if( in_array($project->PJStatus, ['RISK']) )
                                                <div class="stepper-line h-40px"></div>
                                                @endif
                                            </div>

                                            @if( in_array($project->PJStatus, ['RISK']) )

                                                <div class="stepper-item" data-kt-stepper-element="nav" data-step="5">
                                                    <div class="stepper-wrapper">
                                                        <div class="stepper-icon w-40px h-40px">
                                                            <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                                            <span class="stepper-number">5</span>
                                                        </div>
                                                        <div class="stepper-label">
                                                            <h3 class="stepper-title">Project Risk</h3>
                                                            <div class="stepper-desc">Project Risk list</div>
                                                        </div>
                                                    </div>
                                                </div>

                                            @endif

                                        @endif

                                    </div>
                                </div>
                                <div class="flex-row-fluid py-lg-5 px-lg-15">
                                    <div class="forms" id="kt_modal_create_app_form">

                                        <div class="current" data-kt-stepper-element="content" data-step="1">
                                            <div class="w-100">
                                                <form id="daftarForm" class="ajax-form-register" method="POST" action="{{ route('project.updateInfo',[$project->PJCode]) }}" enctype="multipart/form-data">
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
                                                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter project name" value="{{ $project->PJName }}" />
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
                                                        <textarea id="description" name="description" class="form-control" data-kt-autosize="true" placeholder="Project description">{{ $project->PJDesc }}</textarea>
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
                                                            <input type="date" class="form-control" id="startDate" name="startDate" placeholder="Start date" value="{{ $project->PJStartDate }}"/>
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
                                                            <input type="date" class="form-control" id="endDate" name="endDate" placeholder="End date" value="{{ $project->PJEndDate }}"/>
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
                                                                aria-label="Budget project" aria-describedby="basic-addon1" value="{{ $project->PJBudget }}">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @if( in_array($project->PJStatus, ['PENDING']) )
                                                    <div class="row">
                                                        <div class="col-md-12 text-end">
                                                            <div class="mt-7">
                                                                <button type="submit" class="btn btn-primary text-nowrap">
                                                                Continue <i class="fas fa-arrow-right text-white fs-4 ms-1 me-0"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif

                                                </form>
                                            </div>
                                        </div>

                                        <div class="" data-kt-stepper-element="content" data-step="2">
                                            <div class="w-100">
                                                <form id="memberForm" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="fv-row">

                                                        <div id="registerform">

                                                            <h4 class="">Role Assignment</h4>
                                                            <h5>Please select member to be on this project:</h5>

                                                            @if( in_array($project->PJStatus, ['PENDING']) )
                                                            <div class="row mb-10 mt-8">
                                                                <div class="col-md-12">
                                                                    <div class="input-group mb-5">
                                                                        <span class="input-group-text">Search by Email</span>
                                                                        <input type="text" class="form-control" id="searchEmail" name="searchEmail" aria-label="Search Email"/>
                                                                        <span class="input-group-text btn btn-info" onclick="searchUser()">Search</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @endif

                                                            <div class="row mb-10 mt-8">
                                                                <div class="col-md-12">
                                                                    <h5>List of Team Member</h5>
                                                                    <table class="table table-bordered text-center border-dark" id="memberList">
                                                                        <thead class="text-center bg-gray">
                                                                            <th>Member Name</th>
                                                                            <th>Member Email</th>
                                                                            <th>Role</th>
                                                                            <th>Action</th>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($project->projectTeam as $index => $projectTeam)
                                                                            <tr>
                                                                                <td>
                                                                                    <input type="hidden" name="projectTeamID[]" value="{{ $projectTeam->PTID }}">
                                                                                    <input type="hidden" name="memberID[]" value="{{ $projectTeam->user->USCode }}">
                                                                                    <input type="text" name="memberName[]" id="memberName[]" class="form-control" value="{{ $projectTeam->user->USName }}" readonly>
                                                                                </td>
                                                                                <td>
                                                                                    <input type="text" name="memberEmail[]" id="memberEmail[]" class="form-control" value="{{ $projectTeam->user->USEmail }}" readonly>
                                                                                </td>
                                                                                <td>
                                                                                    {!! Form::select('memberRole[]', $roleUser , $projectTeam->PT_RLCode, [
                                                                                        'id' => 'memberRole',
                                                                                        'class' => 'form-select form-control',
                                                                                        'placeholder' => 'Choose role',
                                                                                    ]) !!}
                                                                                </td>
                                                                                <td>
                                                                                    @if( in_array($project->PJStatus, ['PENDING']) )
                                                                                    <a class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove();">Delete</a>
                                                                                    @endif
                                                                                </td>

                                                                            </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>

                                                            @if( in_array($project->PJStatus, ['PENDING']) )
                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <div class="d-flex flex-stack pt-10">
                                                                        <div class="mr-2">
                                                                            <a onclick="backPage()" class="btn btn-secondary btn-sm text-nowrap">
                                                                                <i class="fas fa-arrow-left text-dark fs-4 ms-1 me-0"></i> Previous
                                                                            </a>
                                                                        </div>
                                                                        <div class="text-end">
                                                                            <a onclick="storeMember()" class="btn btn-primary btn-sm text-nowrap">
                                                                                Continue <i class="fas fa-arrow-right text-white fs-4 ms-1 me-0"></i>
                                                                                </a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @endif

                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="" data-kt-stepper-element="content" data-step="3">
                                            <div class="w-100">
                                                <form id="documentForm" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="fv-row">
                                                        <h4 class="">Project Document</h4>
                                                        <h5>Please add document related to this project:</h5>

                                                        <div class="row mb-10 mt-8">

                                                            @if( in_array($project->PJStatus, ['PENDING']) )
                                                            <div class="col-md-12 text-end">
                                                                <a class="btn btn-primary btn-sm" onclick="addDocumentList()">Add Document</a>
                                                            </div>
                                                            @endif

                                                            <div class="col-md-12">
                                                                <h5>List of Document</h5>
                                                                <table class="table table-bordered text-center border-dark" id="documentList">
                                                                    <thead class="text-center bg-gray">
                                                                        <th>Document Name</th>
                                                                        <th>File</th>
                                                                        <th>Action</th>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($project->projectDocument as $index => $projectDocument)
                                                                        <tr>
                                                                            <td>
                                                                                <input type="hidden" name="documentID[]" value="{{ $projectDocument->PDCode }}">
                                                                                <input type="text" name="documentDesc[]" id="documentDesc[]" class="form-control" value="{{ $projectDocument->PDDesc }}">
                                                                            </td>
                                                                            <td>
                                                                                <input type="hidden" name="fileID[]" value="documentFile_{{ $projectDocument->PDCode }}">
                                                                                <input type="file" name="documentFile_{{ $projectDocument->PDCode }}[]" id="documentFile_{{ $projectDocument->PDCode }}[]"
                                                                                 class="form-control" @if( !in_array($project->PJStatus, ['PENDING']) ) disabled @endif>
                                                                            </td>
                                                                            <td>
                                                                                <a class="btn btn-secondary btn-sm" target="_blank" href="{{ route('file.view',$projectDocument->fileAttach->FAGuidID) }}">View</a>

                                                                                @if( in_array($project->PJStatus, ['PENDING']) )
                                                                                <a class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove();">Delete</a>
                                                                                @endif

                                                                            </td>

                                                                        </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>

                                                        @if( in_array($project->PJStatus, ['PENDING']) )
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="d-flex flex-stack pt-10">
                                                                    <div class="mr-2">
                                                                        <a onclick="backPage()" class="btn btn-secondary btn-sm text-nowrap">
                                                                            <i class="fas fa-arrow-left text-dark fs-4 ms-1 me-0"></i> Back
                                                                        </a>
                                                                    </div>
                                                                    <div class="text-end">
                                                                        <a onclick="storeDocument(0)" class="btn btn-cyan btn-sm text-nowrap">
                                                                        Save
                                                                        </a>
                                                                        <a onclick="storeDocument(1)" class="btn btn-success btn-sm text-nowrap">
                                                                        Finish
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endif

                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        @if( !in_array($project->PJStatus, ['PENDING']) )

                                            <div class="" data-kt-stepper-element="content" data-step="4">
                                                <div class="w-100">
                                                    <form id="ideaForm" enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="fv-row">
                                                            <h4 class="">Project Idea</h4>
                                                            <h5>List idea for this project:</h5>

                                                            <div class="fv-row mt-8">
                                                                <table class="table table-bordered text-center border-dark" id="project-tab">
                                                                    <thead class="text-center bg-gray">
                                                                        <th class="text-center w-5">No.</th>
                                                                        <th class="text-center w-65">Idea</th>
                                                                        <th class="text-center">Submitted By</th>
                                                                    </thead>
                                                                </table>
                                                            </div>

                                                            @if( in_array($project->PJStatus, ['IDEA']) )
                                                            <div class="row">
                                                                <div class="col-md-12 text-end">
                                                                    <div class="mt-7">
                                                                        <a onclick="confirmSubmitIdea()" class="btn btn-primary text-nowrap">
                                                                        Submit Idea
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @endif

                                                            @if( in_array($project->PJStatus, ['IDEA-ALS']) )
                                                            <div class="row">
                                                                <div class="col-md-12 text-end">
                                                                    <div class="mt-7">
                                                                        <a onclick="confirmSubmitReq()" class="btn btn-primary text-nowrap">
                                                                        Submit Analysis
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @endif

                                                            @if( in_array($project->PJStatus, ['IDEA-SCR']) )
                                                            <div class="row">
                                                                <div class="col-md-12 text-end">
                                                                    <div class="mt-7">
                                                                        <a onclick="confirmSubmitScore()" class="btn btn-primary text-nowrap">
                                                                        Submit Scoring
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            @endif


                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                            @if( in_array($project->PJStatus, ['RISK']) )

                                                <div class="" data-kt-stepper-element="content" data-step="5">
                                                    <input type="hidden" name="riskCode" id="riskCode" value="{{ $projectRisk ? $projectRisk->PRCode : 0 }}">
                                                    <div class="w-100">
                                                        <form id="ideaForm" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="fv-row">
                                                                <h4 class="">Project Risk</h4>
                                                                <h5>Risk analysis for this project:</h5>

                                                                <div class="fv-row mt-8">

                                                                    <table class="table table-bordered border-dark" id="functional-tab">
                                                                        <thead class="text-center bg-gray">
                                                                            <tr>
                                                                                <th class="text-center" rowspan="2">Dimension</th>
                                                                                <th class="text-center" rowspan="2">Question</th>
                                                                                <th class="text-center" colspan="3">Risk Severity
                                                                                    <span class="ms-1" data-bs-toggle="tooltip" title="50 = High, Medium = 5, Low = 0">
                                                                                        <i class="ki-duotone ki-information-5 text-info fs-3">
                                                                                            <span class="path1"></span>
                                                                                            <span class="path2"></span>
                                                                                            <span class="path3"></span>
                                                                                        </i>
                                                                                    </span>
                                                                                </th>
                                                                            </tr>
                                                                            <tr>
                                                                                <th class="text-center">High</th>
                                                                                <th class="text-center">Medium</th>
                                                                                <th class="text-center">Low</th>
                                                                            </tr>
                                                                        </thead>

                                                                        @if($projectRisk)

                                                                        <tbody>

                                                                            <tr>
                                                                                <td rowspan="2" class="text-center">Security</td>
                                                                                <td>
                                                                                    Will the application contain any possible
                                                                                    confidential or sensitive data?
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="security1" @if($projectRisk && $projectRisk->PR_Security1 == 'H') checked @endif >
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="security1" @if($projectRisk && $projectRisk->PR_Security1 == 'M') checked @endif >
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="security1" @if($projectRisk && $projectRisk->PR_Security1 == 'L') checked @endif >
                                                                                    </center>
                                                                                </td>
                                                                            </tr>

                                                                            <tr>
                                                                                <td>
                                                                                    Will the application need high amount of user access?
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="security2" @if($projectRisk && $projectRisk->PR_Security2 == 'H') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="security2" @if($projectRisk && $projectRisk->PR_Security2 == 'M') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="security2" @if($projectRisk && $projectRisk->PR_Security2 == 'L') checked @endif />
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
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="operational1" @if($projectRisk && $projectRisk->PR_Operational1 == 'H') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="operational1" @if($projectRisk && $projectRisk->PR_Operational1 == 'M') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="operational1" @if($projectRisk && $projectRisk->PR_Operational1 == 'L') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    Will any technical IT support be available for maintenance?
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="operational2" @if($projectRisk && $projectRisk->PR_Operational2 == 'H') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="operational2" @if($projectRisk && $projectRisk->PR_Operational2 == 'M') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="operational2" @if($projectRisk && $projectRisk->PR_Operational2 == 'L') checked @endif />
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
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="architect" @if($projectRisk && $projectRisk->PR_Architect == 'H') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="architect" @if($projectRisk && $projectRisk->PR_Architect == 'M') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="architect" @if($projectRisk && $projectRisk->PR_Architect == 'L') checked @endif />
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
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="regulatory" @if($projectRisk && $projectRisk->PR_Regulatory == 'H') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="regulatory" @if($projectRisk && $projectRisk->PR_Regulatory == 'M') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="regulatory" @if($projectRisk && $projectRisk->PR_Regulatory == 'L') checked @endif />
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
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="reputation1" @if($projectRisk && $projectRisk->PR_Reputation1 == 'H') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="reputation1" @if($projectRisk && $projectRisk->PR_Reputation1 == 'M') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="reputation1" @if($projectRisk && $projectRisk->PR_Reputation1 == 'L') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    Will the application be used only for internal usage?
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="reputation2" @if($projectRisk && $projectRisk->PR_Reputation2 == 'H') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="reputation2" @if($projectRisk && $projectRisk->PR_Reputation2 == 'M') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="reputation2" @if($projectRisk && $projectRisk->PR_Reputation2 == 'L') checked @endif />
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
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="financial" @if($projectRisk && $projectRisk->PR_Financial == 'H') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="financial" @if($projectRisk && $projectRisk->PR_Financial == 'M') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="financial" @if($projectRisk && $projectRisk->PR_Financial == 'L') checked @endif />
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
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="buildApp" @if($projectRisk && $projectRisk->PR_BuildApp == 'H') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="buildApp" @if($projectRisk && $projectRisk->PR_BuildApp == 'M') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="buildApp" @if($projectRisk && $projectRisk->PR_BuildApp == 'L') checked @endif />
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
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="integrate" @if($projectRisk && $projectRisk->PR_Integrate == 'H') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="integrate" @if($projectRisk && $projectRisk->PR_Integrate == 'M') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="integrate" @if($projectRisk && $projectRisk->PR_Integrate == 'L') checked @endif />
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
                                                                                        <input class="form-check-input" disabled type="radio" value="H" name="uicreate" @if($projectRisk && $projectRisk->PR_UICreate == 'H') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="M" name="uicreate" @if($projectRisk && $projectRisk->PR_UICreate == 'M') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                                <td>
                                                                                    <center>
                                                                                        <input class="form-check-input" disabled type="radio" value="L" name="uicreate" @if($projectRisk && $projectRisk->PR_UICreate == 'L') checked @endif />
                                                                                    </center>
                                                                                </td>
                                                                            </tr>


                                                                        </tbody>

                                                                        @endif
                                                                    </table>
                                                                </div>

                                                                @if( in_array($project->PJStatus, ['RISK']) )
                                                                <div class="row">
                                                                    <div class="col-md-12 text-end">
                                                                        <div class="mt-7">
                                                                            <a onclick="confirmSubmitRisk()" class="btn btn-primary text-nowrap">
                                                                            Submit Risk
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                @endif

                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>

                                            @endif

                                        @endif

                                        <div class="d-flex flex-stack pt-10">
                                            <div class="mr-2 d-none">
                                                <button id="backButton" type="button" class="btn vksb-btn btn-secondary me-3" data-kt-stepper-action="previous">
                                                <i class="fas fa-arrow-left text-white fs-4 me-1">
                                                </i>Kembali</button>
                                            </div>
                                            <div class="text-end d-none">
                                                <button type="button" class="btn btn-lg btn-primary me-3" data-kt-stepper-action="submit">
                                                    <span class="indicator-label">Hantar
                                                    <i class="ki-duotone ki-arrow-right fs-3 ms-2 me-0">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i></span>
                                                    <span class="indicator-progress">Sila tunggu...
                                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                                </button>
                                                <button id="nextButton" type="button" class="btn vksb-btn btn-cyan d-none" data-kt-stepper-action="next">Seterusnya
                                                <i class="fas fa-arrow-right text-white fs-4 ms-1 me-0">
                                                </i></button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-5">
                        <div class="col-md-12 text-start">
                            <a class="btn btn-secondary btn-sm" href="{{ route('project.index') }}"><i class="fa fa-chevron-left"></i> Back</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection

@push('modals')

    <div class="modal fade" id="modal-search"  data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered justify-content-center">
            <div class="modal-content w-80">
                <div class="modal-header">
                    <h3 class="modal-title">Result Search</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-close fs-1"></i>
                    </div>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="resultID" id="resultID" value="0">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input readonly type="text" class="form-control" name="resultName" id="resultName" placeholder="Result name"/>
                                <label for="resultName">Name</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input readonly type="text" class="form-control" name="resultEmail" id="resultEmail" placeholder="Result email"/>
                                <label for="resultEmail">Email</label>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12 text-end">
                            <a class="btn btn-info btn-sm" onclick="selectUser()">Select User</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endpush

@push('script')

    <script>

		(function ($) {

            projectCode = $('#projectCode').val();

            table = $('#project-tab').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:false,
                ajax:  {
                    "url" :"{{ route('project.idea.ideaProjectDatatable') }}",
                    "method": 'POST',
                    "data": function(d) {
                        d.projectCode = projectCode;
                    }
                },
                order: [[1, 'desc']],
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'viewIdea', data: 'viewIdea', class: 'text-start' },
                    { name: 'PICB', data: 'PICB', class: 'text-center' },

                ]
            });
            table.buttons().container().appendTo('.button-table-export');

        })(jQuery);

    </script>

    <script>

        function backPage(){
            $('#backButton').click();
        }

        $(document).ready(function(){

            nextPage = '{{ $editPage }}';

            if(nextPage == 1){
                $('#nextButton').click();
            }

            ajaxSubmitFormRegister('form.ajax-form-register');

        });

        function ajaxSubmitFormRegister(form, callback) {
            $(form).on("submit", function (e) {
                e.preventDefault();
                urlAction = $(this).attr("action");
                var formData = new FormData(this);

                toggleLoader();

                ajaxFormXHR = $.ajax({
                    url: urlAction,
                    type: 'POST',
                    contentType: false,
                    data: formData,
                    processData: false,
                    cache: false,
                    success: function (resp) {
                        toggleLoader();
                        console.log(resp);

                        if (typeof callback == 'function') {
                            callback(resp);
                        } else if ($(form).attr('data-success') !== undefined) {
                            eval($(form).attr('data-success') + '(resp)');
                        } else {

                            if (typeof resp.datatables != "undefined") {
                                resp.datatables.forEach(function(element) {
                                    $('#'+element).DataTable().ajax.reload();
                                });
                            }

                            if (resp.message) {
                                var is_html = false;

                                if (resp.html)
                                {
                                    is_html = true;
                                }

                                $('#nextButton').click();

                            }
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
                })
            });
        }

    </script>

    <script>

        function searchUser(){

            email = $('#searchEmail').val();

            formData = new FormData();

            formData.append('email', email);
            toggleLoader();

            $.ajax({
                url: "{{ route('project.searchUser') }}",
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

                        $('#resultID').val(resp.id);
                        $('#resultName').val(resp.name);
                        $('#resultEmail').val(resp.email);

                        $('#modal-search').modal('show');

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
                        });
                        swal.fire("Warning", errors, "warning",{html:true});
                        $('html, body').animate({
                            scrollTop: ($(".has-error").first().offset().top) - 200
                        }, 500);
                    }
                }
            });

        }

        function selectUser(){

            $('#modal-search').modal('hide');

            id = $('#resultID').val();
            name = $('#resultName').val();
            email = $('#resultEmail').val();

            var roleSelect = `
                {!! Form::select('memberRole[]', $roleUser , null, [
                    'id' => 'memberRole',
                    'class' => 'form-select form-control',
                    'placeholder' => 'Choose role',
                ]) !!}
            `;

            var tableBody = $('#memberList').find('tbody');

            var newRow = $('<tr>');

            newRow.append($('<input>').attr({
                type: 'hidden',
                class: 'form-control',
                name: 'memberID[]',
                value: id
            }));

            newRow.append($('<input>').attr({
                type: 'hidden',
                class: 'form-control',
                name: 'projectTeamID[]',
                value: 0
            }));

            newRow.append($('<td>').append($('<input>').attr({
                type: 'text',
                class: 'form-control',
                name: 'memberName[]',
                value: name,
                readonly: true // Add readonly attribute
            })));

            newRow.append($('<td>').append($('<input>').attr({
                type: 'text',
                class: 'form-control',
                name: 'memberEmail[]',
                value: email,
                readonly: true // Add readonly attribute
            })));

            newRow.append($('<td>').append(roleSelect));

            var deleteButton = $('<a>').addClass('btn btn-danger btn-sm').text('Delete');
            deleteButton.on('click', function() {
                $(this).closest('tr').remove();
            });

            newRow.append($('<td>').append(deleteButton));

            tableBody.append(newRow);

            resetSearchUser();

        }

        function resetSearchUser(){

            $('#resultID').val('');
            $('#resultName').val('');
            $('#resultEmail').val('');

        }

        function storeMember(){

            form = $('#memberForm');
            var formData = new FormData(form[0]);

            projectCode = $('#projectCode').val();
            console.log(projectCode);

            formData.append('projectCode', projectCode);

            toggleLoader();

            $.ajax({
                url: "{{ route('project.storeMember') }}",
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

                        $('#nextButton').click();

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

        function addDocumentList(){

            var tableBody = $('#documentList').find('tbody');

            var newRow = $('<tr>');

            filecode = generateRandomCode();

            newRow.append($('<input>').attr({
                type: 'hidden',
                class: 'form-control',
                name: 'documentID[]',
                value: 0
            }));

            newRow.append($('<input>').attr({
                type: 'hidden',
                class: 'form-control',
                name: 'fileID[]',
                value: 'documentFile_'+filecode
            }));

            newRow.append($('<td>').append($('<input>').attr({
                type: 'text',
                class: 'form-control',
                name: 'documentDesc[]',
            })));

            newRow.append($('<td>').append($('<input>').attr({
                type: 'file',
                class: 'form-control',
                name: 'documentFile_'+filecode+'[]',
            })));

            var deleteButton = $('<a>').addClass('btn btn-danger btn-sm').text('Delete');
            deleteButton.on('click', function() {
                $(this).closest('tr').remove();
            });

            newRow.append($('<td>').append(deleteButton));

            tableBody.append(newRow);

        }

        function storeDocument(status){


            form = $('#documentForm');
            var formData = new FormData(form[0]);

            projectCode = $('#projectCode').val();
            formData.append('projectCode', projectCode);
            formData.append('status', status);

            toggleLoader();

            $.ajax({
                url: "{{ route('project.storeDocument') }}",
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
                            text: "Project has been completely updated.",
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

        function confirmSubmitIdea(){

            swal.fire({
                title: 'Are you sure?',
                text: "All project idea will be submitted for requirement analysis.",
                type: 'warning',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes,submit it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitIdea();
                    }
            })

        }

        function submitIdea(){

            projectCode = $('#projectCode').val();

            var formData = new FormData();

            formData.append('projectCode',projectCode);
            toggleLoader();

            $.ajax({
                url: "{{ route('project.idea.updateStatus') }}",
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

    <script>

        function confirmSubmitReq(){

            swal.fire({
                title: 'Are you sure?',
                text: "All requirement of idea will submitted for this project.",
                type: 'warning',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes,submit it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitAllRequirement();
                    }
            })

        }

        function submitAllRequirement(){

            projectCode = $('#projectCode').val();
            var formData = new FormData();

            formData.append('projectCode',projectCode);
            toggleLoader();

            $.ajax({
                url: "{{ route('project.idea.analysis.submitAllRequirement') }}",
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

    <script>

        function confirmSubmitScore(){

            swal.fire({
                title: 'Are you sure?',
                text: "All score of idea will submitted for this project.",
                type: 'warning',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes,submit it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitAllScore();
                    }
            })

        }

        function submitAllScore(){

            projectCode = $('#projectCode').val();
            var formData = new FormData();

            formData.append('projectCode',projectCode);
            toggleLoader();

            $.ajax({
                url: "{{ route('project.idea.scoring.submitAllScoring') }}",
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
                            text: "Project has been completely updated.",
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

    <script>

        function confirmSubmitRisk(){

            swal.fire({
                title: 'Are you sure?',
                text: "Risk analysis result will be submitted for this project.",
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

            projectCode = $('#projectCode').val();
            riskCode = $('#riskCode').val();

            var formData = new FormData();

            formData.append('projectCode',projectCode);
            formData.append('riskCode',riskCode);
            toggleLoader();

            $.ajax({
                url: "{{ route('risk.updateStatusRisk') }}",
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

    <script>

        "use strict";
        var KTCreateAccount = function() {
            var e, t, i, o, a, r, s = [];
            return {
                init: function() {
                    (e = document.querySelector("#kt_modal_create_account")) && new bootstrap.Modal(e), (t = document.querySelector("#kt_create_account_stepper")) && (i = t.querySelector("#kt_create_account_form"), o = t.querySelector('[data-kt-stepper-action="submit"]'), a = t.querySelector('[data-kt-stepper-action="next"]'), (r = new KTStepper(t)).on("kt.stepper.changed", (function(e) {
                        4 === r.getCurrentStepIndex() ? (o.classList.remove("d-none"), o.classList.add("d-inline-block"), a.classList.add("d-none")) : 5 === r.getCurrentStepIndex() ? (o.classList.add("d-none"), a.classList.add("d-none")) : (o.classList.remove("d-inline-block"), o.classList.remove("d-none"), a.classList.remove("d-none"))
                    })), r.on("kt.stepper.next", (function(e) {
                        console.log("stepper.next");
                        var t = s[e.getCurrentStepIndex() - 1];
                        t ? t.validate().then((function(t) {
                            console.log("validated!"), "Valid" == t ? (e.goNext(), KTUtil.scrollTop()) : Swal.fire({
                                text: "Sila lengkapkan maklumat borang.",
                                icon: "warning",
                                buttonsStyling: !1,
                                confirmButtonText: "Okay",
                                customClass: {
                                    confirmButton: "btn vksb-btn btn-cyan"
                                }
                            }).then((function() {
                                KTUtil.scrollTop()
                            }))
                        })) : (e.goNext(), KTUtil.scrollTop())
                    })), r.on("kt.stepper.previous", (function(e) {
                        console.log("stepper.previous"), e.goPrevious(), KTUtil.scrollTop()
                    })), s.push(FormValidation.formValidation(i, {
                        fields: {
                            accountType: {
                                validators: {
                                    notEmpty: {
                                        message: "Jenis Akaun diperlukan."
                                    }
                                }
                            },
                        },
                        plugins: {
                            trigger: new FormValidation.plugins.Trigger,
                            bootstrap: new FormValidation.plugins.Bootstrap5({
                                rowSelector: ".fv-row",
                                eleInvalidClass: "",
                                eleValidClass: ""
                            })
                        }
                    })), s.push(FormValidation.formValidation(i, {
                        fields: {
                            account_team_size: {
                                validators: {
                                    notEmpty: {
                                        message: "Time size is required"
                                    }
                                }
                            },
                            account_name: {
                                validators: {
                                    notEmpty: {
                                        message: "Account name is required"
                                    }
                                }
                            },
                            account_plan: {
                                validators: {
                                    notEmpty: {
                                        message: "Account plan is required"
                                    }
                                }
                            }
                        },
                        plugins: {
                            trigger: new FormValidation.plugins.Trigger,
                            bootstrap: new FormValidation.plugins.Bootstrap5({
                                rowSelector: ".fv-row",
                                eleInvalidClass: "",
                                eleValidClass: ""
                            })
                        }
                    })), s.push(FormValidation.formValidation(i, {
                        fields: {
                            business_name: {
                                validators: {
                                    notEmpty: {
                                        message: "Busines name is required"
                                    }
                                }
                            },
                            business_descriptor: {
                                validators: {
                                    notEmpty: {
                                        message: "Busines descriptor is required"
                                    }
                                }
                            },
                            business_type: {
                                validators: {
                                    notEmpty: {
                                        message: "Busines type is required"
                                    }
                                }
                            },
                            business_email: {
                                validators: {
                                    notEmpty: {
                                        message: "Busines email is required"
                                    },
                                    emailAddress: {
                                        message: "The value is not a valid email address"
                                    }
                                }
                            }
                        },
                        plugins: {
                            trigger: new FormValidation.plugins.Trigger,
                            bootstrap: new FormValidation.plugins.Bootstrap5({
                                rowSelector: ".fv-row",
                                eleInvalidClass: "",
                                eleValidClass: ""
                            })
                        }
                    })), s.push(FormValidation.formValidation(i, {
                        fields: {
                            card_name: {
                                validators: {
                                    notEmpty: {
                                        message: "Name on card is required"
                                    }
                                }
                            },
                            card_number: {
                                validators: {
                                    notEmpty: {
                                        message: "Card member is required"
                                    },
                                    creditCard: {
                                        message: "Card number is not valid"
                                    }
                                }
                            },
                            card_expiry_month: {
                                validators: {
                                    notEmpty: {
                                        message: "Month is required"
                                    }
                                }
                            },
                            card_expiry_year: {
                                validators: {
                                    notEmpty: {
                                        message: "Year is required"
                                    }
                                }
                            },
                            card_cvv: {
                                validators: {
                                    notEmpty: {
                                        message: "CVV is required"
                                    },
                                    digits: {
                                        message: "CVV must contain only digits"
                                    },
                                    stringLength: {
                                        min: 3,
                                        max: 4,
                                        message: "CVV must contain 3 to 4 digits only"
                                    }
                                }
                            }
                        },
                        plugins: {
                            trigger: new FormValidation.plugins.Trigger,
                            bootstrap: new FormValidation.plugins.Bootstrap5({
                                rowSelector: ".fv-row",
                                eleInvalidClass: "",
                                eleValidClass: ""
                            })
                        }
                    })), o.addEventListener("click", (function(e) {
                        s[3].validate().then((function(t) {
                            console.log("validated!"), "Valid" == t ? (e.preventDefault(), o.disabled = !0, o.setAttribute("data-kt-indicator", "on"), setTimeout((function() {
                                o.removeAttribute("data-kt-indicator"), o.disabled = !1, r.goNext()
                            }), 2e3)) : Swal.fire({
                                text: "Sorry, looks like there are some errors detected, please try again.",
                                icon: "error",
                                buttonsStyling: !1,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-light"
                                }
                            }).then((function() {
                                KTUtil.scrollTop()
                            }))
                        }))
                    })), $(i.querySelector('[name="card_expiry_month"]')).on("change", (function() {
                        s[3].revalidateField("card_expiry_month")
                    })), $(i.querySelector('[name="card_expiry_year"]')).on("change", (function() {
                        s[3].revalidateField("card_expiry_year")
                    })), $(i.querySelector('[name="business_type"]')).on("change", (function() {
                        s[2].revalidateField("business_type")
                    })))
                }
            }
        }();
        KTUtil.onDOMContentLoaded((function() {
            KTCreateAccount.init()
        }));

    </script>

@endpush