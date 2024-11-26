@extends('layouts.app')

@push('css')
    <style>

        .timeline-label:before {
            content: "";
            position: absolute;
            left: 10%;
            width: 0;
            top: 0;
            bottom: 0;
            background-color: var(--bs-gray-200);
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
                            <h2>Task Information</h2>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header card-header-stretch min-height-none">
                            <div class="card-toolbar m-0">
                                <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a id="info_tab" class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab" role="tab" href="#info_content">
                                            <span class="badge badge-dark me-2">1</span> Task Detail
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a id="task_tab" class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab" role="tab" href="#task_content">
                                            <span class="badge badge-dark me-2">2</span> Task Progress
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div id="info_content" class="card-body p-0 tab-pane fade show active" role="tabpanel" aria-labelledby="info_content">
                                    <div class="w-100">

                                        <form id="taskForm" class="ajax-form" method="POST" enctype="multipart/form-data">
                                            @csrf

                                            <input type="hidden" name="taskCode" id="taskCode" value="{{ $taskProject->TPCode }}">

                                            <h4 class="">Details Project Task</h4>
                                            <h5>Task information:</h5>

                                            <div class="fv-row mt-10">
                                                <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                    <span class="required">Choose Project</span>
                                                    <span class="ms-1" data-bs-toggle="tooltip" title="Select project for your task">
                                                        <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                            <span class="path3"></span>
                                                        </i>
                                                    </span>
                                                </label>
                                                {!! Form::select('project', $project , $taskProject->TP_PJCode, [
                                                    'id' => 'project',
                                                    'class' => 'form-select form-control',
                                                    'placeholder' => 'Select project',
                                                ]) !!}
                                            </div>
                                            <div class="fv-row row mb-4">
                                                <div class="col-md-12">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold my-4">
                                                        <span class="required">Task Name</span>
                                                        <span class="ms-1" data-bs-toggle="tooltip" title="Enter task name">
                                                            <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                            </i>
                                                        </span>
                                                    </label>
                                                    <input type="text" class="form-control" id="name" name="name" placeholder="Task name" value="{{ $taskProject->TPName }}">
                                                </div>
                                            </div>

                                            <div class="fv-row row mb-4">

                                                @if( in_array($taskProject->TPStatus, ['PENDING']) )

                                                <div class="col-md-6">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold my-4">
                                                        <span class="required">Search Assignee</span>
                                                        <span class="ms-1" data-bs-toggle="tooltip" title="Search assignee for the task">
                                                            <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                            </i>
                                                        </span>
                                                    </label>
                                                    <div class="input-group mb-5">
                                                        <input type="text" class="form-control" disabled placeholder="Find user by email" aria-label="Find user by email" aria-describedby="basic-addon2"/>
                                                        <span class="input-group-text cursor-pointer btn btn-info" id="basic-addon2" data-bs-toggle="modal" data-bs-target="#modal-search"><i class="fa-solid fa-magnifying-glass"></i></span>
                                                    </div>
                                                </div>
                                                @endif

                                                <div class="col-md-6">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold my-4">
                                                        <span class="required">Task Assignee</span>
                                                        <span class="ms-1" data-bs-toggle="tooltip" title="Select assignee for the task">
                                                            <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                            </i>
                                                        </span>
                                                    </label>
                                                    {!! Form::select('assignee', $users , $taskProject->TPAssignee, [
                                                        'id' => 'assignee',
                                                        'class' => 'form-select form-control',
                                                        'placeholder' => 'Select assignee',
                                                        'readonly'
                                                    ]) !!}
                                                </div>

                                            </div>

                                            <div class="fv-row">
                                                <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                    <span class="required">Description</span>
                                                    <span class="ms-1" data-bs-toggle="tooltip" title="Task description">
                                                        <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                            <span class="path3"></span>
                                                        </i>
                                                    </span>
                                                </label>
                                                <textarea id="description" name="description" class="form-control" data-kt-autosize="true" placeholder="Task description">{{ $taskProject->TPDesc }}</textarea>
                                            </div>
                                            <div class="fv-row row mb-4">
                                                <div class="col-md-6">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold my-4">
                                                        <span class="required">Level of Priority</span>
                                                        <span class="ms-1" data-bs-toggle="tooltip" title="Select level of project priority">
                                                            <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                            </i>
                                                        </span>
                                                    </label>
                                                    {!! Form::select('priority', $priorityLevel , $taskProject->TPPriority, [
                                                        'id' => 'priority',
                                                        'class' => 'form-select form-control',
                                                        'placeholder' => 'Choose priority',
                                                    ]) !!}
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="d-flex align-items-center fs-5 fw-semibold my-4">
                                                        <span class="required">Due Date</span>
                                                        <span class="ms-1" data-bs-toggle="tooltip" title="Enter task due date">
                                                            <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                                <span class="path3"></span>
                                                            </i>
                                                        </span>
                                                    </label>
                                                    <input type="date" class="form-control" id="dueDate" name="dueDate" placeholder="Due date" value="@if($taskProject->TPDueDate != null) {{ $taskProject->TPDueDate }} @endif">
                                                </div>
                                            </div>
                                            <div class="fv-row mt-10">
                                                <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                    <span class="required">Task Status</span>
                                                    <span class="ms-1" data-bs-toggle="tooltip" title="Select task status">
                                                        <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                            <span class="path3"></span>
                                                        </i>
                                                    </span>
                                                </label>
                                                {!! Form::select('taskStatus', $taskStatus , $taskProject->TPStatus, [
                                                    'id' => 'taskStatus',
                                                    'class' => 'form-select form-control',
                                                    'placeholder' => 'Select status',
                                                    'readonly'
                                                ]) !!}
                                            </div>

                                            <div class="fv-row mb-10 mt-5">
                                                <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                    <span class="">Upload File</span>
                                                    <span class="ms-1" data-bs-toggle="tooltip" title="Select file for reference">
                                                        <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                            <span class="path3"></span>
                                                        </i>
                                                    </span>
                                                </label>

                                                @if( in_array($taskProject->TPStatus, ['PENDING']) )
                                                    <input type="file" class="form-control" id="taskFile" name="taskFile" placeholder="Select task file">
                                                @endif

                                                @if($taskProject->fileAttach)
                                                    <a target="_blank" href="{{ route('file.view', $taskProject->fileAttach->FAGuidID ) }}" class="btn btn-info btn-sm">
                                                        View File
                                                    </a>
                                                @endif
                                            </div>

                                            @if( in_array($taskProject->TPStatus, ['PENDING']) )
                                                <div class="row mb-4 ">
                                                    <div class="col-md-12 text-end">
                                                        <div class="mt-7">
                                                            <a onclick="confirmUpdateTask(0)" class="btn btn-primary btn-sm text-nowrap">
                                                            Save as Draft
                                                            </a>
                                                            <a onclick="confirmUpdateTask(1)" class="btn btn-success btn-sm text-nowrap">
                                                            Submit
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if( in_array($taskProject->TPStatus, ['SUBMIT']) )
                                                <div class="row mb-4 ">
                                                    <div class="col-md-12 text-end">
                                                        <div class="mt-7">
                                                            <a onclick="confirmCompleteTask()" class="btn btn-success btn-sm text-nowrap">
                                                            Task Complete
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                        </form>

                                    </div>
                                </div>
                                <div id="task_content" class="card-body p-0 tab-pane fade" role="tabpanel" aria-labelledby="task_content">
                                    <div class="w-100">

                                        @if($taskProject->TPStatus == 'SUBMIT')

                                        <form id="taskUpdateForm" class="ajax-form" method="POST" enctype="multipart/form-data">
                                            @csrf

                                            <div class="fv-row text-center">
                                                <h4 class="">Let's update the issue for this task</h4>
                                            </div>

                                            <div class="fv-row mb-8 mt-5">
                                                <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                    <span class="required">Task Issue</span>
                                                </label>
                                                <textarea id="taskDesc" name="taskDesc" class="form-control" data-kt-autosize="true" placeholder="Task description"></textarea>
                                            </div>

                                            <div class="fv-row mb-8">
                                                <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                                    <span class="">Upload File</span>
                                                </label>
                                                <input type="file" class="form-control" id="taskFile" name="taskFile" placeholder="Select task file">
                                            </div>

                                            @if($taskProject->TPStatus == 'SUBMIT')
                                            <div class="fv-row text-end">
                                                <a onclick="confirmSubmitTask()" class="btn btn-success">Submit</a>
                                            </div>
                                            @endif

                                        </form>

                                        @endif

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($taskIssue && count($taskIssue) > 0)

                        <div class="card card-flush mt-10" id="kt_chat_messenger">
                            <div class="card-header" id="kt_chat_messenger_header">
                                <div class="card-title">
                                    <div class="d-flex justify-content-center flex-column me-3">
                                        <a href="#" class="fs-4 fw-bold text-gray-900 text-hover-primary me-1 mb-2 lh-1">Timeline</a>
                                        <div class="mb-0 lh-1">
                                            <span class="fs-7 fw-semibold text-muted">Task Log</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body" id="kt_chat_messenger_body">
                                <div class="scroll-y me-n5 pe-5 h-300px h-lg-auto" data-kt-element="messages" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_header, #kt_app_header, #kt_app_toolbar, #kt_toolbar, #kt_footer, #kt_app_footer, #kt_chat_messenger_header, #kt_chat_messenger_footer" data-kt-scroll-wrappers="#kt_content, #kt_app_content, #kt_chat_messenger_body" data-kt-scroll-offset="5px">

                                    @foreach($taskIssue as $index => $issue)

                                        @if($issue->TPI_isLead == 1)

                                            <div class="d-flex justify-content-start mb-10">
                                                <div class="d-flex flex-column align-items-start">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="ms-3">
                                                            <a href="#" class="fs-7 fw-bold text-gray-900 text-hover-primary me-1">{{ $issue->user->USName }}</a>
                                                            <span class="text-muted fs-7 mb-1">Commentor</span>
                                                        </div>
                                                    </div>
                                                    <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start" data-kt-element="message-text">
                                                        {{ $issue->TPIDesc }}
                                                        <br>
                                                        @if($issue->fileAttach)
                                                            <a target="_blank" href="{{ route('file.view', $issue->fileAttach->FAGuidID ) }}" class="text-underline cursor-pointer">View File</a>
                                                        @endif
                                                    </div>

                                                </div>
                                            </div>

                                        @else

                                            <div class="d-flex justify-content-end mb-10">
                                                <div class="d-flex flex-column align-items-end">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="me-3">
                                                            <span class="text-muted fs-7 mb-1">Assignee</span>
                                                            <a href="#" class="fs-7 fw-bold text-gray-900 text-hover-primary ms-1">{{ $issue->user->USName }}</a>
                                                        </div>
                                                    </div>
                                                    <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end" data-kt-element="message-text">
                                                        {{ $issue->TPIDesc }}
                                                        <br>
                                                        @if($issue->fileAttach)
                                                            <a target="_blank" href="{{ route('file.view', $issue->fileAttach->FAGuidID ) }}" class="text-underline cursor-pointer">View File</a>
                                                        @endif
                                                    </div>

                                                </div>
                                            </div>

                                        @endif

                                    @endforeach

                                </div>
                            </div>
                        </div>

                    @endif

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
                    <h3 class="modal-title">Search User</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-close fs-1"></i>
                    </div>
                </div>

                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="input-group mb-5">
                                <input id="searchEmail" type="text" class="form-control" placeholder="Search email" aria-label="Search email" aria-describedby="basic-addon2"/>
                                <span class="input-group-text cursor-pointer btn btn-primary" id="basic-addon2" onclick="searchUser()">Search</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4 d-none" id="resultSearch">
                        <div class="col-md-12">
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
        </div>
    </div>


@endpush

@push('script')

    <script>

        function searchUser(){

            $('#resultSearch').addClass('d-none');

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

                        $('#resultSearch').removeClass('d-none');

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

            $('#assignee').val(id);

            resetSearchUser();

        }

        function resetSearchUser(){

            $('#resultSearch').addClass('d-none');
            $('#searchEmail').val('');

            $('#resultID').val('');
            $('#resultName').val('');
            $('#resultEmail').val('');

        }


    </script>

    <script>

        function confirmUpdateTask(status){

            if(status == 1){

                swal.fire({
                    title: 'Are you sure?',
                    text: "Task will submitted to the assignee.",
                    type: 'warning',
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes,submit it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateTask(status);
                        }
                })

            }
            else{

                updateTask(status);

            }


        }

        function updateTask(status){

            taskCode = $('#taskCode').val();

            form = $('#taskForm');
            var formData = new FormData(form[0]);

            formData.append('taskCode',taskCode);
            formData.append('status',status);
            toggleLoader();

            $.ajax({
                url: "{{ route('task.update') }}",
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

        function confirmSubmitTask(){

            swal.fire({
                title: 'Are you sure?',
                text: "Task status will update to completed.",
                type: 'warning',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes,submit it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitTaskLead();
                    }
            })

        }

        function submitTaskLead(){

            taskCode = $('#taskCode').val();

            form = $('#taskUpdateForm');
            var formData = new FormData(form[0]);

            formData.append('taskCode',taskCode);
            toggleLoader();

            $.ajax({
                url: "{{ route('task.user.submitTaskLead') }}",
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

        function confirmCompleteTask(){

            swal.fire({
                title: 'Are you sure?',
                text: "Task status will update to completed.",
                type: 'warning',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes,submit it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        completeTask();
                    }
            })

        }

        function completeTask(){

            taskCode = $('#taskCode').val();

            form = $('#taskUpdateForm');
            var formData = new FormData(form[0]);

            formData.append('taskCode',taskCode);
            toggleLoader();

            $.ajax({
                url: "{{ route('task.user.completeTask') }}",
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
