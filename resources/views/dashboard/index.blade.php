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
                    <div class="col-md-12">
                        <h2>Dashboard</h2>
                    </div>
                </div>

                <div class="row flex-row mb-5">
                    <div class="col-md-12">
                        <table class="table table-bordered border-none">

                            <tr>
                                <td class="text-center w-15 bg-gray">Project</td>
                                <td class="bg-primary">Project 1</td>
                                <td class="bg-primary">Project 2</td>
                                <td class="bg-primary">Project 3</td>
                                <td class="bg-primary">Project 4</td>
                            </tr>

                            <tr>
                                <td class="text-center w-15 bg-gray">Progress</td>
                                <td>1</td>
                                <td>2</td>
                                <td>3</td>
                                <td>4</td>
                            </tr>

                            <tr>
                                <td class="text-center w-15 bg-gray">Risks & Issue</td>
                                <td>1</td>
                                <td>2</td>
                                <td>3</td>
                                <td>4</td>
                            </tr>

                        </table>
                    </div>
                </div>
				
			</div>
		</div>
	</div>
</div>

@endsection

@push('script')
@endpush
