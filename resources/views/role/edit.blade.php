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
                        <a href="{{ route('role.index') }}" class="text-dark">
                            <i class="fas fa-chevron-left fs-2 text-dark"></i><b class="ps-3">Back</b>
                        </a>
                    </div>
                </div>

                <div class="row mb-5 d-flex flex-center">

                    <div class="col-md-8 card bg-content card-no-border p-5">

                        <div class="row flex-row mb-4 text-center">
                            <div class="col">
                                <h3 class="text-light">Edit Role</h3>
                            </div>
                        </div>

                        <form class="ajax-form" method="POST" action="{{ route('role.update',[$role->RLCode]) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row p-5">
                                <div class="col-md-12">
                                    <div class="row mb-8">
                                        <div class="col-md-12 mb-4">
                                            <div class="form-group row">
                                                <label for="code" class="col-sm-2 col-form-label text-light text-bold">Role Code :</label>
                                                <div class="col-sm-10">
                                                    <input readonly value="{{ $role->RLCode }}" type="text" name="code" id="code" class="form-control" placeholder="Enter role code">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group row">
                                                <label for="name" class="col-sm-2 col-form-label text-light text-bold">Role Name :</label>
                                                <div class="col-sm-10">
                                                    <input value="{{ $role->RLName }}" type="text" name="name" id="name" class="form-control" placeholder="Enter role name">
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group row">
                                                <label for="name" class="col-sm-2 col-form-label text-light text-bold">Status :</label>
                                                <div class="col-sm-10">
                                                    {!! Form::select('status', $statusActive , $role->RLActive, [
                                                        'id' => 'status',
                                                        'class' => 'form-select form-control',
                                                        'placeholder' => 'Choose Status',
                                                    ]) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-12">

                                            <table class="table bg-transparent text-light">

                                                <thead class="border-bottom">
                                                    <th class="text-light fs-1">Menu</th>
                                                    <th class="text-light fs-1 text-center">Permission</th>
                                                </thead>

                                                <tbody>
                                                    @foreach($permissions as $index => $permission)

                                                    <tr>
                                                        <td class="text-bold">{{ $index }}</td>
                                                        <td>
                                                            <div class="row">
                                                                @foreach( $permission as $perm )

                                                                <div class="col-md-6 mb-4">
                                                                    
                                                                    <div class="form-check form-check-custom form-check-solid">
                                                                        <input @if($rolePermission->contains($perm->PMCode)) checked @endif class="form-check-input form-check-solid" name="permissionCode[]" type="checkbox" value="{{ $perm->PMCode }}" id="permissionCode" />
                                                                        <label class="form-check-label text-light" for="permissionCode">
                                                                        {{ $perm->PMName }}
                                                                        </label>
                                                                    </div>
                                                                </div>

                                                                @endforeach
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    @endforeach
                                                </tbody>

                                            </table>
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row flex-row mt-5">
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-sm btn-primary w-25">Save</button>
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
