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
                        <a href="{{ route('signDocument.index') }}" class="text-dark">
                            <i class="fas fa-chevron-left fs-2 text-dark"></i><b class="ps-3">Back</b>
                        </a>
                    </div>
                </div>

                <div class="row d-flex justify-content-center mb-5 gap-2 ">

                    <div class="col-md-3 card bg-fancy card-no-border p-5 text-light h-50">

                        <div class="row d-flex flex-center mb-8 text-center ">
                            <div class="col-md-10 border-bottom border-light">
                                <h3 class="text-light">Document Detail</h3>
                            </div>
                        </div>
                        <div class="row p-5 d-flex flex-center">
                            <div class="col-md-6">
                                <div class="row mb-4">
                                    <div class="col-md-12 text-light text-bold ">
                                        <label for="">Date Document Signed:</label>
                                        <p>{{ $signDocument->SDMD ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-12 text-light text-bold  ">
                                        <label for="">Document Signed No.:</label>
                                        <p>{{ $signDocument->SDNo ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-12 text-light text-bold">
                                        <label for="">Certificate No.:</label>
                                        <p>{{ $signDocument->SD_CENo ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-12 text-light text-bold ">
                                        <label for="">Signer Name:</label>
                                        <p>{{ $signDocument->certificate->CEName ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="col-md-7 card bg-light card-no-border p-5">

                        <div class="row mb-4">
                            <div class="col-md-6" >
                                <h3 class="text-dark">Document</h3>
                            </div>
                            <div class="col-md-6 text-end" >
                            <a class=""target="_blank" href="{{ route('file.download', [$signDocument->fileAttach->FAGuidID ?? '']) }}" ><i class="fas text-dark fs-2 fa-solid fa-download"></i></a>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col md-12">
                                <iframe src="{{ route('file.view', [$signDocument->fileAttach->FAGuidID ?? '']) }}" width="100%" height="800" frameborder="0"></iframe>
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
