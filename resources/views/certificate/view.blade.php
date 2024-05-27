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
                <div class="row flex-row card p-5 bg-fancy card-no-border">
                    <div class="row mb-5">
                        <div class="col-md-12">
                            <a href="{{ route('certificate.index') }}" class="text-dark">
                                <i class="fas fa-chevron-left fs-2 text-dark"></i><b class="ps-3">Back</b>
                            </a>
                        </div>
                    </div>
                    <div class="row d=flex flex-center">
                        <div class="col-md-8 ">

                            <div class="row">
                                <div class="col-md-12">
                                    <h2 class=" text-light">Certificate Details</h2>
                                </div>
                            </div>
                            <hr style="color: white;">
                            
                            <div class="row mb-9">
                                <div class="col-md-3 text-light text-bold">
                                    <label for="">Identification/Register No.</label>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" id="icno" class="form-control" autocomplete="off" readonly value="{{ $certificate->CEIDNo ?? '-' }}">
                                </div>
                            </div>
                            <div class="row mb-9">
                                <div class="col-md-3 text-light text-bold">
                                    <label for="">Subscriber Name</label>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" id="name" class="form-control" autocomplete="off" readonly value="{{ $certificate->CEName ?? '-' }}">
                                </div>
                            </div>
                            <div class="row mb-9">
                                <div class="col-md-3 text-light text-bold">
                                    <label for="">Certificate No.</label>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" id="certno" class="form-control" autocomplete="off" readonly value="{{ $certificate->CENo ?? '-' }}">                            
                                </div>
                            </div>
                            <div class="row mb-9">
                                <div class="col-md-3 text-light text-bold">
                                    <label for="">Project</label>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" id="project" class="form-control" autocomplete="off" readonly value="{{ $certificate->project->PJDesc ?? '-' }}">  
                                </div>
                            </div>

                            @if($certificate->CERevokeInd == 1)
                                <div class="row mb-4">
                                    <div class="col-md-3 text-light text-bold">
                                        <label for="">Revoke Date</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" id="revokedate" class="form-control" autocomplete="off" required readonly value="{{ isset($certificate->CERevokeDate) ? \Carbon\Carbon::parse($certificate->CERevokeDate)->format('d/m/Y') : '' }}">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-3 text-light text-bold">
                                        <label for="">Status</label>
                                    </div>
                                    <div class="col-md-6">
                                            <span class="badge badge-outline bg-light badge-danger">Revoke</span>
                                    </div>
                                </div>
                                
                            @else
                                <div class="row mb-4">
                                    <div class="col-md-3 text-light text-bold">
                                        <label for="">Valid From</label>
                                    </div>
                                    <div class="col-md-6">
                                    <input type="text" id="startdate" class="form-control" autocomplete="off" required readonly value="{{ isset($certificate->CEStartDate) ? $certificate->CEStartDate->format('d/m/Y') : '' }}">                            
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-3 text-light text-bold">
                                        <label for="">Valid Till</label>
                                    </div>
                                    <div class="col-md-6">
                                    <input type="text" id="enddate" class="form-control" autocomplete="off" required readonly value="{{ isset($certificate->CEEndDate) ? $certificate->CEEndDate->format('d/m/Y') : '' }}">                            
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-3 text-light text-bold">
                                        <label for="">Status</label>
                                    </div>
                                    <div class="col-md-6">
                                        @if($certificate->CEEndDate >= $certificate->CEStartDate) 
                                            <span class="badge badge-outline bg-light badge-success">Active</span>
                                        @else
                                            <span class="badge badge-outline bg-light badge-danger">Inactive</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            

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
