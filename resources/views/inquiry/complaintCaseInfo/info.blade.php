@extends('layouts.appFull')
@push('css')
@endpush
@section('content')
     <div class="breadcrumbs-inline pt-3 pb-1" id="breadcrumbs-wrapper">
        <!-- Search for small screen-->
        <div class="container">
            <div class="row">
                <div class="col s9 breadcrumbs-left">
                    <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down"><span>{{ __('Complaint Case Info')}}</span></h5>
                    <ol class="breadcrumbs mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home')}}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Complaint Case Details')}}
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
				 <form class="ajax-form" >
					<div class="row">
						<div class="col s12">
							<h4 class="card-title">{{ __('Complaint Case Information')}}</h4>
							<div class="row">
								<div class="input-field col s12 m6 l6 form-group">
									{!! Form::text('caseNo', $complaintCase->CCNo ?? null, ['id' => 'caseNo', 'readonly']) !!}
									<label for="caseNo">{{ __('Case No')}}</label>
								</div>
								<div class="input-field col s12 m6 l6 form-group">
									 {!! Form::date('caseDate', isset($complaintCase->CCDate) ? \Carbon\Carbon::parse($complaintCase->CCDate)->format('Y-m-d') : null, [ 'class' => 'form-control datepicker', 'id' => 'caseDate', 'readonly']) !!}
									<label for="caseDate">{{ __('Date')}}</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12 m6 l6 form-group">
									{!! Form::text('caseStatus', $complaintCase->CSDesc ?? null, ['id' => 'caseStatus', 'class' => 'form-control',  'readonly']) !!}
									<label for="caseStatus">{{ __('Status')}}<span style="color: red;">*</span></label>
								</div>
								<div class="input-field col s12 m6 l6 form-group" >
									{!! Form::text('reportBy', $complaintCase->TypeDesc ?? null, ['id' => 'reportBy', 'class' => 'form-control',  'readonly']) !!}
									<label for="reportBy">{{ __('Reported By')}}<span style="color: red;">*</span></label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12 m12 l12 form-group">
									{!! Form::text('category', $complaintCase->CGDesc ?? null, ['id' => 'category', 'class' => 'form-control',  'readonly']) !!}
									<label for="category">{{ __('Category')}}<span style="color: red;">*</span></label>
								</div>
							</div>
							
							<div class="row">
								<div class="input-field col s12 m12 l12 form-group">
									{!! Form::text('details', $complaintCase->CCReason ?? null, ['id' => 'details', 'class' => 'form-control'],  'readonly') !!}
									<label for="details">{{ __('Details')}} </label>
								</div>                                            
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="col s6">
		<div class="card">
			<div class="card-content">
				 <form class="ajax-form" >
					<div class="row">
						<div class="col s12">
							<h4 class="card-title">{{ __('Employer Information')}}<a target="_blank" href="{{ route('inquiry.employerInfo.info', $complaintCase->ERID) }}"> <i class="material-icons prefix">info</i></a></h4>
							<div class="row">
								<div class="input-field col s12 m6 l8 form-group">
									{!! Form::text('compName', $complaintCase->ERCompName ?? null, ['id' => 'compName', 'class' => 'form-control',  'readonly']) !!}
									<label for="compName">{{ __('Company Name')}}</label>
								</div>
								<div class="input-field col s12 m6 l4 form-group">
									<label for="ssmNo">{{ __('SSM No')}}</label>
									{!! Form::text('ssmNo', $complaintCase->ERSSMNo ?? null, ['id' => 'ssmNo', 'class' => 'form-control',  'readonly']) !!}
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	
		<div class="card">
			<div class="card-content">
				 <form class="ajax-form" >
					<div class="row">
						<div class="col s12">
							<h4 class="card-title">{{ __('Employee Information')}}<a target="_blank" href="{{ route('inquiry.employeeInfo.info', $complaintCase->EEID) }}"> <i class="material-icons prefix">info</i></a></h4>
							<div class="row">
								<div class="input-field col s12 m6 l6 form-group">
									{!! Form::text('fullName', $complaintCase->EEFirstName.' '.$complaintCase->EELastName ?? null, ['id' => 'fullName']) !!}
									<label for="userName">{{ __('Name')}} </label>
								</div>
								<div class="input-field col s12 m6 l6 form-group">
									{!! Form::date('dob', isset($complaintCase->EEDOB) ? \Carbon\Carbon::parse($complaintCase->EEDOB)->format('Y-m-d') : null, [ 'class' => 'form-control datepicker', 'id' => 'dob', 'readonly']) !!}
									<label for="dob">{{ __('Date of Birth')}}</label>
								</div>
								<div class="input-field col s12 m6 l6 form-group">
									{!! Form::text('nationality', $complaintCase->EENationality_CTCode ?? null, ['id' => 'nationality', 'readonly']) !!}
									<label for="nationality">{{ __('Nationality')}}</label>
								</div>
								<div class="input-field col s12 m6 l6 form-group">
									{!! Form::text('passportNo', $complaintCase->EEPassportNo ?? null, ['id' => 'passportNo', 'readonly']) !!}
									<label for="passportNo">{{ __('Passport No')}}</label>
								</div>
								<div class="input-field col s12 m6 l6 form-group">
									 {!! Form::date('passportIssueDate', isset($complaintCase->EEPassportIssueDate) ? \Carbon\Carbon::parse($complaintCase->EEPassportIssueDate)->format('Y-m-d') : null, [ 'class' => 'form-control datepicker', 'id' => 'dob', 'readonly']) !!}
									<label for="passportIssueDate">{{ __('Passport Issue Date')}}</label>
								</div>
								<div class="input-field col s12 m6 l6 form-group">
									{!! Form::date('passportExpDate', isset($complaintCase->EEPassportExpDate) ? \Carbon\Carbon::parse($complaintCase->EEPassportExpDate)->format('Y-m-d') : null, [ 'class' => 'form-control datepicker', 'id' => 'dob', 'readonly']) !!}
									 <label for="passportExpDate">{{ __('Passport Expiry Date')}}</label>
								</div>
							</div>
						</div>
					</div>
				</form>
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
@endpush