@extends('layouts.appSetting')

@push('css')
@endpush
@section('content')
    <div id="breadcrumbs-wrapper" class="pt-0" >
        <div class="col s12 breadcrumbs-left">
            <ol class="breadcrumbs mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home')}}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('Senarai Jabatan')}}
                </li>
            </ol>
        </div>
    </div>
    <div class="section">
        <div class="section">
            <div class="row" >
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <form class="ajax-form"  method="POST" action="{{ route('setting.department.update') }}" enctype="multipart/form-data">
                            @csrf
                                <div class="row">
                                    <div class="col s12">
                                        <span class="header-button">
                                            <h4 class="card-title">{{ $department->DPTDesc }}</h4>
                                        </span>
                                    </div>
                                </div>
                                <input type="hidden" name="DPTID" id="DPTID" value="{{ $department->DPTID}}">
                                <div class="row">
                                    <div class="input-field col l14 m4 s12 ">
                                        {!! Form::text('DPTCode', $department->DPTCode ?? null , [
                                            'id' => 'DPTCode',
                                            'class' => 'form-control',
                                            'autocomplete' => 'off'
                                        ]) !!}
                                        <label for="DPTCode" class="col-md-4 col-form-label text-md-right">{{ __('Kod Jabatan') }} <span style="color:red">*</span></label>
                                    </div>

                                    <div class="input-field col l18 m8 s12 ">
                                        {!! Form::text('DPTDesc', $department->DPTDesc ?? null , [
                                            'id' => 'DPTDesc',
                                            'class' => 'form-control',
                                            'autocomplete' => 'off'
                                        ]) !!}
                                        <label for="DPTDesc" class="col-md-4 col-form-label text-md-right">{{ __('Nama Jabatan') }} <span style="color:red">*</span></label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-field col l14 m4 s12 ">
                                        {!! Form::text('DPTEmail', $department->DPTEmail ?? null , [
                                            'id' => 'DPTEmail',
                                            'class' => 'form-control',
                                            'autocomplete' => 'off'
                                        ]) !!}
                                        <label for="DPTEmail" class="col-md-4 col-form-label text-md-right">{{ __('Emel Jabatan') }} <span style="color:red">*</span></label>
                                    </div>

                                    <div class="input-field col l14 m4 s12 ">
                                        {!! Form::select('DPTHead_USCode', $user , $department->DPTHead_USCode ?? null , [
                                            'id' => 'DPTHead_USCode',
                                            'class' => 'form-control',
                                            'placeholder' => trans('message.dropdown_default')
                                        ]) !!}
                                        <label for="DPTHead_USCode" class="col-md-4 col-form-label text-md-right">{{ __('Ketua Jabatan') }} <span style="color:red">*</span></label>
                                    </div>

                                    <div class="input-field col l14 m4 s12 ">
                                        {!! Form::select('DPTActive', $statusActive , $department->DPTActive ?? null , [
                                            'id' => 'DPTActive',
                                            'class' => 'form-control',
                                            'placeholder' => trans('message.dropdown_default')
                                        ]) !!}
                                        <label for="DPTActive" class="col-md-4 col-form-label text-md-right">{{ __('Status') }} <span style="color:red">*</span></label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-field col l4 s12 m4">
                                        <div class="row">
                                            <div class="col l12 m12 s12">
                                                <label for="meeting_time">{{ __('Gambar Ketua Jabatan') }}</label>
                                            </div>
                                            <div class="input-field col l12 m12 s12">
                                                @if($department->user->fileAttachHOD_USFP)
                                                    <a target="_blank" href="{{ route('file.view',[$department->user->fileAttachHOD_USFP->FAGuidID]) }}" class="waves-effect waves-light btn btn-light-primary">
                                                        <i class="material-icons left">visibility</i><span style="font-size: 14px">{{ __('Papar')}}</span>
                                                    </a>
                                                @else
                                                    Tiada gambar ketua jabatan ditemui. Mohon ketua jabatan memuat naik gambar.
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" >
                                    <div class="col s12">
                                        <div style="text-align:right; padding:5px">
                                            <a href="{{ route('setting.department.index') }}" class="waves-effect waves-light btn btn-secondary">
                                                {{ __('Kembali')}}
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                {{ __('Simpan')}}
                                            </button>
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
@endsection
@push('script')
    <script>

    </script>
@endpush
