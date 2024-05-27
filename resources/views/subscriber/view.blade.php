@extends('layouts.app')

@push('css')
    <style>
    </style>
@endpush
@section('content')

<div id="kt_app_content_container" class="app-container d-flex justify-content-center align-items-center">
	<div class=" mb-5 mb-xl-10 w-100">
		<div id="kt_account_settings_profile_details">
			<div class="card-body p-9 card bg-section card-no-border">

                <div class="row mb-5">
                    <div class="col-md-12">
                        <a href="{{ route('subscriber.index') }}" class="text-dark">
                            <i class="fas fa-chevron-left fs-2 text-dark"></i><b class="ps-3">Back</b>
                        </a>
                    </div>
                </div>

                <div class="row d-flex justify-content-center mb-5">

                    <input type="hidden" name="subsID" id="subsID" value="{{ $subscriber->RGIDNo }}">
                    <div class="col-md-3 card bg-fancy card-no-border p-5 text-light">

                        <div class="row d-flex flex-center mb-8 text-center ">
                            <div class="col-md-10 border-bottom border-light">
                                <h3 class="text-light fs-2x">Subscriber Info</h3>
                            </div>
                        </div>
                        <div class="row d-flex flex-center">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-12 mb-4">
                                        <h5 class="text-bold text-light">Identification/Register No. :</h5>
                                        <p>{{ $subscriber->RGIDNo ?? "-" }}</p>
                                    </div>
                                    <div class="col-md-12 mb-4">
                                        <h5 class="text-bold text-light">Name :</h5>
                                        <p>{{ $subscriber->RGName ?? "-" }}</p>
                                    </div>
                                    <div class="col-md-12 mb-4">
                                        <h5 class="text-bold text-light">Email :</h5>
                                        <p>{{ $subscriber->RGEmail ?? "-" }}</p>
                                    </div>
                                    <div class="col-md-12 mb-4">
                                        <h5 class="text-bold text-light">Phone No. :</h5>
                                        <p>{{ $subscriber->RGPhoneNo ?? "-" }}</p>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                    <div class="col-md-7 card bg-transparent card-no-border p-5">

                        <div class="row">
                            <div class="col">
                                <h3 class="text-dark">History Certificate</h3>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col">
                                <table class="dcms-table" id="historyCert-tab">
                                    <thead class="table-head">
                                        <th>No.</th>
                                        <th>Certificate Date</th>
                                        <th>Certificate No.</th>
                                        <th>Request Type</th>
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

@endsection
@push('script')
    <script>

        $(document).ready(function(){

            var table = $('#historyCert-tab').DataTable();

            loadHistoryCert();


        });
        
        function loadHistoryCert(){

            toggleLoader();
            registerID = $('#subsID').val();
            var formData = new FormData();

            formData.append('registerID', registerID);

            if ($.fn.DataTable.isDataTable('#historyCert-tab')) {
                $('#historyCert-tab').DataTable().destroy();
            }

            $.ajax({
                url: "{{ route('subscriber.loadHistoryCert') }}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: false,
                data: formData,
                processData: false,
                cache: false,
                success: function (resp) {
                    console.log(resp);
                    toggleLoader();
            
                    var table = $('#historyCert-tab').DataTable({
                        dom: 'frtip',
                        searching: true,
                        @include('layouts._partials.lengthMenu')
                        processing: true,
                        serverSide: false,
                        data: resp.data,
                        columns: [
                            { name: 'indexNo', data: 'indexNo', class: 'text-center' },
                            { name: 'CEStartDate', data: 'CEStartDate', class: '' },
                            { name: 'CENo', data: 'CENo', class: '' },
                            { name: 'CE_CSCode', data: 'CE_CSCode', class: '' },
                        ],
                        fnDrawCallback: function(oSettings) {
                            var api = this.api();
                            api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                                cell.innerHTML = i + 1;
                            });
                        }
                    });
                    table.buttons().container().appendTo('.button-table-export');
                    
                    toggleLoader();
                    

                },
                error: function (xhr, status) {
                    toggleLoader();
                    var response = xhr.responseJSON;

                    if ( $.isEmptyObject(response.errors) )
                    {
                        var message = response.message;

                        if (! message.length && response.exception)
                        {
                            message = response.exception;
                        }

                        swal.fire("Warning", message, "warning");
                    }
                    else
                    {
                        var errors = '<p  id="fontSize" style="margin-top:2%; margin-bottom:1%; font-size: 25px;"><i>Invalid Information</i></p>';
                        $.each(response.errors, function (key, message) {
                            errors = errors;
                            errors += '<p style="margin-top:2%; margin-bottom:1%">'+message;
                            errors += '</p>';

                            if (key.indexOf('.') !== -1) {

                                var splits = key.split('.');

                                key = '';

                                $.each(splits, function(i, val) {
                                    if (i === 0)
                                    {
                                        key = val;
                                    }
                                    else
                                    {
                                        key += '[' + val + ']';
                                    }
                                });
                            }

                            // $('[name="' + key + '"]').closest('.form-group').addClass("has-error");
                            // $('[name="' + key + '"]').addClass("was-validated is-invalid invalid custom-select.is-invalid");
                            // $('#Valid'+key).empty();
                            // $('[name="' + key + '"]').closest('.form-group').append("<span id='Valid"+key+"' class=\"help-block\" style='color:red; font-family:Nunito, sans-serif;'>" + message[0] + "</span>");
                        });
                        swal.fire("Warning", errors, "warning",{html:true});
                        $('html, body').animate({
                            scrollTop: ($(".has-error").first().offset().top) - 200
                        }, 500);
                    }
                }
            });

        }
    </script>
@endpush
