@extends('layouts.app')
@push('css')
@endpush
@section('content')
     <div class="breadcrumbs-inline pt-3 pb-1" id="breadcrumbs-wrapper">
        <!-- Search for small screen-->
        <div class="container">
            <div class="row">
                <div class="col s12 breadcrumbs-left">
                    <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down"><span>{{ __('Employer Info')}}</span></h5>
                    <ol class="breadcrumbs mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home')}}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Employer Details')}}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

	<div class="col s12">
		<div class="card">
			<div class="card-content">
				 <form class="ajax-form" >
					<div class="row">
						<div class="col s12">
							<h4 class="card-title">{{ __('Company Information')}}</h4>
							<div class="row">
								<div class="input-field col s12 m6 l8 form-group">
									<i class="material-icons prefix">info</i>
									{!! Form::text('compName', $employer->ERCompName ?? null, ['id' => 'compName', 'class' => 'form-control',  'readonly']) !!}
									<label for="compName">{{ __('Company Name')}} <span style="color:red">*</span></label>
								</div>
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('ssmNo', $employer->ERSSMNo ?? null, ['id' => 'ssmNo', 'class' => 'form-control',  'readonly']) !!}
									<label for="ssmNo">{{ __('SSM No')}}</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">phone_iphone</i>
									{!! Form::text('compTelNo', $employer->ERCompTelNo ?? null, ['id' => 'compTelNo', 'class' => 'form-control', 'readonly']) !!}
									<label for="compTelNo">{{ __('Tel No')}} </label>
								</div>
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">phone_iphone</i>
									{!! Form::text('compFaxNo', $employer->ERCompFaxNo ?? null, ['id' => 'compFaxNo', 'class' => 'form-control', 'readonly']) !!}
									<label for="compFaxNo">{{ __('Fax No')}} </label>
								</div>
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">email</i>
									{!! Form::text('compEmail', $employer->ERCompEmail ?? null, ['id' => 'compEmail', 'class' => 'form-control', 'readonly']) !!}
									<label for="compEmail">{{ __('Email')}} </label>
								</div>
							</div>
							</br>
							<h4 class="card-title">{{ __('Registered Address')}}</h4>
							<div class="row">
								<div class="input-field col s12 m12 l12 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('regAddr1', $employer->ERRegAddr1 ?? null, ['id' => 'regAddr1', 'class' => 'form-control',  'readonly']) !!}
									<label for="regAddr1">{{ __('Address 1')}}</label>
								</div>
								<div class="input-field col s12 m12 l12 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('regAddr2', $employer->ERRegAddr2 ?? null, ['id' => 'regAddr2', 'class' => 'form-control',  'readonly']) !!}
									<label for="regAddr2">{{ __('Address 1')}}</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('regCity', $employer->ERRegCity ?? null, ['id' => 'regCity', 'class' => 'form-control',  'readonly']) !!}
									<label for="regCity">{{ __('City')}}</label>
								</div>
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('regPostcode', $employer->ERRegPostcode ?? null, ['id' => 'regPostcode', 'class' => 'form-control',  'readonly']) !!}
									<label for="regPostcode">{{ __('Postcode')}}</label>
								</div>
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('regState', $employer->RegState ?? null, ['id' => 'regState', 'class' => 'form-control',  'readonly']) !!}
									<label for="regState">{{ __('State')}}</label>
								</div>
							</div>
							</br>
							<h4 class="card-title">{{ __('Company Address')}}</h4>
							<div class="row">
								<div class="input-field col s12 m12 l12 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('regAddr1', $employer->ERCompAddr1 ?? null, ['id' => 'compAddr1', 'class' => 'form-control',  'readonly']) !!}
									<label for="compAddr1">{{ __('Address 1')}}</label>
								</div>
								<div class="input-field col s12 m12 l12 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('regAddr2', $employer->ERCompAddr2 ?? null, ['id' => 'compAddr2', 'class' => 'form-control',  'readonly']) !!}
									<label for="compAddr2">{{ __('Address 1')}}</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('compCity', $employer->ERCompCity ?? null, ['id' => 'compCity', 'class' => 'form-control',  'readonly']) !!}
									<label for="compCity">{{ __('City')}}</label>
								</div>
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('compPostcode', $employer->ERCompPostcode ?? null, ['id' => 'compPostcode', 'class' => 'form-control',  'readonly']) !!}
									<label for="compPostcode">{{ __('Postcode')}}</label>
								</div>
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('compState', $employer->CompState ?? null, ['id' => 'compState',  'class' => 'form-control', 'readonly']) !!}
									<label for="compState">{{ __('State')}}</label>
								</div>
							</div>
							</br>
							<h4 class="card-title">{{ __('Contact Person')}}</h4>
							<div class="row">
								<div class="input-field col s12 m12 l12 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('cpFirstName', $employer->ERCPFirstName ?? null, ['id' => 'cpFirstName', 'class' => 'form-control',  'readonly']) !!}
									<label for="cpFirstName">{{ __('Address 1')}}</label>
								</div>
								<div class="input-field col s12 m12 l12 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('cpLastName', $employer->ERCPLastName ?? null, ['id' => 'cpLastName', 'class' => 'form-control',  'readonly']) !!}
									<label for="cpLastName">{{ __('Address 1')}}</label>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">person</i>
									{!! Form::text('cpDesignation', $employer->ERCPDesignation ?? null, ['id' => 'cpDesignation', 'class' => 'form-control',  'readonly']) !!}
									<label for="cpDesignation">{{ __('City')}}</label>
								</div>
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">phone_iphone</i>
									{!! Form::text('cpPhoneNo', $employer->ERCPPhoneNo ?? null, ['id' => 'cpPhoneNo', 'class' => 'form-control', 'readonly']) !!}
									<label for="cpPhoneNo">{{ __('Tel No')}} </label>
								</div>
								<div class="input-field col s12 m6 l4 form-group">
									<i class="material-icons prefix">email</i>
									{!! Form::text('cpEmail', $employer->ERCPEmail ?? null, ['id' => 'cpEmail', 'class' => 'form-control', 'readonly']) !!}
									<label for="cpEmail">{{ __('Email')}} </label>
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