@extends('layouts.app')

@push('css')
@endpush
@section('content')
    <meta name="my.message.yes" content="{{ __('messages.yes') }}">
    <div class="content-wrapper-before gradient-45deg-deep-purple-purple"></div>
    <div class="breadcrumbs-inline pt-3 pb-1" id="breadcrumbs-wrapper">
        <!-- Search for small screen-->
        <div class="container">
            <div class="row">
                <div class="col s10 m6 l6 breadcrumbs-left">
                    @if (isset($customer))
                        <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down">
                            <span>{{ __('Edit Customer') }}</span></h5>
                        <ol class="breadcrumbs mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('Home') }}</a>
                            </li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('masterData.customer.index') }}">{{ __('Customer') }}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ __('Edit Customer') }}
                            </li>
                        </ol>
                    @else
                        <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down">
                            <span>{{ __('New Customer') }}</span></h5>
                        <ol class="breadcrumbs mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('Home') }}</a>
                            </li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('masterData.customer.index') }}">{{ __('Customer') }}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ __('New Customer') }}
                            </li>
                        </ol>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="section">
            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <div class="col m6 s6" style=" padding:5px">
                                <h4 class="card-title">{{ __('Customer Entry') }}</h4>
                            </div>
                            @isset($customer)
                                <form class="ajax-form" novalidate
                                    action="{{ route('masterData.customer.update', [$customer->CSID]) }}" method="POST">
                                @else
                                    <form class="ajax-form" novalidate action="{{ route('masterData.customer.store') }}"
                                        method="POST">
                                    @endisset
                                    <div class="row">
                                        <div class="col s12">
                                            <div class="row">
                                                <div class="input-field col m12 s6" style="text-align: center;">
                                                    <div class="form-group">
                                                        <img src="{{ isset($profilePhotoURL) ? $profilePhotoURL : '' }}"
                                                            id="profile-img-tag" class="rounded-circle" width="150px"
                                                            alt="Avatar" style="border-radius: 50%;" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="input-field file-field col m12 s6 form-group">
                                                    <div class="col m4 s6" style="text-align: center; margin-left: 35%;">
                                                        <div class="btn float-right orange darken-3">
                                                            <span>{{ __('File') }}</span>
                                                            <input name="file" type="file" id="profile-img"
                                                                accept="image/*" class="form-control"
                                                                style="padding: 3px 12px !important;"
                                                                onchange="preview_image(event)">
                                                        </div>
                                                        <div class="file-path-wrapper">
                                                            <input class="file-path validate" type="text">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="input-field col s12 m12 l12 form-group">
                                                    {!! Form::text('name', $customer->CSName ?? null, ['id' => 'name', 'class' => 'form-control']) !!}
                                                    <label for="name">{{ __('Name') }} <span
                                                            style="color:red">*</span></label>
                                                </div>
                                                <div class="input-field col s12 m6 l6 form-group">
                                                    {!! Form::text('email', $customer->CSEmail ?? null, ['id' => 'email', 'class' => 'form-control']) !!}
                                                    <label for="email">{{ __('Email') }} <span
                                                            style="color:red">*</span></label>
                                                </div>
                                                <div class="input-field col s12 m6 l6 form-group">
                                                    {!! Form::text('phone', $customer->CSPhoneNo ?? null, ['id' => 'phone', 'class' => 'form-control']) !!}
                                                    <label for="phone">{{ __('Phone') }} </label>
                                                </div>
                                                <div class="input-field col s12 m6 l6 form-group">
                                                    {!! Form::text('customerCode', $customer->CSCode ?? null, ['id' => 'customerCode', 'readonly']) !!}
                                                    <label for="customerCode">{{ __('Customer Code') }}</label>
                                                </div>
                                                <div class="input-field col m3 s6 form-group">
                                                    {!! Form::select('isActive', $isActive, $customer->CSActive ?? null, [
                                                        'id' => 'isActive',
                                                        'class' => 'select2 form-control',
                                                        'placeholder' => trans('message.dropdown_default'),
                                                    ]) !!}
                                                    <label for="isActive">{{ __('Status Active') }}<span
                                                            style="color:red">*</span></label>
                                                </div>
                                            </div>

                                            <div class="row">
                                            </div>
                                            <div class="row">
                                                <div class="col s6">
                                                    <div class="card">
                                                        <div class="card-content">
                                                            <h4 class="card-title">{{ __('Malaysia Address') }}</h4>
                                                            <div class="row">
                                                                <div class="input-field col m12 s12 form-group">
                                                                    {!! Form::text('mStreet1', $customer->CSMStreet1 ?? null, ['id' => 'mStreet1', 'class' => 'form-control']) !!}
                                                                    <label for="mStreet1">{{ __('Street 1') }} <span
                                                                            style="color:red">*</span></label>
                                                                </div>
                                                                <div class="input-field col m12 s12 form-group">
                                                                    {!! Form::text('mStreet2', $customer->CSMStreet2 ?? null, ['id' => 'mStreet2', 'class' => 'form-control']) !!}
                                                                    <label for="mStreet2">{{ __('Street 2') }} </label>
                                                                </div>
                                                                <div class="input-field col m12 s12 form-group">
                                                                    {!! Form::text('mPostcode', $customer->CSMPostcode ?? null, ['id' => 'mPostcode', 'class' => 'form-control']) !!}
                                                                    <label for="mPostcode">{{ __('Postcode') }} <span
                                                                            style="color:red">*</span></label>
                                                                </div>
                                                                <div class="input-field col m12 s12 form-group">
                                                                    {!! Form::text('mCity', $customer->CSMCity ?? null, ['id' => 'mCity', 'class' => 'form-control']) !!}
                                                                    <label for="mCity">{{ __('City') }} <span
                                                                            style="color:red">*</span></label>
                                                                </div>
                                                                <div class="input-field col m12 s12 form-group">
                                                                    {!! Form::select('mState', $mState, isset($customer) ? $customer->CSM_StateCode : null, [
                                                                        'id' => 'mState',
                                                                        'class' => 'select2 form-control',
                                                                        'placeholder' => trans('message.dropdown_default'),
                                                                    ]) !!}
                                                                    <label for="mState">{{ __('State') }} <span
                                                                            style="color:red">*</span></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col s6">
                                                    <div class="card">
                                                        <div class="card-content">
                                                            <h4 class="card-title">{{ __('Oversea Address') }}</h4>
                                                            <div class="row">
                                                                <div class="input-field col m12 s12 form-group">
                                                                    {!! Form::text('street1', $customer->CSStreet1 ?? null, ['id' => 'street1', 'class' => 'form-control']) !!}
                                                                    <label for="street1">{{ __('Street 1') }} <span
                                                                            style="color:red">*</span></label>
                                                                </div>
                                                                <div class="input-field col m12 s12 form-group">
                                                                    {!! Form::text('street2', $customer->CSStreet2 ?? null, ['id' => 'street2', 'class' => 'form-control']) !!}
                                                                    <label for="street2">{{ __('Street 2') }} </label>
                                                                </div>
                                                                <div class="input-field col m12 s12 form-group">
                                                                    {!! Form::text('postcode', $customer->CSPostcode ?? null, ['id' => 'phone', 'class' => 'form-control']) !!}
                                                                    <label for="postcode">{{ __('Postcode') }} <span
                                                                            style="color:red">*</span></label>
                                                                </div>
                                                                <div class="input-field col m12 s12 form-group">
                                                                    {!! Form::text('city', $customer->CSCity ?? null, ['id' => 'phone', 'class' => 'form-control']) !!}
                                                                    <label for="city">{{ __('City') }} <span
                                                                            style="color:red">*</span></label>
                                                                </div>
                                                                <div class="input-field col m12 s12 form-group">
                                                                    {!! Form::select('country', $country, isset($customer) ? $customer->CS_CTCode : null, [
                                                                        'id' => 'country',
                                                                        'class' => 'select2 form-control',
                                                                        'placeholder' => trans('message.dropdown_default'),
                                                                    ]) !!}
                                                                    <label for="country">{{ __('Country') }} <span
                                                                            style="color:red">*</span></label>
                                                                </div>
                                                                <div class="input-field col m12 s12 form-group">
                                                                    {!! Form::select('state', $state, isset($customer) ? $customer->CS_StateCode : null, [
                                                                        'id' => 'state',
                                                                        'class' => 'select2 form-control',
                                                                        'placeholder' => trans('message.dropdown_default'),
                                                                    ]) !!}
                                                                    <label for="state">{{ __('State') }} <span
                                                                            style="color:red">*</span></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col s12">
                                                    <div class="card">
                                                        <div class="card-content">
                                                            <h4 class="card-title">{{ __('Password') }}</h4>
                                                            <div class="row">
                                                                @if (isset($employee))
                                                                    <div class="input-field col m4 s6 form-group">
                                                                        <label>
                                                                            <input type="checkbox" name="resetPassword"
                                                                                value="1" />
                                                                            <span>{{ __('Reset Password') }}</span>
                                                                        </label>
                                                                    </div>
                                                                    <div class="input-field col m4 s6 form-group">
                                                                        {{ Form::password('password', ['id' => 'password', 'class' => 'form-control']) }}
                                                                        <label
                                                                            for="password">{{ __('New Password') }}</label>
                                                                    </div>
                                                                    <div class="input-field col m4 s6 form-group">
                                                                        {{ Form::password('password-confirm', ['id' => 'password-confirm', 'class' => 'form-control']) }}
                                                                        <label
                                                                            for="password-confirm">{{ __('Confirm Password') }}</label>
                                                                    </div>
                                                                @else
                                                                    <div class="input-field col m6 s6 form-group">
                                                                        {{ Form::password('password', ['id' => 'password', 'class' => 'form-control']) }}
                                                                        <label for="password">{{ __('Password') }}</label>
                                                                    </div>
                                                                    <div class="input-field col m6 s6 form-group">
                                                                        {{ Form::password('password-confirm', ['id' => 'password-confirm', 'class' => 'form-control']) }}
                                                                        <label
                                                                            for="password-confirm">{{ __('Confirm Password') }}</label>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col s12 m12 l12" style="text-align:right; ">
                                                    <a class="btn gradient-45deg-green-teal"
                                                        href="{{ route('masterData.customer.index') }}">{{ __('Back') }}</a>
                                                    <button class="btn btn-primary"
                                                        id="save">{{ __('Submit') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script type="text/javascript">
        function preview_image(event) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById('profile-img-tag');
                output.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }


        $('#country').on('change', function() {
            $('#state').empty();
            //console.log($('#state'));
            $country = $('#country').val();

            if ($country != '') {
                populateState();
            } else {
                $('#state').html('');
                $('#state').append('<option value="">{{ trans('message.dropdown_default') }}</option>');
                $('#state').attr("disabled", true);
                $('#state').formSelect();
            }
        });


        function populateState() {
            $.ajax({
                type: 'POST',
                url: '{{ route('masterData.customer.populateState') }}',
                data: {
                    country: $('#country').val(),
                },
                success: function(response) {

                    //console.log(response)
                    if (response.length > 0) {
                        $('#state').html('');
                        $('#state').append(
                        '<option value="">{{ trans('message.dropdown_default') }}</option>');

                        $.each(response, function(i, v) {
                            $('#state').append('<option value="' + v.StateCode + '">' + v.StateDesc +
                                '</option>');
                        });
                        $('#state').formSelect();
                        $('#state').attr("disabled", false);
                    } else {
                        $('#state').html('');
                        $('#state').append(
                        '<option value="">{{ trans('message.dropdown_default') }}</option>');
                        $('#state').attr("disabled", true);
                        $('#state').formSelect();
                    }

                }
            });
        }
    </script>
@endpush
