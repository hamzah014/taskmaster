@extends('layouts.appFull')
@push('css')
@endpush
@section('content')
     <div class="breadcrumbs-inline pt-3 pb-1" id="breadcrumbs-wrapper">
        <!-- Search for small screen-->
        <div class="container">
            <div class="row">
                <div class="col s9 breadcrumbs-left">
                    <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down"><span>{{ __('Employee Info')}}</span></h5>
                    <ol class="breadcrumbs mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home')}}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Employee Details')}}
                        </li>
                    </ol>
                </div>
				<div class="col s3">
					 <button id="back-btn" type="button" class="btn waves-effect waves-light gradient-45deg-green-teal" style="float: right">{{ __('Back')}}</button>
			   </div>
            </div>
        </div>
    </div>

	<div class="col s3">
		<div class="card">
			<div class="card-content">
					<div class="row">
						<div class="col s12">
							<div class="row">
								<div class="input-field col m12 s6" style="text-align: center;">
									<div class="form-group">
										<img src="{{ isset($profilePhotoURL) && $profilePhotoURL != '' ? $profilePhotoURL : ''}}"
											 id="profile-img-tag" class="rounded-circle" width="150px" alt="Avatar" style="border-radius: 50%;"/>
									</div>
								</div>
							</div>
						</div>
					</div>
			</div>
		</div>
	</div>

	<div class="col s9">
		<div class="card">
			<div class="card-content">
				 <form class="ajax-form" >
					<div class="row">
						<div class="col s12">
							<h4 class="card-title">{{ __('Personal Information')}}</h4>
							<div class="row">
								<div class="input-field col m4 s6 form-group">
									{!! Form::text('fullName', isset($employee) ? $employee->EEFirstName.' '.$employee->EELastName : null, ['id' => 'fullName', 'readonly']) !!}
									<label for="userName">{{ __('Name')}} </label>
								</div>
								<div class="input-field col m4 s6 form-group">
									{!! Form::date('dob', isset($employee->EEDOB) ? \Carbon\Carbon::parse($employee->EEDOB)->format('Y-m-d') : null, [ 'class' => 'form-control datepicker', 'id' => 'dob', 'readonly']) !!}
									<label for="dob">{{ __('Date of Birth')}}</label>
								</div>
								<div class="input-field col m4 s6 form-group">
									{!! Form::text('nationality', $employee->CTDesc ?? null, ['id' => 'nationality', 'readonly']) !!}
									<label for="nationality">{{ __('Nationality')}}</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col m4 s6 form-group">
									{!! Form::text('passportNo', $employee->EEPassportNo ?? null, ['id' => 'passportNo', 'readonly']) !!}
									<label for="passportNo">{{ __('Passport No')}}</label>
								</div>
								<div class="input-field col m4 s6 form-group">
									 {!! Form::date('passportIssueDate', isset($employee->EEPassportIssueDate) ? \Carbon\Carbon::parse($employee->EEPassportIssueDate)->format('Y-m-d') : null, [ 'class' => 'form-control datepicker', 'id' => 'dob', 'readonly']) !!}
									<label for="passportIssueDate">{{ __('Passport Issue Date')}}</label>
								</div>
								<div class="input-field col m4 s6 form-group">
									{!! Form::date('passportExpDate', isset($employee->EEPassportExpDate) ? \Carbon\Carbon::parse($employee->EEPassportExpDate)->format('Y-m-d') : null, [ 'class' => 'form-control datepicker', 'id' => 'dob', 'readonly']) !!}
									 <label for="passportExpDate">{{ __('Passport Expiry Date')}}</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col m4 s6 form-group">
									{!! Form::text('email', $employee->EEEmail ?? null, ['id' => 'email', 'class' => 'form-control']) !!}
									<label for="email">{{ __('Email')}} </label>
								</div>
								<div class="input-field col m4 s6 form-group">
									{!! Form::text('phone', $employee->EEPhoneNo ?? null, ['id' => 'phone', 'class' => 'form-control']) !!}
									<label for="phone">{{ __('Phone')}} </label>
								</div>
								<div class="input-field col m4 s6 form-group">
									{!! Form::date('entryDate', isset($employee->EEEntryDate) ? \Carbon\Carbon::parse($employee->EEEntryDate)->format('Y-m-d') : null, [ 'class' => 'form-control datepicker', 'id' => 'dob', 'readonly']) !!}
									 <label for="entryDate">{{ __('Date of Entry')}}</label>
								</div>
								<div class="input-field col m4 s6 form-group">
									{!! Form::date('workDate', isset($employee->EEWorkDate) ? \Carbon\Carbon::parse($employee->EEWorkDate)->format('Y-m-d') : null, [ 'class' => 'form-control datepicker', 'id' => 'dob', 'readonly']) !!}
									 <label for="workDate">{{ __('Date of Start Work')}}</label>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>

		<div class="card">
			<div class="card-content">
				<div class="col m6 s6" style=" padding:5px"> <h4 class="card-title">{{ __('Complaint Case Information')}}</h4></div>
				<div class="col s6 m6 l6" style="text-align:right; padding:5px">
				</div>
				<div class="row">
					<div class="col s12">
						<table id="page-length-option" class="display responsive-table" style="width:100%; max-height: 1500px;overflow-x: auto;">
							  <thead>
							<tr>
								<th width="5px">No.</th>
								<th>{{ __('Case No')}}</th>
								<th>{{ __('Status')}}</th>
								<!--th>{{ __('Company Name')}}</th>
								<th>{{ __('SSM No')}}</th>
								<th>{{ __('Employee Name')}}</th>
								<th>{{ __('Passport No')}}</th>
								<th>{{ __('Nationality')}}</th>
								<th>{{ __('Category')}}</th>
								<th>{{ __('Type')}}</th-->
								<th>{{ __('Details')}}</th>
								<th>{{ __('Created Date')}}</th>
							</tr>
							</thead>
						</table>
						<br/>
					</div>
				</div>
			</div>
		</div>
	</div>
	

	
@endsection
@push('script')
    <script type="text/javascript">
	
		$('#back-btn').click(function(e){
			window.close() ;
		})
		
        function preview_image(event)
        {
            var reader = new FileReader();
            reader.onload = function()
            {
                var output = document.getElementById('profile-img-tag');
                output.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
	
  <script>
        (function ($) {
            var table = $('#page-length-option').DataTable({
                dom: 'Brtip',
                @include('layouts._partials.lengthMenu')
                @include('layouts._partials.dt_buttonConfigBlank')
                processing: true,
                serverSide: false,
                ordering:true,
                ajax:  {
                    "url" :"{{ route('inquiry.employeeInfo.datatableComplaintCase') }}",
                    "method": 'POST',
                    "data" : {"employeeCode" : $('#employeeCode').val() },
                    // error callback to handle error
                    "error": function(xhr, error, thrown) {
                        console.log("Error occurred!");
                        console.log(xhr, error, thrown);
                    }
                },
                columns: [
                    { name: 'DT_RowIndex', data: 'DT_RowIndex', orderable: false, searchable: false, class: 'dt-body-center' },
                    { name: 'CCNo', data: 'CCNo', class: 'dt-body-left' },
                    { name: 'CSDesc', data: 'CSDesc', class: 'dt-body-left' },
                    //{ name: 'ERCompName', data: 'ERCompName', class: 'dt-body-left' },
                    //{ name: 'ERSSMNo', data: 'ERSSMNo', class: 'dt-body-left' },
                    //{ name: 'EEFullName', data: 'EEFullName', class: 'dt-body-left' },
                    //{ name: 'EEPassportNo', data: 'EEPassportNo', class: 'dt-body-left' },
                    //{ name: 'CTDesc', data: 'CTDesc', class: 'dt-body-left' },
                    //{ name: 'CGDesc', data: 'CGDesc', class: 'dt-body-left' },
                    //{ name: 'CCType', data: 'CCType', class: 'dt-body-left' },
                    { name: 'CCReason', data: 'CCReason', class: 'dt-body-left' },
                    { name: 'CCCD.timestamp', data: {'_': 'CCCD.display', 'sort': 'CCCD.timestamp'}}
				]
            });
            table.buttons().container().appendTo('.button-table-export');
        })(jQuery);
    </script>
@endpush