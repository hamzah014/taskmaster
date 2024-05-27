@extends('layouts.appKewangan')

@push('css')
    <style>
    </style>
@endpush
@section('content')
<div class="row">
    <div class="col s12">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col s3" >
                    <div class="card border-radius-6 login-card bg-opacity-8" style="margin: 2rem 0 2rem 0 !important; min-height:210px">
                        <div class="card-content">
                            <div class="row">
                                <div class="col s12">
                                    <label >RefNo</label>
                                    {!! Form::text('refNo', null , [
                                        'id' => 'refNo',
                                        'class' => 'form-control',
                                        'autocomplete' => 'off',
                                        'placeholder' => 'PKJME'
                                    ]) !!}
                                    <button id="capture-btn" class="btn waves-effect waves-light gradient-45deg-brown-brown col s12">Capture Selfie</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <video id="video" width="640" height="480" autoplay="true" playsinline></video>

                <canvas id="canvas" width="640" height="480"></canvas>

                <div id="image1">
                </div>
                <div id="image2">
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('script')
{{--    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>--}}
{{--    <script src="https://cdn.jsdelivr.net/npm/face-api.js"></script>--}}

{{--    <script>--}}
{{--        document.addEventListener('DOMContentLoaded', function () {--}}
{{--            const video = document.getElementById('video');--}}
{{--            const canvas = document.getElementById('canvas');--}}
{{--            const captureBtn = document.getElementById('capture-btn');--}}
{{--            const context = canvas.getContext('2d');--}}

{{--            navigator.mediaDevices.getUserMedia({ video: true })--}}
{{--                .then((stream) => {--}}
{{--                    video.srcObject = stream;--}}
{{--                })--}}
{{--                .catch((error) => {--}}
{{--                    console.error('Error accessing the camera: ', error);--}}
{{--                });--}}

{{--            captureBtn.addEventListener('click', () => {--}}
{{--                context.drawImage(video, 0, 0, canvas.width, canvas.height);--}}
{{--                const imageData = canvas.toDataURL('image/png');--}}

{{--                // Use face-api.js to detect faces and eyes in the captured image--}}
{{--                faceapi.detectAllFaces(video).withFaceLandmarks().then((detections) => {--}}
{{--                    if (detections.length > 0) {--}}
{{--                        const landmarks = detections[0].landmarks._positions;--}}
{{--                        const eyeAspectRatio = getEyeAspectRatio(landmarks);--}}

{{--                        // Customize the blink detection threshold based on your needs--}}
{{--                        if (eyeAspectRatio < 0.2) {--}}
{{--                            console.log('Blink detected!');--}}

{{--                            // Continue with your code for capturing and processing the image--}}
{{--                            var refNo = $('#refNo').val();--}}

{{--                            var data = {--}}
{{--                                imageData: imageData,--}}
{{--                                refNo: refNo,--}}
{{--                            }--}}

{{--                            // Send the image data to the Laravel controller--}}
{{--                            fetch('{{ route("webcam.store") }}', {--}}
{{--                                method: 'POST',--}}
{{--                                headers: {--}}
{{--                                    'Content-Type': 'application/json',--}}
{{--                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content--}}
{{--                                },--}}
{{--                                body: JSON.stringify({ data })--}}
{{--                            })--}}
{{--                                .then(response => response.json())--}}
{{--                                .then(data => {--}}
{{--                                    console.log('Server response:', data);--}}
{{--                                    // Your existing code for displaying images--}}
{{--                                    var imageElement = document.createElement('img');--}}
{{--                                    imageElement.src = 'data:image/png;base64, ' + data.image1;--}}
{{--                                    imageElement.alt = 'Red dot';--}}

{{--                                    var divElement = document.getElementById('image1');--}}
{{--                                    divElement.innerHTML = '';--}}
{{--                                    divElement.appendChild(imageElement);--}}

{{--                                    var imageElement = document.createElement('img');--}}
{{--                                    imageElement.src = data.image2;--}}

{{--                                    var divElement = document.getElementById('image2');--}}
{{--                                    divElement.appendChild(imageElement);--}}
{{--                                })--}}
{{--                                .catch(error => {--}}
{{--                                    console.error('Error sending image data:', error);--}}
{{--                                });--}}
{{--                        }--}}

{{--                        else{--}}
{{--                            console.log('No eyeAspectRatio');--}}
{{--                        }--}}
{{--                    }--}}
{{--                    else{--}}
{{--                        console.log('No detections');--}}
{{--                    }--}}
{{--                });--}}
{{--            });--}}

{{--            // Helper function to calculate eye aspect ratio--}}
{{--            function getEyeAspectRatio(landmarks) {--}}
{{--                const leftEye = landmarks[42].y - landmarks[39].y + landmarks[47].y - landmarks[36].y;--}}
{{--                const rightEye = landmarks[45].y - landmarks[42].y + landmarks[46].y - landmarks[43].y;--}}
{{--                const eyeAspectRatio = (leftEye + rightEye) / (2 * (landmarks[45].x - landmarks[42].x));--}}

{{--                return eyeAspectRatio;--}}
{{--            }--}}
{{--        });--}}
{{--    </script>--}}

<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const captureBtn = document.getElementById('capture-btn');
    const context = canvas.getContext('2d');

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
        var refNo = $('#refNo').val();

        var data = {
            imageData: imageData,
            refNo: refNo,
        }

        // Send the image data to the Laravel controller
        fetch('{{ route("webcam.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ data })

        })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data);
                if(data.success == true){
                    if(data.facePass == true){
                        $('#faceScore').val(data.faceScore);
                        // $('#btnSubmit').click()
                    }
                    else{
                        swal.fire({
                            title: "Amaran",
                            text: "Pengecaman Muka Tidak berjaya. Markah Anda Hanya: " +data.faceScore+ "/100",
                            icon: "warning",
                            showCancelButton: false,
                            confirmButtonText: "Ok",
                        }).then((result) => {
                        });
                    }
                }
                else{
                    swal.fire({
                        title: "Amaran",
                        text: "Muka tidak wujud didalam sistem.Sila Daftarkan Muka",
                        icon: "warning",
                        showCancelButton: false,
                        confirmButtonText: "Ok",
                    }).then((result) => {
                    });
                }

                // var imageElement = document.createElement('img');
                // imageElement.src = 'data:image/png;base64, ' + data.image1;
                // imageElement.alt = 'Red dot';
                //
                // var divElement = document.getElementById('image1');
                // divElement.innerHTML = '';
                // divElement.appendChild(imageElement);
                //
                // var imageElement = document.createElement('img');
                // imageElement.src = data.image2;
                //
                // var divElement = document.getElementById('image2');
                // divElement.appendChild(imageElement);
            })
            .catch(error => {
                console.error('Error sending image data:', error);
            });
    });
</script>
@endpush
