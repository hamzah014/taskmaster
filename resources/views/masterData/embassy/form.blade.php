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
                    @if (isset($embassy))
                        <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down">
                            <span>{{ __('Edit Embassy') }}</span>
                        </h5>
                        <ol class="breadcrumbs mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('Home') }}</a>
                            </li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('masterData.embassy.index') }}">{{ __('Embassy') }}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ __('Edit Embassy') }}
                            </li>
                        </ol>
                    @else
                        <h5 class="breadcrumbs-title mt-0 mb-0 display-inline hide-on-small-and-down">
                            <span>{{ __('New Embassy') }}</span>
                        </h5>
                        <ol class="breadcrumbs mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('Home') }}</a>
                            </li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('masterData.embassy.index') }}">{{ __('Embassy') }}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ __('New Embassy') }}
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
                                <div class="col m6 s6" style=" padding:5px">
                                    <h4 class="card-title">{{ __('Embassy Entry') }}</h4>
                                </div>
                                @isset($embassy)
                                    <form class="ajax-form" novalidate
                                        action="{{ route('masterData.embassy.update', [$embassy->EMID]) }}" method="POST">
                                    @else
                                        <form class="ajax-form" novalidate action="{{ route('masterData.embassy.store') }}"
                                            method="POST">
                                        @endisset
                                        <div class="row">
                                            <div class="col s12">
                                                <div class="row">
                                                    <div class="col s12">
                                                        <div class="card">
                                                            <div class="card-content">
                                                                <div class="row">
                                                                    <div class="input-field col m3 s6 form-group">
                                                                        {{-- <i class="material-icons prefix">person</i> --}}
                                                                        {!! Form::text('EMCode', $embassy->EMCode ?? null, [
                                                                            'id' => 'EMCode',
                                                                        ]) !!}
                                                                        <label for="EMCode">{{ __('Embassy Code') }}<span
                                                                                style="color:red">*</span></label>
                                                                    </div>
                                                                    <div class="input-field col m3 s6 form-group">
                                                                        {{-- <i class="material-icons prefix">blur_on</i> --}}
                                                                        {!! Form::select('EM_CTCode', $embassyCountry, $embassy->EM_CTCode ?? null, [
                                                                            'id' => 'EM_CTCode',
                                                                            'class' => 'select2 form-control',
                                                                            'placeholder' => trans('message.dropdown_default'),
                                                                        ]) !!}
                                                                        <label
                                                                            for="EM_CTCode">{{ __('Embassy Country') }}<span
                                                                                style="color:red">*</span></label>
                                                                    </div>
                                                                    <div class="input-field col m3 s6 form-group">
                                                                        {!! Form::select('EMActive', $isActive, $embassy->EMActive ?? null, [
                                                                            'id' => 'EMActive',
                                                                            'class' => 'select2 form-control',
                                                                            'placeholder' => trans('message.dropdown_default'),
                                                                        ]) !!}
                                                                        <label
                                                                            for="EMActive">{{ __('Status Active') }}<span
                                                                                style="color:red">*</span></label>
                                                                    </div>

                                                                    <div class="input-field col m12 s6 form-group">
                                                                        {{-- <i class="material-icons prefix">person</i> --}}
                                                                        {!! Form::text('EMName', $embassy->EMName ?? null, ['id' => 'EMName']) !!}
                                                                        <label for="EMName">{{ __('Embassy Name') }}<span
                                                                                style="color:red">*</span></label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col s12">
                                                        <div class="row">
                                                            <div class="col m6 s6" style=" padding:5px">
                                                                <h4 class="card-title">
                                                                    {{ __('Embassy Service Permission') }}
                                                                </h4>
                                                            </div>
                                                        </div>
                                                        <div class="card">
                                                            <div class="card-content">

                                                                <div class="row">
                                                                    {!! $checkedServices !!}
                                                                </div>
                                                                <div class="row">
                                                                    <div class="input-field col m3 s6 form-group">

                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col s12 m12 l12" style="text-align:right; ">
                                                        <a class="btn gradient-45deg-green-teal"
                                                            href="{{ route('masterData.embassy.index') }}">{{ __('Back') }}</a>
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
    </div>
@endsection

@push('script')
    <script>
        // Set to readonly if edit
        @isset($embassy)
            $('#EMCode').attr('readonly', true);
        @endisset
    </script>
@endpush
