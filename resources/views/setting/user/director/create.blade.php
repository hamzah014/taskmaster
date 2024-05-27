@extends('layouts.app')

@push('css')
@endpush
@section('content')
    <div id="breadcrumbs-wrapper" class="pt-0" >
        <div class="col s12 breadcrumbs-left">
            <ol class="breadcrumbs mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home')}}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('Tambah Pengarah Syarikat')}}
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
                                        <h4 class="card-title">{{ __('Tambah Pengarah Syarikat')}}</h4>
                                    </span>
                                    <div class="row">
                                        <div class="col m12">
                                            <div class="tab" style="display:inline-flex !important;">
                                                <button class="tablinks" onclick="openTab(event, 'maklumatPengguna')" id="defaultOpen">Maklumat Pengguna</button></br>
                                                <button class="tablinks" disabled onclick="openTab(event, 'maklumatGambar')">Maklumat Gambar</button></br>
                                            </div>
                                        </div>
                                        <div class="col s12">
                                            <table >
                                                <tr>
                                                    <td width="90%" style="vertical-align:top;">
                                                        <div id="maklumatPengguna" class="tabcontent">
                                                            <div class="box-title">Maklumat Pengguna</div>
                                                            <div class="row">
                                                                <div class="col m12 s12">
                                                                    @include('setting.user.director.maklumatPenggunaDirector')
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div id="maklumatGambar" class="tabcontent">
                                                            <div class="box-title">Maklumat Gambar</div>
                                                            <div class="row">
                                                                <div class="col m12 s12">
{{--                                                                @include('setting.user.include.maklumatGambar')--}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
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
    </div>
@endsection
@push('script')
    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        // #FLAG-OPEN-TAB
        function openTabByIndex(index) {
            var tabcontent = document.getElementsByClassName("tabcontent");
            for (var i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }

            var tablinks = document.getElementsByClassName("tablinks");
            for (var i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }

            tabcontent[index].style.display = "block";
            tablinks[index].className += " active";
        }

        // Get the flag value from the URL - #FLAG-OPEN-TAB
        var urlParams = new URLSearchParams(window.location.search);
        var flag = urlParams.get('flag');

        // Convert the flag to an integer and use it to open the corresponding tab
        if (flag !== null || flag==0) {
            var flagNumber = parseInt(flag, 10);
            openTabByIndex(flagNumber); // Assuming the flag corresponds to the tab index (1-based)
        } else {
            // Default behavior, you can adjust this as needed
            document.getElementById("defaultOpen").click();
        }

    </script>
@endpush
