@extends('layouts.app')

@push('css')
    <style>
    </style>
@endpush
@section('content')


<div id="kt_app_content_container" class="app-container d-flex justify-content-center align-items-center">
	<div class="card mb-5 mb-xl-10 w-100 mt-5">
		<div id="kt_account_settings_profile_details">
			<div class="card-body p-9">

                <div class="row mb-5">
                    <div class="col-md-12 text-start">
                        <a class="btn btn-secondary btn-sm" href="{{ route('task.index',$type) }}"><i class="fa fa-chevron-left"></i> Back</a>
                    </div>
                </div>

                <div class="row flex-row mb-5">
                    <div class="col-md-8">
                        <h2>List of {{ $typeName }} (<a class="text-hover" href="{{ route('project.edit', $projectThis->PJCode) }}" target="_blank">{{ $projectThis->PJName }}</a>)</h2>
                    </div>
                    @if ($leader == 1)
                    <div class="col-md-4 text-end">
                        @if ($projectThis->PJStatus == 'PROGRESS-' . $type)
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create"><i class="fa-plus fa-solid"></i>Create</button>
                            <button type="button" class="btn btn-sm btn-success" onclick="proceedTask('{{ $type }}')"><i class="fa-arrow-right fa-solid"></i>Proceed Next</button>
                        @endif
                    </div>
                    @endif
                </div>


                <div class="row g-5 g-xl-10 mb-5 mb-xl-10">

                    @foreach ($taskStatusType as $index => $taskStatusTaip)

                        @if(isset($tasks[$index]))
                        <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 card p-5">
                            <h4 class="text-underline"><center>{{ $taskStatusTaip }}</center></h4>
                            <div class="row">

                                @foreach ( $tasks[$index] as $index => $taskproject)

                                    <div class="col-12">

                                        <div class="card card-flush h-xl-100 cursor-pointer" onclick="viewTask('{{ $taskproject->TPCode }}')">
                                            <div class="card-body text-center pb-5">
                                                <div class="d-flex align-items-end flex-stack mb-1">
                                                    <div class="text-start">
                                                        <span class="fw-bold text-gray-800 fs-4 d-block">{{ $taskproject->TPName }}</span>
                                                        <span class="text-gray-500 mt-1 fw-bold fs-6">

                                                            @if ( in_array($taskproject->TPStatus, ['PENDING','FIP']))

                                                                <span class="badge badge-secondary">{{ $taskproject->TPStatusName }}</span>

                                                            @elseif (in_array($taskproject->TPStatus, ['PROGRESS','ACP']))

                                                                <span class="badge badge-light-primary">{{ $taskproject->TPStatusName }}</span>

                                                            @elseif (in_array($taskproject->TPStatus, ['SUBMIT','DEP']))

                                                                <span class="badge badge-light-info">{{ $taskproject->TPStatusName }}</span>

                                                            @elseif (in_array($taskproject->TPStatus, ['COMPLETE','MAN']))

                                                                <span class="badge badge-light-success">{{ $taskproject->TPStatusName }}</span>

                                                            @endif

                                                        </span>
                                                    </div>

                                                    @if($taskproject->parentTask)
                                                    <span class="text-light-600 text-end fw-bold fs-7 btn btn-outline btn-outline-info btn-active-light-info btn-sm" onclick="event.stopPropagation(); viewTask('{{ $taskproject->parentTask->TPCode }}')">{{ $taskproject->parentTask->TPCode }}</span>
                                                    @endif

                                                </div>
                                            </div>
                                            <div class="card-footer d-flex flex-stack pt-0">
                                                <span class=" btn btn-outline btn-outline-primary btn-active-light-primary btn-sm flex-shrink-0 me-2 fs-5">{{ $taskproject->TPCode }}</span>

                                                @if($taskproject->assignee)
                                                    <span class="symbol symbol-35px symbol-circle bg-primary" data-bs-toggle="tooltip" title="{{ $taskproject->assignee->USName }}">
                                                        <img alt="Pic" src="{{ $taskproject->assignee->getProfileURL() }}" />
                                                    </span>
                                                @else
                                                    <span class="symbol symbol-35px symbol-circle bg-primary p-2 text-white" data-bs-toggle="tooltip" title="Not set">
                                                        N.S
                                                    </span>
                                                @endif

                                            </div>
                                        </div>

                                    </div>

                                @endforeach

                            </div>
                        </div>

                        @endif

                    @endforeach
                </div>


			</div>
		</div>


	</div>
</div>

@endsection


@push('modals')

    <div class="modal fade" id="modal-task" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered justify-content-center">
            <div class="modal-content w-80">
                <div class="modal-header">
                    <h3 class="modal-title">Task Information</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-close fs-1"></i>
                    </div>
                </div>

                <div class="modal-body">
                    <div id="detailHere"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-subtask" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered justify-content-center">
            <div class="modal-content w-80">
                <div class="modal-header">
                    <h3 class="modal-title">Create Sub-Task</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-close fs-1"></i>
                    </div>
                </div>

                <div class="modal-body">
                    <div class="row card flex-row p-5">
                        <div class="col-md-12 col-sm-12">
                            <div class="w-100 mt-5">
                                <form id="subtaskForm" class="ajax-form" method="POST" action="{{ route('task.add') }}" enctype="multipart/form-data">
                                    @csrf

                                    <div id="subTaskDetail"></div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-search" tabindex="-1" aria-hidden="true">
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

    <div class="modal fade" id="modal-subSearch" tabindex="-1" aria-hidden="true">
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
                                <input id="searchSubEmail" type="text" class="form-control" placeholder="Search email" aria-label="Search email" aria-describedby="basic-addon2"/>
                                <span class="input-group-text cursor-pointer btn btn-primary" id="basic-addon2" onclick="searchSubUser()">Search</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4 d-none" id="resultSubSearch">
                        <div class="col-md-12">
                            <input type="hidden" name="resultSubID" id="resultSubID" value="0">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input readonly type="text" class="form-control" name="resultSubName" id="resultSubName" placeholder="Result name"/>
                                        <label for="resultSubName">Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input readonly type="text" class="form-control" name="resultSubEmail" id="resultSubEmail" placeholder="Result email"/>
                                        <label for="resultSubEmail">Email</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12 text-end">
                                    <a class="btn btn-info btn-sm" onclick="selectSubUser()">Select User</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-create" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered justify-content-center">
            <div class="modal-content w-80">
                <div class="modal-header">
                    <h3 class="modal-title">Create Task</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-close fs-1"></i>
                    </div>
                </div>

                <div class="modal-body">

                    <div class="row card flex-row p-5">
                        <div class="col-md-12 col-sm-12">
                            <div class="w-100 mt-5">
                                <form id="daftarForm" class="ajax-form" method="POST" action="{{ route('task.add') }}" enctype="multipart/form-data">
                                    @csrf

                                    <h4 class="">Details Project Task</h4>
                                    <h5>Task information:</h5>
                                    <input type="hidden" name="taskType" id="taskType" value="{{ $type }}">

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
                                        {!! Form::select('project', $projects , $projectCode, [
                                            'id' => 'project',
                                            'class' => 'form-select form-control',
                                            'placeholder' => 'Select project',
                                            'readonly'
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
                                            <input type="text" class="form-control" id="name" name="name" placeholder="Task name"/>
                                        </div>
                                    </div>
                                    <div class="fv-row row mb-4">
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
                                                <span class="input-group-text cursor-pointer btn btn-info" id="basic-addon2" onclick="viewModal('modal-newSearch')"><i class="fa-solid fa-magnifying-glass"></i></span>
                                            </div>
                                        </div>
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
                                            {!! Form::select('assignee', $users , null, [
                                                'id' => 'assigneeNew',
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
                                        <textarea id="description" name="description" class="form-control" data-kt-autosize="true" placeholder="Task description"></textarea>
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
                                            {!! Form::select('priority', $priorityLevel , null, [
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
                                            <input type="date" class="form-control" id="dueDate" name="dueDate" placeholder="Due date"/>
                                        </div>
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
                                        <input type="file" class="form-control" id="taskFile" name="taskFile" placeholder="Select task file">
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-12 text-end">
                                            <div class="mt-7">
                                                <button type="submit" class="btn btn-primary text-nowrap">
                                                Submit
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-newSearch" tabindex="-1" aria-hidden="true">
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
                                <input id="searchNewEmail" type="text" class="form-control" placeholder="Search email" aria-label="Search email" aria-describedby="basic-addon2"/>
                                <span class="input-group-text cursor-pointer btn btn-primary" id="basic-addon2" onclick="searchNewUser()">Search</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4 d-none" id="resultNewSearch">
                        <div class="col-md-12">
                            <input type="hidden" name="resultNewID" id="resultNewID" value="0">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input readonly type="text" class="form-control" name="resultNewName" id="resultNewName" placeholder="Result name"/>
                                        <label for="resultNewName">Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input readonly type="text" class="form-control" name="resultNewEmail" id="resultNewEmail" placeholder="Result email"/>
                                        <label for="resultNewEmail">Email</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12 text-end">
                                    <a class="btn btn-info btn-sm" onclick="selectNewUser()">Select User</a>
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

        function viewTask(id){

            formData = new FormData();

            formData.append('id',id);
            toggleLoader();

            $.ajax({
                url: "{{ route('task.view.detail') }}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: false,
                data: formData,
                processData: false,
                cache: false,
                success: function (resp) {
                    // console.log(resp);
                    toggleLoader();
                    $('#detailHere').html(resp);
                    $('#modal-task').modal('show');


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

        function createSubtask(taskcode, taskName, projectCode){

            parentDetail = taskcode + " - " + taskName;
            parentTask = taskcode;
            projectSub = projectCode;

            formData = new FormData();

            formData.append('parentDetail',parentDetail);
            formData.append('parentTask',parentTask);
            formData.append('projectSub',projectSub);

            toggleLoader();

            $.ajax({
                url: "{{ route('task.view.subTaskForm') }}",
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
                    $('#subTaskDetail').html(resp);
                    $('#modal-task').modal('hide');
                    $('#modal-subtask').modal('show');


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

        function searchSubUser(){

            $('#resultSubSearch').addClass('d-none');

            email = $('#searchSubEmail').val();

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

                        $('#resultSubID').val(resp.id);
                        $('#resultSubName').val(resp.name);
                        $('#resultSubEmail').val(resp.email);

                        $('#resultSubSearch').removeClass('d-none');

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

        function selectSubUser(){

            $('#modal-subSearch').modal('hide');

            id = $('#resultSubID').val();
            name = $('#resultSubName').val();
            email = $('#resultSubEmail').val();

            $('#assigneeSub').val(id);

            resetSubSearchUser();

        }

        function resetSubSearchUser(){

            $('#resultSubSearch').addClass('d-none');
            $('#searchSubEmail').val('');

            $('#resultSubID').val('');
            $('#resultSubName').val('');
            $('#resultSubEmail').val('');

        }


    </script>

    <script>

        function searchNewUser(){

            $('#resultNewSearch').addClass('d-none');

            email = $('#searchNewEmail').val();

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

                        $('#resultNewID').val(resp.id);
                        $('#resultNewName').val(resp.name);
                        $('#resultNewEmail').val(resp.email);

                        $('#resultNewSearch').removeClass('d-none');

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

        function selectNewUser(){

            $('#modal-newSearch').modal('hide');

            id = $('#resultNewID').val();
            name = $('#resultNewName').val();
            email = $('#resultNewEmail').val();

            $('#assigneeNew').val(id);

            resetNewSearchUser();

        }

        function resetNewSearchUser(){

            $('#resultNewSearch').addClass('d-none');
            $('#searchNewEmail').val('');

            $('#resultNewID').val('');
            $('#resultNewName').val('');
            $('#resultNewEmail').val('');

        }


    </script>

    <script>

        function proceedTask(type){

            projectCode = '{{ $projectCode }}';

            swal.fire({
                title: 'Are you sure?',
                text: "Your current task will be submitted for the phase.",
                type: 'warning',
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        completeTask(projectCode, type);
                    }
            })

        }

        function completeTask(projectCode, taskType){

            formData = new FormData();

            formData.append('projectCode', projectCode);
            formData.append('taskType', taskType);
            toggleLoader();

            $.ajax({
                url: "{{ route('task.submit.complete') }}",
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
