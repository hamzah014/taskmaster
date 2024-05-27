
    <!-- HIDDEN BUTTON FOR OPEN AND CLOSE MODAL -->
    <a id="btnModalOpen" href="#notifModal" style="display:none" class="new modal-trigger btn">Modal OPen</a>
    <a id="btnModalClose" href="#!" style="display:none;" class="modal-action modal-close new modal-trigger btn">Modal Close</a>

    <!-- GLOBAL MODAL FOR NOTIFICATION DETAILS -->
    <div class="modal fade" tabindex="-1" id="notifModal">
        <div class="modal-dialog mw-1000px">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">{{ __('Maklumat Notifikasi')}}</h3>

                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path4"></span></i>
                    </div>
                </div>

                <div class="modal-body">

                    <div id="notDesc">
                    <p>...................</p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Tutup')}}</button>
                </div>
            </div>
        </div>
    </div>

    <script>

        function openNotifModal(id){

            console.log('ids = ' + id);
            var formData = new FormData();

            formData.append('notiID',id);

            $.ajax({
                url: "{{ route('notification.update.getNotification') }}",
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

                    if(resp.status == 1){
                        $('#notTitle').html(resp.data.NOTitle);
                        $('#notDesc').html(resp.data.NODescription);
                    }else{
                        
                        swal.fire("{{ __('Warning')}}", resp.message, "warning");
                    }
                    notificationDatatable();

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

        function closeNotifModal(){

            if ($.fn.DataTable.isDataTable('table')) {
                // If it exists, destroy it first
                $('table').DataTable().destroy();
            }
                    
            notificationDatatable();

        }
        

    </script>

