@extends('layouts.app')

@push('css')
    <style>
    </style>
@endpush
@section('content')


<div id="kt_app_content_container" class="app-container d-flex justify-content-center align-items-center">
	<div class="card mb-5 mb-xl-10 bg-content-card card-no-border w-100">
		<div id="kt_account_settings_profile_details">
			<div class="card-body p-9">

                <div class="row flex-row">
                    <div class="col-md-12">
                        <h3>Permohonan Peranan</h3>
                    </div>

                </div>

				<div class="row flex-row">
					<div class="col-md-12 col-sm-12 mt-5">
                        <div class="card bg-search-card">
                            <div class="d-flex align-items-center collapsible py-3 toggle mb-0 p-5" data-bs-toggle="collapse"
                                data-bs-target="#kt_job_4_1" aria-expanded="true">
                                <div class="btn btn-sm btn-icon mw-20px btn-active-color-primary me-5">
                                    <i class="fas fa-search text-dark fs-1x"></i>
                                </div>
                                <h4 class="text-dark fw-bold cursor-pointer mb-0">
                                    Carian
                                </h4>
                            </div>
                            <div id="kt_job_4_1" class="fs-6 ms-1 collapse p-10" style="">
                            
                        
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="search_name" class="form-label">Nama</label>
                                        <input type="text" name="search_name" class="form-control" id="search_name" placeholder="Tulis nama disini"/>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="search_ic" class="form-label">No. Kad Pengenalan</label>
                                        <input type="text" name="search_ic" class="form-control" id="search_ic" placeholder="Tulis kad pengenalan"/>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="search_name" class="form-label">No. Staf</label>
                                        <input type="text" name="search_staffno" class="form-control" id="search_staffno" placeholder="Tulis no. staf"/>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="search_appno" class="form-label">No. Permohonan</label>
                                        <input type="text" name="search_appno" class="form-control" id="search_appno" placeholder="Tulis no. peromohonan"/>
                                    </div>

                                </div>
                        
                                <div class="row mt-4">
                                    <div class="col-md-2">
                                        <label for="search_name" class="form-label">Status</label>
                                        {!! Form::select('status', [] , null, [
                                            'id' => 'status',
                                            'class' => 'form-select',
                                            'placeholder' => 'Pilih Status',
                                        ]) !!}
                                    </div>
                                    <div class="col-md-3">
                                        <label for="search_datefrom" class="form-label">Tarikh Dari:</label>
                                        <input type="date" name="search_datefrom" class="form-control" id="search_datefrom" placeholder="Tarikh dari"/>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="search_dateto" class="form-label">Tarikh Hingga:</label>
                                        <input type="date" name="search_dateto" class="form-control" id="search_dateto" placeholder="Tarikh hingga"/>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-12 text-end">
                                        <a href="#" onclick="searchApplication()" class="btn btn-sm btn-info">Cari</a>
                                    </div>
                                </div>

                            </div>
                        </div>


					</div>
				</div>

                <div class="row flex-row mt-10 card p-5 bg-content-card">
                    <div class="col-md-12 text-end">
                        <a href="#" class="btn btn-daun text-white">
                            <i class="fas fa-add fs-1x text-white"></i> Tambah
                        </a>
                    </div>

                    <div class="col-md-12 ">
                        <div class="responsive-table scroll-x">
                            <table class="table table-sm table-bordered bg-secondary" id="application-tab">
                                <thead class="bg-cyan">
                                    <tr>
                                        <th>Bil.</th>
                                        <th>Tarikh</th>
                                        <th>No. Permohonan</th>
                                        <th>Nama</th>
                                        <th>No. Kad Pengenalan</th>
                                        <th>No. Gaji</th>
                                        <th>Jabatan</th>
                                        <th>Status Permohonan</th>
                                        <th>Tindakan</th>
                                    </tr>
                                </thead>
                            </table>

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
