
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
                    @if(isset($country))
                        <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down"><span>{{ __('Edit Country')}}</span></h5>
                        <ol class="breadcrumbs mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('Home')}}</a>
                            </li>
                            <li class="breadcrumb-item"><a href="{{ route('masterData.country.index') }}">{{ __('Country')}}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ __('Edit Country')}}
                            </li>
                        </ol>
                    @else
                        <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down"><span>{{ __('New Country')}}</span></h5>
                        <ol class="breadcrumbs mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('Home')}}</a>
                            </li>
                            <li class="breadcrumb-item"><a href="{{ route('masterData.country.index') }}">{{ __('Country')}}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ __('New Country')}}
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
                                <div class="col m6 s6" style=" padding:5px"> <h4 class="card-title">{{ __('Country Entry')}}</h4></div>
                                {{--@if(isset($country))--}}
                                    {{--<form class="ajax-form" novalidate action="{{ route('masterData.employer.delete',[$employer->ERID]) }}" method="POST">--}}
                                    {{--<div class="col s6 m6 l6" style="text-align:right; padding:5px">--}}
                                        {{--<a type="button" class="btn red btn-danger" id="delete" data-id="{{$country->CTID}}"--}}
                                           {{--data-url="{{ route('masterData.country.delete',[$country->CTID]) }}">{{ __('Delete')}}</a>--}}
                                    {{--</div>--}}
                                {{--@endif--}}
                                @isset($country)
                                    <form class="ajax-form" novalidate action="{{ route('masterData.country.update',[$country->CTID]) }}" method="POST">
                                        @else
                                            <form class="ajax-form" novalidate action="{{ route('masterData.country.store') }}" method="POST">
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
                                                                                {!! Form::text('countryCode', $country->CTCode ?? null, ['id' => 'countryCode']) !!}
                                                                                <label for="countryCode">{{ __('Country Code')}}<span style="color:red">*</span></label>
                                                                            </div>
                                                                            <div class="input-field col m3 s6 form-group">
                                                                                {{--<i class="material-icons prefix">blur_on</i>--}}
                                                                                {!! Form::select('isActive', $isActive, $country->CTActive ?? null, ['id' => 'isActive', 'class' => 'select2 form-control', 'placeholder' => trans('message.dropdown_default')]) !!}
                                                                                <label for="isActive">{{ __('Status Active')}}<span style="color:red">*</span></label>
                                                                            </div>
                                                                            <div class="input-field col m12 s6 form-group">
                                                                                {{--<i class="material-icons prefix">person</i>--}}
                                                                                {!! Form::text('countryName', $country->CTDesc ?? null, ['id' => 'countryName']) !!}
                                                                                <label for="countryName">{{ __('Country Name')}}<span style="color:red">*</span></label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col s12 m12 l12" style="text-align:right; ">
                                                                <a class="btn gradient-45deg-green-teal" href="{{ route('masterData.country.index') }}">{{ __('Back')}}</a>
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