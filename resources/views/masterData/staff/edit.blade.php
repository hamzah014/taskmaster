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
                    <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down">
                        <span>{{ __('Staff') }}</span>
                    </h5>
                    <ol class="breadcrumbs mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('Home') }}</a>
                        </li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('masterData.staff.createEmbassyAdmin') }}">{{ __('Staff') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Edit Staff') }}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="col s12">
        <div class="container">
            <div class="section">
                <div class="row">
                    <div class="col s12">
                        <div class="card">
                            <div class="card-content">
                                <h4 class="card-title">{{ __('User Info') }}</h4>
                                <form action="{{ route('masterData.staff.update', ['id' => $staff->ESID]) }}"
                                    method="post">
                                    @csrf
                                    <div class="row">
                                        <div class="col s12">
                                            <div class="row">
                                                <div class="input-field col m8 s6 form-group">
                                                    <i class="material-icons prefix">info</i>
                                                    {!! Form::text('name', $staff->ESName, ['id' => 'name']) !!}
                                                    <label for="name">{{ __('Name') }}<span
                                                            style="color:red">*</span></label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="input-field col m4 s6 form-group">
                                                    <i class="material-icons prefix">email</i>
                                                    {!! Form::text('email', $staff->ESEmail, ['id' => 'email', 'class' => 'form-control']) !!}
                                                    <label for="email">{{ __('Email') }}<span
                                                            style="color:red">*</span> </label>
                                                </div>
                                                <div class="input-field col m4 s6 form-group">
                                                    <i class="material-icons prefix">phone_iphone</i>
                                                    {!! Form::text('phone', $staff->ESPhoneNo, ['id' => 'phone', 'class' => 'form-control']) !!}
                                                    <label for="phone">{{ __('Phone') }} </label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="input-field col m4 s6 form-group">
                                                    <i class="material-icons prefix">perm_data_setting</i>
                                                    @if ($currentUser == 'EMBADMIN' || $currentUser == 'EMBSTAFF')
                                                        <input type="hidden" name="embassy"
                                                            value="{{ $staff->ES_EMCode }}">
                                                    @else
                                                        <input type="hidden" name="embassy" id="getEmbassy"
                                                            value="{{ $staff->ES_EMCode }}">
                                                    @endif
                                                    {!! Form::select('', $embassies, $staff->ES_EMCode, [
                                                        'class' => 'select2 form-control',
                                                        'placeholder' => trans('message.dropdown_default'),
                                                        'id' => 'embassy',
                                                    ]) !!}
                                                    <label for="embassy">{{ __('Embassy') }}<span
                                                            style="color:red">*</span></label>
                                                </div>
                                                <div class="input-field col m4 s6 form-group">
                                                    @if ($currentUser == 'EMBADMIN' || $currentUser == 'EMBSTAFF')
                                                        <input type="hidden" name="role"
                                                            value="{{ $staff->user->role->RLCode }}">
                                                    @else
                                                        <input type="hidden" name="role" id="getRole"
                                                            value="{{ $staff->user->role->RLCode }}">
                                                    @endif
                                                    <i class="material-icons prefix">expand_circle_down</i>
                                                    {!! Form::select('', $roles, $staff->user->role->RLCode, [
                                                        'class' => 'select2 form-control',
                                                        'id' => 'role',
                                                        'placeholder' => trans('message.dropdown_default'),
                                                    ]) !!}
                                                    <label for="role">{{ __('Roles') }}<span
                                                            style="color:red">*</span></label>
                                                </div>
                                                <div class="input-field col m4 s6 form-group">
                                                    @if ($currentUser == 'EMBSTAFF')
                                                        <input type="hidden" name="isActive"
                                                            value="{{ $user->USActive }}">
                                                    @else
                                                        <input type="hidden" name="isActive" id="getIsActive"
                                                            value="{{ $user->USActive }}">
                                                    @endif
                                                    <i class="material-icons prefix">blur_on</i>
                                                    {!! Form::select('', $isActive, $user->USActive ?? null, [
                                                        'id' => 'isActive',
                                                        'class' => 'select2 form-control',
                                                        'placeholder' => trans('message.dropdown_default'),
                                                    ]) !!}
                                                    <label for="isActive">{{ __('Status Active') }}<span
                                                            style="color:red">*</span></label>
                                                </div>
                                            </div>
                                            <div class="input-field col m4 s6 form-group" id="checkboxes">
                                                <div id="view-checkboxes">
                                                    <p class="mb-1">
                                                        <label>
                                                            <input type="checkbox" name="resetPassword" value="1" />
                                                            <span>{{ __('Reset Password') }}</span>
                                                        </label>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="input-field col m4 s6 form-group">
                                                <i class="material-icons prefix">lock</i>
                                                {{ Form::password('password', ['id' => 'password', 'class' => 'form-control']) }}
                                                <label for="password">{{ __('New Password') }}</label>
                                            </div>
                                            <div class="input-field col m4 s6 form-group">
                                                <i class="material-icons prefix">lock</i>
                                                {{ Form::password('password-confirm', ['id' => 'password-confirm', 'class' => 'form-control']) }}
                                                <label for="password-confirm">{{ __('Confirm Password') }}</label>
                                            </div>

                                            <br />
                                            <div class="row">
                                                <div class="col s12 m12 l12" style="text-align:right; ">
                                                    <a class="btn gradient-45deg-green-teal"
                                                        href="{{ route('masterData.staff.index') }}">{{ __('Back') }}</a>
                                                    <button class="btn btn-primary"
                                                        id="save">{{ __('Submit') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
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

        // Disable the embassy option
        @if ($currentUser == 'EMBADMIN')
            $('#embassy').prop('disabled', true);
            $('#role').prop('disabled', true);
        @elseif ($currentUser == 'EMBSTAFF')
            $('#embassy').prop('disabled', true);
            $('#role').prop('disabled', true);
            $('#isActive').prop('disabled', true);
        @endif

        // Get the embassy and role
        $('#embassy').on('change', function() {
            var embassy = $(this).val();
            $('#getEmbassy').val(embassy);
        });

        $('#role').on('change', function() {
            var role = $(this).val();
            $('#getRole').val(role);
        });

        $('#isActive').on('change', function() {
            var isActive = $(this).val();
            $('#getIsActive').val(isActive);
        });
    </script>
@endpush
