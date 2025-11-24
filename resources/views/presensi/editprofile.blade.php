@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text light">
    <div class="left">
        <a href="/dashboard" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Edit Profile</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="row" style="margin-top: 5rem; margin-bottom: -3rem;">
    <div class="col text-center">
        @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
        @endif
    </div>
</div>
<form action="/presensi/{{$siswa->nisn}}/updateprofile" method="POST" enctype="multipart/form-data" style="margin-top: 4rem;">
    @csrf
    <div class="col">
        <div class="form-group boxed">
            <div class="input-wrapper">
                <input type="text" class="form-control" value="{{$siswa->nama_lengkap}}" name="nama_lengkap" placeholder="Nama Lengkap" autocomplete="off">
            </div>
        </div>
        <div class="form-group boxed">
            <div class="input-wrapper">
                <input type="text" class="form-control" value="{{$siswa->no_hp}}" name="no_hp" placeholder="No. HP" autocomplete="off">
            </div>
        </div>
        <div class="form-group boxed">
            <div class="input-wrapper">
                <input type="password" class="form-control" name="password" placeholder="Password" autocomplete="off">
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-12 text-center">
                <img id="webcamPreview" 
                    src="{{ asset('/storage/uploads/foto_siswa/'.$siswa->foto) }}" 
                    alt="Foto Profil" 
                    style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%; margin-bottom: 10px;">
            </div>
        </div>
        <div class="form-group boxed">
            <div class="input-wrapper">
                <button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#cameraModal">
                    <ion-icon name="camera-outline"></ion-icon>
                    Ambil Foto Langsung
                </button>
            </div>
        </div>
        <input type="hidden" name="foto_webcam" id="foto-webcam-input">

        <div class="custom-file-upload" id="fileUpload1">
            <input type="file" name="foto" id="fileuploadInput" accept=".png, .jpg, .jpeg">
            <label for="fileuploadInput">
                <span>
                    <strong>
                        <ion-icon name="cloud-upload-outline" role="img" class="md hydrated" aria-label="cloud upload outline"></ion-icon>
                        <i>Tap to Upload</i>
                    </strong>
                </span>
            </label>
        </div>
        <div class="form-group boxed">
            <div class="input-wrapper">
                <button type="submit" class="btn btn-primary btn-block">
                    <ion-icon name="refresh-outline"></ion-icon>
                    Update
                </button>
            </div>
        </div>
    </div>
</form>

<div class="modal fade" id="cameraModal" tabindex="-1" role="dialog" aria-labelledby="cameraModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cameraModalLabel">Ambil Foto Profil</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <video id="webcam-video" width="100%" height="auto" autoplay style="border: 1px solid #ccc;"></video>
                
                <canvas id="webcam-canvas" style="display:none;"></canvas>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="capture-button">
                    <ion-icon name="camera"></ion-icon>
                    Tangkap Foto
                </button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('myscripts')
<script>
    const webcamPreview = document.getElementById('webcamPreview'); 
    const video = document.getElementById('webcam-video');
    const canvas = document.getElementById('webcam-canvas');
    const captureButton = document.getElementById('capture-button');
    const fotoWebcamInput = document.getElementById('foto-webcam-input');
    const fileUploadInput = document.getElementById('fileuploadInput'); 
    const fileUploadContainer = document.getElementById('fileUpload1');
    const cameraModal = $('#cameraModal');
    let stream; 
    cameraModal.on('shown.bs.modal', function () {
        video.style.display = 'block'; 
        canvas.style.display = 'none';

        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } }) 
                .then(function (s) {
                    stream = s;
                    video.srcObject = s;
                    video.play();
                })
                .catch(function (error) {
                    alert('Gagal mengakses kamera: Pastikan Anda memberikan izin akses. Error: ' + error.message);
                });
        }
    });
    cameraModal.on('hidden.bs.modal', function () {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        video.srcObject = null; 
    });
    captureButton.addEventListener('click', function () {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const imageData = canvas.toDataURL('image/jpeg', 0.8); 
        fotoWebcamInput.value = imageData; 
        webcamPreview.src = imageData;
        fileUploadInput.value = ''; 
        fileUploadContainer.style.display = 'none'; 
        cameraModal.modal('hide');
        alert('Foto berhasil diambil dan siap di-update! Klik "Update" untuk menyimpan.');
    });
    fileUploadInput.addEventListener('change', function() {
        if (this.value) {
            fotoWebcamInput.value = ''; 
            fileUploadContainer.style.display = 'block'; 
        }
    });
</script>
@endpush