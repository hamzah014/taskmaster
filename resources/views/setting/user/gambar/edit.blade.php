@extends('layouts.app')

@push('css')
@endpush
@section('content')
    <div id="breadcrumbs-wrapper" class="pt-0" >
        <div class="col s12 breadcrumbs-left">
            <ol class="breadcrumbs mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home')}}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('Gambar')}}
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
                            <div class="row">
                                <div class="col s12">
                                    <span class="header-button">
                                        <h4 class="card-title">{{ __('Kemaskini Gambar')}}</h4>
                                    </span>
                                </div>
                                <form class="ajax-form"  method="POST" action="{{ route('setting.user.gambar.update') }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="userCode" id="userCode" value="{{ $user->USCode}}">
                                    <input type="hidden" name="type" id="type" value="{{ $type}}">
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
                                                            <center>
                                                                <a href="#approvalModal" class="modal-trigger waves-effect waves-light btn btn-primary">
                                                                    {{ __('Ambil Gambar Selfie')}}
                                                                </a>
                                                                <br>
                                                                <input type="hidden" id="picSelfie" name="picSelfie">
                                                                <canvas class="" id="canvas" width="640" height="480"></canvas>
                                                            </center>
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="approvalModal" class="modal modal-fixed-footer">
        <div class="modal-content">
            <h4>Pengecaman Muka</h4>
            <div class="row">	
                <center>
                    <div >
                        <div class="circle-frame">
                            <div id="countdown-container">
                                <p id="countdown-text"></p>
                            </div>
                            <div id="loader-container">
                                <div id="loader" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> <!-- Font Awesome spinning icon -->
                                    Loading...
                                </div>
                            </div>
                            <video id="video" width="100%" autoplay="true" playsinline></video>
                        </div>
                    </div>
                </center>
            </div>

        </div>
        <div class="modal-footer">
            <a href="#" class="modal-action modal-close new modal-trigger btn btn-secondary">{{ __('Kembali')}}</a>
            <button id="capture-btn" class="btn btn-primary">Capture Selfie</button>
        </div>
    </div>
@endsection
@push('script')
    <script>

        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const captureBtn = document.getElementById('capture-btn');
        const context = canvas.getContext('2d');

        // Get the canvas image data
        var imageData2 = context.getImageData(0, 0, canvas.width, canvas.height);

        // Check if there are any non-zero pixels in the image data
        var hasContent = imageData2.data.some(function (value) {
            return value !== 0;
        });

        if (hasContent) {
            $('#canvas').show();
            console.log("Canvas has content.");
        } else {
            $('#canvas').hide();
            console.log("Canvas is empty.");
        }

        navigator.mediaDevices.getUserMedia({ video: true })
            .then((stream) => {
                video.srcObject = stream;
            })
            .catch((error) => {
                console.error('Error accessing the camera: ', error);
            });

        captureBtn.addEventListener('click', () => {
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = canvas.toDataURL('image/png');

            var xxx = document.getElementById('picSelfie');

            xxx.value = imageData;

            var refNo = $('#refNo').val();
            var APNo = $('#APNo').val();

            var data = {
                imageData: imageData,
                refNo: refNo,
                APNo: APNo,
            }

            // Get the canvas image data
            var imageData2 = context.getImageData(0, 0, canvas.width, canvas.height);

            // Check if there are any non-zero pixels in the image data
            var hasContent = imageData2.data.some(function (value) {
                return value !== 0;
            });

            if (hasContent) {
                $('#canvas').show();
                console.log("Canvas has content.");
            } else {
                $('#canvas').hide();
                console.log("Canvas is empty.");
            }
        });

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
