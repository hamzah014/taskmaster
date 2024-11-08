
<h4 class="">Details Project Task</h4>
<h5>Task information:</h5>

<input type="hidden" name="parentTask" id="parentTask" value="{{ $parentTask }}">
<input type="hidden" name="taskType" id="taskType" value="{{ $taskParent->TPType }}">

<div class="fv-row row mt-10">
    <div class="col-md-6">
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
        {!! Form::select('project', $project , $projectSub, [
            'id' => 'projectSub',
            'class' => 'form-select form-control',
            'placeholder' => 'Select project',
            'readonly'
        ]) !!}
    </div>
    <div class="col-md-6">
        <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
            <span class="required">Parent Task</span>
        </label>
        <input type="text" class="form-control" readonly id="parentDetail" name="parentDetail" placeholder="Parent Task" value="{{ $parentDetail }}">
    </div>
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
            <span class="input-group-text cursor-pointer btn btn-info" id="basic-addon2" onclick="viewModal('modal-subSearch')"><i class="fa-solid fa-magnifying-glass"></i></span>
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
            'id' => 'assigneeSub',
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

