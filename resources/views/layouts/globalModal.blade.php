
    <!-- HIDDEN BUTTON FOR OPEN AND CLOSE MODAL -->
    <a id="btnModalOpen" href="#uploadModal" style="display:none" class="new modal-trigger btn">Modal Iklan</a>
    <a id="btnModalClose" href="#!" style="display:none;" class="modal-action modal-close new modal-trigger btn">Modal Iklan</a>

    <!-- GLOBAL MODAL FOR UPLOAD FILE -->
    <form class="ajax-form" novalidate action="{{ route('file.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="modal fade" tabindex="-1" id="uploadModal">
            <div class="modal-dialog mw-1000px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">{{ __('Muat Naik Dokumen')}}</h3>

                        <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path4"></span></i>
                        </div>
                    </div>

                    <div class="modal-body">
                        
                        <div class="w-100">
                            <div class="fv-row mb-10 row">
                                <div class="col-md-12">
                                    <input class="form-control file-css" type="file" id="dok_upload" name="dok_upload" required>
                                    <span class="instruction"><i>Format fail yang dibenarkan ialah: .pdf, .jpg & .png. Saiz fail tidak melebihi 5MB.</i></span>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="dok_FAUSCode" id="dok_FAUSCode" value="">
                        <input type="hidden" name="dok_FARefNo" id="dok_FARefNo" value="">
                        <input type="hidden" name="dok_FAFileType" id="dok_FAFileType" value="">
                        <input type="hidden" name="dok_Type" id="dok_Type" value="">
                        <input type="hidden" name="dok_idRef" id="dok_idRef" value="">
                        <input type="hidden" name="dok_redirect" id="dok_redirect" value="{{ url()->current() }}">
                    </div>

                    <div class="modal-footer">
                        <button onclick="closeUploadModal()" type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Tutup')}}</button>
                        <button id="save" class="btn-sm fw-bold btn btn-primary">{{ __('Simpan')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>

        function openUploadModal(refNo,coNo,fileType, type = "", idRef = "" ,urls=""){

            $('#dok_FAUSCode').val(coNo);
            $('#dok_FARefNo').val(refNo);
            $('#dok_FAFileType').val(fileType);
            $('#dok_Type').val(type);
            $('#dok_idRef').val(idRef);
            $('#dok_upload').val("");

            if(urls != ""){
                $('#dok_redirect').val(urls);
            }

            // $('.modal').modal();

            // var targetModalId = $('#btnModalOpen').attr('href');
            // $(targetModalId).modal('open');


        }


        function closeUploadModal(){

            $('#dok_upload').val("");
            $('#dok_FAUSCode').val("");
            $('#dok_FARefNo').val("");
            $('#dok_FAFileType').val("");
            $('#dok_Type').val("");
            $('#dok_idRef').val("");

            // $('.modal').modal();

            // var targetModalId = $('#btnModalClose').attr('href');
            // $(targetModalId).modal('close');


        }

        function ajaxSubmitForm(form, callback) {

            $(form).on("submit", function (e) {
                e.preventDefault();

                urlAction = $(this).attr("action");

                var formData = new FormData(this);


                ajaxFormXHR = $.ajax({
                    url: urlAction,
                    type: 'POST',
                    contentType: false,
                    data: formData,
                    processData: false,
                    cache: false,
                    success: function (resp) {
                        console.log(resp);
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
                        else
                        {
                            var errors = '<p  id="fontSize" style="margin-top:2%; margin-bottom:1%; font-size: 25px;"><i>{{ __('Fail Tidak Berjaya Dimuat naik')}}</i></p>';
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

                                $('[name="' + key + '"]').closest('.form-group').addClass("has-error");
                                $('[name="' + key + '"]').addClass("was-validated is-invalid invalid custom-select.is-invalid");
                                $('#Valid'+key).empty();
                                $('[name="' + key + '"]').closest('.form-group').append("<span id='Valid"+key+"' class=\"help-block\" style='color:red; font-family:Nunito, sans-serif;'>" + message[0] + "</span>");
                            });
                            swal.fire('{{ __('Warning')}}', errors, 'warning',{html:true});

                            //SET BACK THE VALUE AND OPEN MODAL
                            var coNo    = $('#dok_FAUSCode').val();
                            var refNo     = $('#dok_FARefNo').val();
                            var fileType = $('#dok_FAFileType').val();
                            var type = $('#dok_Type').val();
                            var idRef = $('#dok_idRef').val();

                            openUploadModal(refNo,coNo,fileType, type, idRef)

                        }
                    }
                })
            });

        }
    </script>

