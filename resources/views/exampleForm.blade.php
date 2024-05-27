@extends('layouts.app')

@push('css')
    <style>
    </style>
@endpush
@section('content')


<div id="kt_app_content_container" class="app-container d-flex justify-content-center align-items-center">
	<div class="card mb-5 mb-xl-10 bg-content-card card-no-border bg-cert w-100">
		<div id="kt_account_settings_profile_details">
			<div class="card-body p-9">

                <div class="row mb-5">
                    <div class="col-md-12">
                        <a href="{{ route('management.user.index') }}" class="text-dark">
                            <i class="fas fa-chevron-left fs-2 text-dark"></i><b class="ps-3">Back</b>
                        </a>
                    </div>
                </div>

                <div class="row flex-row mt-10 card p-5 bg-fancy card-no-border">

                    <div class="col-md-12 ">

                        <div class="row">
                            <div class="col-md-12">
                                <h2>Tajuk</h2>
                            </div>
                        </div>
                        <hr>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="">Label 1</label>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="input1" id="input1" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="">Label 1</label>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="input1" id="input1" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-12 text-end">
                                <label for="">Label 1</label>
                            </div>
                        </div>

                    </div>

                </div>
				
			</div>
		</div>
	</div>
</div>

@endsection
@push('script')
    <script>

    </script>
@endpush
