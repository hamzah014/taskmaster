
@extends('layouts.app')

@push('css')

@endpush
@section('content')
    <meta name="my.message.yes" content="{{ __('messages.yes')}}">
    <div class="content-wrapper-before gradient-45deg-deep-purple-purple"></div>
    <div class="breadcrumbs-inline pt-3 pb-1" id="breadcrumbs-wrapper">
        <!-- Search for small screen-->
        <div class="container">
            <div class="row">
                <div class="col s10 m6 l6 breadcrumbs-left">
                    @if(isset($state))
                        <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down"><span>{{ __('Edit State')}}</span></h5>
                        <ol class="breadcrumbs mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('Home')}}</a>
                            </li>
                            <li class="breadcrumb-item"><a href="{{ route('masterData.state.index') }}">{{ __('State')}}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ __('Edit State')}}
                            </li>
                        </ol>
                    @else
                        <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down"><span>{{ __('New State')}}</span></h5>
                        <ol class="breadcrumbs mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('Home')}}</a>
                            </li>
                            <li class="breadcrumb-item"><a href="{{ route('masterData.state.index') }}">{{ __('State')}}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ __('New State')}}
                            </li>
                        </ol>
                    @endif
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
                                <div class="col m6 s6" style=" padding:5px"> <h4 class="card-title">{{ __('State Entry')}}</h4></div>
                                {{--@if(isset($state))--}}
                                    {{--<form class="ajax-form" novalidate action="{{ route('masterData.employer.delete',[$employer->ERID]) }}" method="POST">--}}
                                    {{--<div class="col s6 m6 l6" style="text-align:right; padding:5px">--}}
                                        {{--<a type="button" class="btn red btn-danger" id="delete" data-id="{{$state->StateID}}"--}}
                                           {{--data-url="{{ route('masterData.state.delete',[$state->StateID]) }}">{{ __('Delete')}}</a>--}}
                                    {{--</div>--}}
                                {{--@endif--}}
                                @isset($state)
                                    <form class="ajax-form" novalidate action="{{ route('masterData.state.update',[$state->StateID]) }}" method="POST">
                                        @else
                                            <form class="ajax-form" novalidate action="{{ route('masterData.state.store') }}" method="POST">
                                                @endisset
                                                <div class="row">
                                                    <div class="col s12">
                                                        <div class="row">
                                                            <div class="col s12">
                                                                <div class="card">
                                                                    <div class="card-content">
                                                                        <div class="row">
                                                                            <div class="input-field col m3 s6 form-group">
                                                                                {{--<i class="material-icons prefix">person</i>--}}
                                                                                {!! Form::text('stateCode', $state->StateCode ?? null, ['id' => 'stateCode']) !!}
                                                                                <label for="stateCode">{{ __('State Code')}}<span style="color:red">*</span></label>
                                                                            </div>
                                                                            <div class="input-field col m3 s6 form-group">
                                                                                {{--<i class="material-icons prefix">blur_on</i>--}}
                                                                                {!! Form::select('isActive', $isActive, $state->StateActive ?? null, ['id' => 'isActive', 'class' => 'select2 form-control', 'placeholder' => trans('message.dropdown_default')]) !!}
                                                                                <label for="isActive">{{ __('Status Active')}}<span style="color:red">*</span></label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row">
                                                                            <div class="input-field col m6 s6 form-group">
                                                                                {{--<i class="material-icons prefix">doc</i>--}}
                                                                                {!! Form::select('country', $country , isset($state) ? $curCountry->CTCode : null,
                                                                                    ['id' => 'country', 'class' => 'select2 form-control', 'placeholder' => trans('message.dropdown_default')]) !!}
                                                                                <label for="country">{{ __('Country')}}<span style="color:red">*</span></label>
                                                                            </div>
                                                                            <div class="input-field col m6 s6 form-group">
                                                                                {{--<i class="material-icons prefix">person</i>--}}
                                                                                {!! Form::text('stateDesc', $state->StateDesc ?? null, ['id' => 'stateDesc']) !!}
                                                                                <label for="stateDesc">{{ __('State Name')}}<span style="color:red">*</span></label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col s12 m12 l12" style="text-align:right; ">
                                                                <a class="btn gradient-45deg-green-teal" href="{{ route('masterData.state.index') }}">{{ __('Back')}}</a>
                                                                <button class="btn btn-primary" id="save">{{ __('Submit')}}</button>
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
    </div>
@endsection
{{--@push('script')--}}
{{--<script type="text/javascript">--}}
{{--function preview_image(event)--}}
{{--{--}}
{{--var reader = new FileReader();--}}
{{--reader.onload = function()--}}
{{--{--}}
{{--var output = document.getElementById('profile-img-tag');--}}
{{--output.src = reader.result;--}}
{{--}--}}
{{--reader.readAsDataURL(event.target.files[0]);--}}
{{--}--}}

{{--</script>--}}

{{--@endpush--}}