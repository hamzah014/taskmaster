<form class="ajax-form"  method="POST" action="{{ route('setting.user.store.director') }}" enctype="multipart/form-data">
@csrf
    <div class="row">
        <div class="input-field col l12 m12 s12 ">
            {!! Form::text('CAUNo', 'COA00000001' , [
                'id' => 'CAUNo',
                'class' => 'form-control',
                'autocomplete' => 'off',
                'placeholder'=> 'COA00000001'
            ]) !!}
            <label for="USName" class="col-md-4 col-form-label text-md-right">{{ __('No Rujukan (CAUNo)') }} <span style="color:red">*</span></label>
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
