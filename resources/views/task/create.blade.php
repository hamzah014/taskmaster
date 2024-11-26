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
                            <h3>Create Project Task</h3>
                        </div>
                    </div>

                    <div class="row card flex-row p-5">
                        <div class="col-md-12 col-sm-12">
                            <div class="w-100 mt-5">
                                <form id="daftarForm" class="ajax-form" method="POST" action="{{ route('task.add') }}" enctype="multipart/form-data">
                                    @csrf

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
                                        {!! Form::select('project', $project , null, [
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
                                                <span class="input-group-text cursor-pointer btn btn-info" id="basic-addon2" data-bs-toggle="modal" data-bs-target="#modal-search"><i class="fa-solid fa-magnifying-glass"></i></span>
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

@endpush
