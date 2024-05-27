@extends('layouts.appFull')
@push('css')
@endpush
@section('content')
     <div class="breadcrumbs-inline pt-3 pb-1" id="breadcrumbs-wrapper">
        <!-- Search for small screen-->
        <div class="container">
            <div class="row">
                <div class="col s9 breadcrumbs-left">
                    <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down"><span>{{ __('Employer Info')}}</span></h5>
                    <ol class="breadcrumbs mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home')}}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Employer Details')}}
                        </li>
                    </ol>
                </div>
				<div class="col s3">
					 <button id="back-btn" type="button" class="btn waves-effect waves-light gradient-45deg-green-teal" style="float: right">{{ __('Back')}}</button>
			   </div>
            </div>
        </div>
    </div>

	<div class="col s6">
		<div class="card">
			<div class="card-content">
				<div class="row">
					<div class="col s12">
						<h4 class="card-title">{{ __('Company Information')}}</h4>
						<div class="row">
							<div class="input-field col s12 m6 l8 form-group">
								{!! Form::text('compName', $employer->ERCompName ?? null, ['id' => 'compName', 'class' => 'form-control',  'readonly']) !!}
								<label for="compName">{{ __('Company Name')}} <span style="color:red">*</span></label>
							</div>
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('ssmNo', $employer->ERSSMNo ?? null, ['id' => 'ssmNo', 'class' => 'form-control',  'readonly']) !!}
								<label for="ssmNo">{{ __('SSM No')}}</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('compTelNo', $employer->ERCompTelNo ?? null, ['id' => 'compTelNo', 'class' => 'form-control', 'readonly']) !!}
								<label for="compTelNo">{{ __('Tel No')}} </label>
							</div>
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('compFaxNo', $employer->ERCompFaxNo ?? null, ['id' => 'compFaxNo', 'class' => 'form-control', 'readonly']) !!}
								<label for="compFaxNo">{{ __('Fax No')}} </label>
							</div>
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('compEmail', $employer->ERCompEmail ?? null, ['id' => 'compEmail', 'class' => 'form-control', 'readonly']) !!}
								<label for="compEmail">{{ __('Email')}} </label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>	
		<div class="card">
			<div class="card-content">
				<div class="row">
					<div class="col s12">
						<h4 class="card-title">{{ __('Registered Address')}}</h4>
						<div class="row">
							<div class="input-field col s12 m12 l12 form-group">
								{!! Form::text('regAddr1', $employer->ERRegAddr1 ?? null, ['id' => 'regAddr1', 'class' => 'form-control',  'readonly']) !!}
								<label for="regAddr1">{{ __('Address 1')}}</label>
							</div>
							<div class="input-field col s12 m12 l12 form-group">
								{!! Form::text('regAddr2', $employer->ERRegAddr2 ?? null, ['id' => 'regAddr2', 'class' => 'form-control',  'readonly']) !!}
								<label for="regAddr2">{{ __('Address 2')}}</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('regCity', $employer->ERRegCity ?? null, ['id' => 'regCity', 'class' => 'form-control',  'readonly']) !!}
								<label for="regCity">{{ __('City')}}</label>
							</div>
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('regPostcode', $employer->ERRegPostcode ?? null, ['id' => 'regPostcode', 'class' => 'form-control',  'readonly']) !!}
								<label for="regPostcode">{{ __('Postcode')}}</label>
							</div>
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('regState', $employer->RegState ?? null, ['id' => 'regState', 'class' => 'form-control',  'readonly']) !!}
								<label for="regState">{{ __('State')}}</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>	
		<div class="card">
			<div class="card-content">
				<div class="col m6 s6" style=" padding:5px"> <h4 class="card-title">{{ __('Employee Information')}}</h4></div>
				<div class="col s6 m6 l6" style="text-align:right; padding:5px">
				</div>
				<div class="row">
					<div class="col s12">
						<table id="page-employee" class="display responsive-table" style=" width:100%; max-height: 1500px;overflow-x: auto;">
							  <thead>
							<tr>
								<th width="5px">No.</th>
								<th>{{ __('Passport No')}}</th>
								<th>{{ __('Name')}}</th>
								<th>{{ __('Birth Date')}}</th>
								<th>{{ __('Nationality')}}</th>
								<th>{{ __('Gender')}}</th>
							</tr>
							</thead>
						</table>
						<br/>
					</div>
				</div>
			</div>
		</div>
	
	</div>
	<div class="col s6">
		<div class="card">
			<div class="card-content">
				<div class="row">
					<div class="col s12">
						<h4 class="card-title">{{ __('Contact Person')}}</h4>
						<div class="row">
							<div class="input-field col s12 m6 l6 form-group">
								{!! Form::text('cpFirstName', $employer->ERCPFirstName ?? null, ['id' => 'cpFirstName', 'class' => 'form-control',  'readonly']) !!}
								<label for="cpFirstName">{{ __('First Name')}}</label>
							</div>
							<div class="input-field col s12 m6 l6 form-group">
								{!! Form::text('cpLastName', $employer->ERCPLastName ?? null, ['id' => 'cpLastName', 'class' => 'form-control',  'readonly']) !!}
								<label for="cpLastName">{{ __('Last Name')}}</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('cpDesignation', $employer->ERCPDesignation ?? null, ['id' => 'cpDesignation', 'class' => 'form-control',  'readonly']) !!}
								<label for="cpDesignation">{{ __('City')}}</label>
							</div>
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('cpPhoneNo', $employer->ERCPPhoneNo ?? null, ['id' => 'cpPhoneNo', 'class' => 'form-control', 'readonly']) !!}
								<label for="cpPhoneNo">{{ __('Tel No')}} </label>
							</div>
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('cpEmail', $employer->ERCPEmail ?? null, ['id' => 'cpEmail', 'class' => 'form-control', 'readonly']) !!}
								<label for="cpEmail">{{ __('Email')}} </label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="card">
			<div class="card-content">
				<div class="row">
					<div class="col s12">
						<h4 class="card-title">{{ __('Company Address')}}</h4>
						<div class="row">
							<div class="input-field col s12 m12 l12 form-group">
								{!! Form::text('regAddr1', $employer->ERCompAddr1 ?? null, ['id' => 'compAddr1', 'class' => 'form-control',  'readonly']) !!}
								<label for="compAddr1">{{ __('Address 1')}}</label>
							</div>
							<div class="input-field col s12 m12 l12 form-group">
								{!! Form::text('regAddr2', $employer->ERCompAddr2 ?? null, ['id' => 'compAddr2', 'class' => 'form-control',  'readonly']) !!}
								<label for="compAddr2">{{ __('Address 2')}}</label>
							</div>
						</div>
						<div class="row">
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('compCity', $employer->ERCompCity ?? null, ['id' => 'compCity', 'class' => 'form-control',  'readonly']) !!}
								<label for="compCity">{{ __('City')}}</label>
							</div>
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('compPostcode', $employer->ERCompPostcode ?? null, ['id' => 'compPostcode', 'class' => 'form-control',  'readonly']) !!}
								<label for="compPostcode">{{ __('Postcode')}}</label>
							</div>
							<div class="input-field col s12 m6 l4 form-group">
								{!! Form::text('compState', $employer->CompState ?? null, ['id' => 'compState',  'class' => 'form-control', 'readonly']) !!}
								<label for="compState">{{ __('State')}}</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>	
		<div class="card">
			<div class="card-content">
				<div class="col m6 s6" style=" padding:5px"> <h4 class="card-title">{{ __('Complaint Case Information')}}</h4></div>
				<div class="col s6 m6 l6" style="text-align:right; padding:5px">
				</div>
				<div class="row">
					<div class="col s12">
						<table id="page-complaint-case" class="display responsive-table" style=" width:100%; max-height: 1500px;overflow-x: auto;">
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
			
            var tableEmployee = $('#page-employee').DataTable({
                dom: 'Brtip',
                @include('layouts._partials.lengthMenu')
                @include('layouts._partials.dt_buttonConfigBlank')
                processing: true,
                serverSide: false,
                ordering:true,
                ajax:  {
                    "url" :"{{ route('inquiry.employerInfo.datatableEmployee') }}",
                    "method": 'POST',
                    "data" : {"ssmNo" : $('#ssmNo').val() },
                    // error callback to handle error
                    "error": function(xhr, error, thrown) {
                        console.log("Error occurred!");
                        console.log(xhr, error, thrown);
                    }
                },
                columns: [
					{ name: 'DT_RowIndex', data: 'DT_RowIndex', orderable: false, searchable: false, class: 'dt-body-center' },
					{ name: 'passportNo', data: 'passportNo', class: 'dt-body-left' },
					{ name: 'fullName', data: 'fullName', class: 'dt-body-left' },
					{ name: 'dob.timestamp', data: {'_': 'dob.display', 'sort': 'dob.timestamp'}},
					{ name: 'nationality', data: 'nationality', class: 'dt-body-left' },
					{ name: 'gender', data: 'gender', class: 'dt-body-left' },
                ]
            });
            tableEmployee.buttons().container().appendTo('.button-table-export');
			
			
            var tableComplaintCase = $('#page-complaint-case').DataTable({
                dom: 'Brtip',
                @include('layouts._partials.lengthMenu')
                @include('layouts._partials.dt_buttonConfigBlank')
                processing: true,
                serverSide: false,
                ordering:true,
                ajax:  {
                    "url" :"{{ route('inquiry.employerInfo.datatableComplaintCase') }}",
                    "method": 'POST',
                    "data" : {"ssmNo" : $('#ssmNo').val() },
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
            tableComplaintCase.buttons().container().appendTo('.button-table-export');
        })(jQuery);
    </script>
@endpush