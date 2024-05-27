@extends('layouts.app')

@push('css')
    <style>
    </style>
@endpush
@section('content')

<div id="kt_app_content_container" class="app-container d-flex justify-content-center align-items-center">
	<div class=" mb-5 mb-xl-10 w-100">
		<div id="kt_account_settings_profile_details">
			<div class="card-body p-9 card bg-section card-no-border">

                <div class="row mb-5">
                    <div class="col-md-12">
                        <a href="{{ route('management.user.index') }}" class="text-dark">
                            <i class="fas fa-chevron-left fs-2 text-dark"></i><b class="ps-3">Back</b>
                        </a>
                    </div>
                </div>

                <div class="row mb-5 d-flex flex-center">

                    <div class="col-md-8 card bg-content card-no-border p-5">

                        <div class="row flex-row mb-4 text-center">
                            <div class="col">
                                <h3 class="text-light">Add User</h3>
                            </div>
                        </div>

                        <form class="ajax-form" method="POST" action="{{ route('management.user.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row p-5">
                                <div class="col-md-12">
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="name" class="form-label text-light">Name :</label>
                                            <input value="" type="text" name="name" id="name" class="form-control" placeholder="Enter name">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="email" class="form-label text-light">Email :</label>
                                            <input value="" type="text" name="email" id="email" class="form-control" placeholder="Enter email">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="phoneNo" class="form-label text-light">Phone Number :</label>
                                            <input value="" type="text" name="phoneNo" id="phoneNo" class="form-control" placeholder="Enter phone number">
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label for="staffDepart" class="form-label text-light">Role :</label>
                                            {!! Form::select('role', $userRole , null, [
                                                'id' => 'role',
                                                'class' => 'form-select form-control',
                                                'placeholder' => 'Choose Role',
                                            ]) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row flex-row mt-5">
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                </div>
                            </div>
                        </form>

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
