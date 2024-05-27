<form class="ajax-form"  method="POST" action="{{ route('setting.user.update.gambar') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="userCode" id="userCode" value="{{ $user->USCode}}">
    <div class="row">
        <div class="col s12 m12 l6 form-group">
            <div class="card border-radius-6 login-card bg-opacity-8" >
                <div class="card-content form-group">
                    <div class="">
                        <div class="input-field">
                            <p>{{ __('Gambar Kad Pengenalan') }}:</p>
                            <input class="form-control file-css" type="file" id="dok_ic" name="dok_ic" accept="image/*" required>
                            </br><span class="instruction"><i>Format fail yang dibenarkan ialah: .jpg & .png. Saiz fail tidak melebihi 5MB.</i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col s12 m12 l6 form-group">
            <div class="card border-radius-6 login-card bg-opacity-8" >
                <div class="card-content form-group">
                    <div class="">
                        <div class="input-field">
                            <p>{{ __('Gambar Pengguna') }}:</p>
                            <input class="form-control file-css" type="file" id="dok_picture" name="dok_picture" accept="image/*" required>
                            </br><span class="instruction"><i>Format fail yang dibenarkan ialah: .jpg & .png. Saiz fail tidak melebihi 5MB.</i></span>
                        </div>
                    </div>
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
<div class="row">
    <div class="col s12 m12 l12 form-group">
        <table class="speed_mini" id="face-table" style="width: 100%">
            <thead>
            <tr>
                <th>No. Rujukan Gambar</th>
                <th>Markah</th>
                <th>Sepadan</th>
                <th>Tarikh Proses</th>
                <th>Catatan</th>
            </tr>
            </thead>
        </table>
    </div>
</div>
@push('script')
    <script>

        (function ($) {
            var userCode = $('#userCode').val();

            var table = $('#face-table').DataTable({
                dom: 'lfrtip',
                @include('layouts._partials.lengthMenu')
                processing: true,
                serverSide: false,
                ordering:false,
                ajax:  {
                    "url" :"{{ route('setting.user.faceDatatable') }}",
                    "method": "POST",
                    "headers": {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    "data": {
                        userCode: userCode // Include the idClaim value in the data object
                    }
                },
                columns: [
                    { name: 'FRNo', data: 'FRNo', class: 'text-center'},
                    { name: 'FRFaceScore', data: 'FRFaceScore', class: 'text-center' },
                    { name: 'FRMatchComplete', data: 'FRMatchComplete', class: 'text-center' },
                    { name: 'FRMatchDate', data: 'FRMatchDate', class: 'text-center' },
                    { name: 'FRUnmatchDesc', data: 'FRUnmatchDesc', class: 'text-center' },
                ]
            });
            table.buttons().container().appendTo('.button-table-export');

        })(jQuery);
    </script>
@endpush
