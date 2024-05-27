
    <div class="modal fade" tabindex="-1" id="jemputanModal" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl">
            
            <form class="ajax-form" action="#" method="POST" id="formJemputan"enctype="multipart/form-data">

                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">{{ __('Maklumat Jemputan')}}</h3>

                        <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path4"></span></i>
                        </div>
                    </div>

                    <div class="modal-body">
                                    
                        <input type="hidden" id="meetingENo" name="meetingNo" value="0">
                        <input type="hidden" id="meetingEType"  name="meetingType" value="0">
                        <input type="hidden" id="meetingEURL"  name="meetingURL" value="0">
                        <div id="jemputanContent">

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm fw-bold" data-bs-dismiss="modal">{{ __('Tutup')}}</button>
                        <button id="globalMeetingSubmit" type="submit" class="btn-sm fw-bold btn btn-primary">{{ __('Hantar Jemputan')}}</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

<script>

    function openJemputanModal(meetingNo,meetingType, meetingURL = 0){

        console.log(meetingNo, meetingType, meetingURL);

        $('#meetingENo').val(meetingNo);
        $('#meetingEType').val(meetingType);
        $('#meetingEURL').val(meetingURL);

        var formData = new FormData();
        formData.append('meetingNo', meetingNo);
        formData.append('meetingType', meetingType);
        formData.append('meetingURL', meetingURL);

        route = '{{ route('perolehan.mesyuarat.getMeetingEmailList') }}';
        routeUpdate = "{{ route('perolehan.mesyuarat.updateMeetingEmailList') }}";

        document.getElementById('formJemputan').setAttribute('action', routeUpdate);
        
        $.ajax({
            url: route,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            contentType: false,
            data: formData,
            processData: false,
            cache: false,
            success: function (resp) {
                $('#jemputanContent').html(resp);
            },
            error: function (xhr, status) {
                var response = xhr.responseJSON;

                if ( $.isEmptyObject(response.errors) )
                {
                    var message = response.message;

                    if (! message.length && response.exception)
                    {
                        message = response.exception;
                    }

                    swal.fire("{{ __('Warning')}}", message, "warning");
                }
            }
        });

    }

</script>

