@if(isset($user))
    <form class="ajax-form"  method="POST" action="{{ route('setting.user.update') }}" enctype="multipart/form-data">
@else
    <form class="ajax-form"  method="POST" action="{{ route('setting.user.store') }}" enctype="multipart/form-data">
@endif

    @csrf
    <div class="row">
        <div class="input-field col l14 m4 s12 ">
            {!! Form::text('USCode', $user->USCode ?? null , [
                'id' => 'USCode',
                'class' => 'form-control',
                'autocomplete' => 'off',

                isset($user) ? 'readonly' : 'disabled'
//                'placeholder' => 'Kosongkan untuk dijana secara automatik'
            ]) !!}
            <label for="USCode" class="col-md-4 col-form-label text-md-right">{{ __('Kod Pengguna') }} <span style="color:red">*</span></label>
        </div>

        <div class="input-field col l18 m8 s12 ">
            {!! Form::text('USName', $user->USName ?? null , [
                'id' => 'USName',
                'class' => 'form-control',
                'autocomplete' => 'off'
            ]) !!}
            <label for="USName" class="col-md-4 col-form-label text-md-right">{{ __('Nama Pengguna') }} <span style="color:red">*</span></label>
        </div>
    </div>
    <div class="row">
        <div class="input-field col l14 m4 s12 ">
            {!! Form::text('USPhoneNo', $user->USPhoneNo ?? null , [
                'id' => 'USPhoneNo',
                'class' => 'form-control',
                'autocomplete' => 'off'
            ]) !!}
            <label for="USPhoneNo" class="col-md-4 col-form-label text-md-right">{{ __('No. Tel. Pengguna') }} <span style="color:red">*</span></label>
        </div>

        <div class="input-field col l14 m4 s12 ">
            {!! Form::text('USEmail', $user->USEmail ?? null , [
                'id' => 'USEmail',
                'class' => 'form-control',
                'autocomplete' => 'off'
            ]) !!}
            <label for="USEmail" class="col-md-4 col-form-label text-md-right">{{ __('Emel Pengguna') }} <span style="color:red">*</span></label>
        </div>

        <div class="input-field col l14 m4 s12 ">
            {!! Form::select('USActive', $statusActive , $user->USActive ?? null , [
                'id' => 'USActive',
                'class' => 'form-control',
                'placeholder' => trans('message.dropdown_default')
            ]) !!}
            <label for="USActive" class="col-md-4 col-form-label text-md-right">{{ __('Status') }} <span style="color:red">*</span></label>
        </div>
    </div>
    <div class="row">
        <div class="input-field col l14 m4 s12 ">
            {!! Form::text('US_FRNo', $user->US_FRNo ?? null , [
                'id' => 'US_FRNo',
                'class' => 'form-control',
                'autocomplete' => 'off',
                'readonly'
            ]) !!}
            <label for="US_FRNo" class="col-md-4 col-form-label text-md-right">{{ __('No Rujukan Gambar') }}</label>
        </div>

        <div class="input-field col l4 s12 m4">
            <div class="row">
                <div class="col l12 m12 s12">
                    <label for="meeting_time">{{ __('Gambar Pengguna') }}</label>
                </div>
                <div class="input-field col l12 m12 s12">
                    @if(isset($user) && $user->fileAttachHOD_USFP)
                        <a target="_blank" href="{{ route('file.view',[$user->fileAttachHOD_USFP->FAGuidID]) }}" class="waves-effect waves-light btn btn-light-primary">
                            <i class="material-icons left">visibility</i><span style="font-size: 14px">{{ __('Papar')}}</span>
                        </a>
                    @else
                        Tiada gambar pengguna ditemui. Sila memuat naik gambar.
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="row" >
        <div class="col s12">
            <div style="text-align:right; padding:5px">
                <a href="{{ route('setting.user.index') }}" class="waves-effect waves-light btn btn-secondary">
                    {{ __('Kembali')}}
                </a>
                <button type="submit" class="btn btn-primary">
                    {{ __('Simpan')}}
                </button>
            </div>
        </div>
    </div>
</form>
