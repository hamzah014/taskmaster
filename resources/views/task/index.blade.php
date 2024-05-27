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

                <div class="row flex-row mb-5">
                    <div class="col-md-9">
                        <h2>List of Task</h2>
                    </div>
                    <div class="col-md-3 text-end">
                        <a href="{{ route('task.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Create</a>
                    </div>
                </div>

                <div class="row flex-row mb-5">
                    <div class="col-md-12">
                        <table class="table table-bordered text-center border-dark" id="project-tab">
                            <thead class="text-center bg-gray">
                                <th class="text-center">No.</th>
                                <th class="text-center">Code</th>
                                <th class="text-center">Name</th>
                                <th class="text-center">Project Name</th>
                                <th class="text-center">Level Priority</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-center">Assignee</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </thead>
                        </table>
                    </div>
                </div>

			</div>
		</div>
	</div>
</div>

@endsection

@push('script')

    <script>

		(function ($) {

            var table = $('#project-tab').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:false,
                ajax:  {
                    "url" :"{{ route('task.taskDatatable') }}",
                    "method": 'POST',
                },
                order: [[1, 'desc']],
                columns: [
                    { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                    { name: 'TPCode', data: 'TPCode', class: 'text-center' },
                    { name: 'TPName', data: 'TPName', class: 'text-center' },
                    { name: 'PJName', data: 'PJName', class: 'text-center' },
                    { name: 'TPPriority', data: 'TPPriority', class: 'text-center' },
                    { name: 'TPDueDate', data: 'TPDueDate', class: 'text-center' },
                    { name: 'TPAssignee', data: 'TPAssignee', class: 'text-center' },
                    { name: 'TPStatus', data: 'TPStatus', class: 'text-center' },
                    { name: 'action', data: 'action', class: 'text-center' },

                ]
            });
            table.buttons().container().appendTo('.button-table-export');

        })(jQuery);
    </script>

@endpush
