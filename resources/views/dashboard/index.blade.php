@extends('layouts.app')

@push('css')
    <style>
    </style>
@endpush
@section('content')


<div id="kt_app_content_container" class="app-container">
	<div class="card mb-5 mb-xl-10 w-100 mt-5">
		<div id="kt_account_settings_profile_details">
			<div class="card-body p-9">

                <div class="row flex-row mb-5">
                    <div class="col-md-10">
                        <h2>Dashboard</h2>
                    </div>
                    <div class="col-md-2 text-end">
                        <a href="{{ route('project.create') }}" class="btn text-light btn-orange btn-round">New Project</a>
                    </div>
                </div>


                <div class="row gy-5 g-xl-10 mb-10">

                    <div class="col-sm-6 col-xl-4">
                        <div class="card border-dark">
                            <div class="card-body d-flex justify-content-center align-items-center flex-column">
                                <div class="d-flex flex-column text-center">
                                    <div class="m-0">
                                        <i class="fa fa-solid fa-rocket fs-2 text-primary"></i>
                                        <span class="fw-bold fs-6 text-dark px-2">My Project</span>
                                    </div>
                                    <div class="m-0 text-center">
                                        <span class="fw-bold fs-4x text-gray-800" data-kt-countup="true" data-kt-countup-value="{{ $projectCount }}">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-4">
                        <div class="card border-dark">
                            <div class="card-body d-flex justify-content-center align-items-center flex-column">
                                <div class="d-flex flex-column text-center">
                                    <div class="m-0">
                                        <i class="fa-solid fa-lightbulb fs-2 text-warning"></i>
                                        <span class="fw-bold fs-6 text-dark px-2">My Ideas</span>
                                    </div>
                                    <div class="m-0">
                                        <span class="fw-bold fs-4x text-gray-800" data-kt-countup="true" data-kt-countup-value="{{ $ideaCount }}">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-4">
                        <div class="card border-dark">
                            <div class="card-body d-flex justify-content-center align-items-center flex-column">
                                <div class="d-flex flex-column text-center">
                                    <div class="m-0">
                                        <i class="fa-solid fa-list-check fs-2 text-info"></i>
                                        <span class="fw-bold fs-6 text-dark px-2">My Task</span>
                                    </div>
                                    <div class="m-0 text-center">
                                        <span class="fw-bold fs-4x text-gray-800" data-kt-countup="true" data-kt-countup-value="{{ $taskCount }}">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

			</div>
		</div>
	</div>

    <div class="card mb-5 mb-xl-10 w-100 mt-5">
		<div id="kt_account_settings_profile_details">
			<div class="card-body p-9">

                <div class="row flex-row mb-5">
                    <div class="col-md-12">
                        <h2>Project</h2>
                    </div>
                </div>

                <div class="row gy-5 g-xl-10 mb-10 d-flex justify-content-center align-items-center">

                    @foreach ( $projects as $index => $project )

                    <div class="col-sm-6 col-xl-6">

                        <div class="card card-xl-stretch mb-xl-8">
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-900">{{ $project->PJName }}</span>
                                    <span class="text-muted mt-1 fw-semibold fs-7">{{ $project->PJCode }}</span>
                                </h3>
                                <div class="card-toolbar">
                                    <a href="{{ route('project.edit',$project->PJCode) }}"><i class="fa-solid fa-rocket fs-2 text-dark text-hover-primary"></i></a>
                                </div>
                            </div>
                            <div class="card-body pt-5">

                                <div class="d-flex align-items-center bg-light-primary rounded p-5 mb-2">
                                    <i class="fa-solid fa-signal text-primary fs-1 me-5"></i>
                                    <div class="flex-grow-1 me-2">
                                        <a class="fw-bold text-gray-800 text-hover-primary fs-6">Current Status</a>
                                    </div>
                                    <span class="fw-bold text-primary py-1">

                                        @if(isset($projectStatus[$project->PJStatus]))
                                            @if($project->PJStatus == "COMPLETE")
                                                <span class="badge badge-success fs-5">Completed</span>

                                            @elseif (in_array($project->PJStatus, ['CANCEL','CLOSED']))
                                                <span class="badge badge-danger fs-5">{{ $projectStatus[$project->PJStatus] }}</span>

                                            @else
                                                <span class="badge badge-warning fs-5">{{ $projectStatus[$project->PJStatus] }}</span>
                                                
                                            @endif
                                        @else
                                            <span class="badge badge-warning fs-5">Pending</span>
                                        @endif

                                    </span>
                                </div>

                                <div class="d-flex align-items-center bg-light-warning rounded p-5 mb-2">
                                    <i class="fa fa-solid fa-lightbulb fs-1 me-5 text-warning"></i>
                                    <div class="flex-grow-1 me-2">
                                        <a class="fw-bold text-gray-800 text-hover-warning fs-6">Ideas</a>
                                    </div>
                                    <span class="fw-bold text-warning py-1">{{ count($project->projectIdea) }}</span>
                                </div>

                                <div class="d-flex align-items-center bg-light-info rounded p-5 mb-2">
                                    <i class="fa fa-solid fa-object-group fs-1 me-5 text-info"></i>
                                    <div class="flex-grow-1 me-2">
                                        <a class="fw-bold text-gray-800 text-hover-info fs-6">Project Design</a>
                                    </div>
                                    <span class="fw-bold text-info py-1">{{ count($project->taskProjectPD) }}</span>
                                </div>

                                <div class="d-flex align-items-center bg-light-primary rounded p-5 mb-2">
                                    <i class="fa fa-solid fa-tree-city fs-1 me-5 text-primary"></i>
                                    <div class="flex-grow-1 me-2">
                                        <a class="fw-bold text-gray-800 text-hover-primary fs-6">Further Development</a>
                                    </div>
                                    <span class="fw-bold text-primary py-1">{{ count($project->taskProjectFD) }}</span>
                                </div>

                                <div class="d-flex align-items-center bg-light-secondary rounded p-5 mb-2">
                                    <i class="fa fa-solid fa-book fs-1 me-5 text-dark"></i>
                                    <div class="flex-grow-1 me-2">
                                        <a class="fw-bold text-gray-800 text-hover-dark fs-6">Project Closure</a>
                                    </div>
                                    <span class="fw-bold text-dark py-1">{{ count($project->taskProjectPC) }}</span>
                                </div>

                                <div class="d-flex align-items-center flex-column mt-8 w-100">
                                    <div class="d-flex justify-content-between fw-bold fs-6 text-dark opacity-50 w-100 mt-auto mb-2">
                                        <span>Status Progress</span>
                                        <span>{{ $project->projectPercent() }}%</span>
                                    </div>
                                    <div class="h-8px mx-3 w-100 bg-light-danger rounded">
                                        <div class="bg-danger rounded h-8px" role="progressbar" style="width: {{ $project->projectPercent() }}%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                    @endforeach

                </div>

			</div>
		</div>
	</div>
</div>

@endsection

@push('script')

    <script src="{{ asset('assets/plugins/custom/draggable/draggable.bundle.js') }}"></script>

    <script>

        $(document).ready(function(){

            var containers = document.querySelectorAll(".draggable-zone");

            if (containers.length === 0) {
                return false;
            }

            var swappable = new Sortable.default(containers, {
                draggable: ".draggable",
                handle: ".draggable .draggable-handle",
                mirror: {
                    //appendTo: selector,
                    appendTo: "body",
                    constrainDimensions: true
                }
            });

        })

    </script>

@endpush
