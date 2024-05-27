
<div class="modal fade" tabindex="-1" id="komenModal">
    <div class="modal-dialog mw-1000px">

        <form class="ajax-form" action="{{ route('comment.store') }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">{{ __('Tambah Semak Semula')}}</h3>

                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path4"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="komenRefNo" name="komenRefNo" value="0">
                    <input type="hidden" id="komenType"  name="komenType" value="0">
                    <input type="hidden" id="komenUrlId"  name="komenUrlId" value="0">
                    <div class="w-100">
                        <div class="fv-row mb-5">
                            <label class="form-label required">{{ __('Keterangan') }}</label>
                            {!! Form::textarea('review_description', null, [
                                'id' => 'review_description',
                                'class' => 'form-control',
                                'required' => 'required',
                            ]) !!}
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Tutup')}}</button>
                    <button type="submit" class="btn-sm fw-bold btn btn-primary">{{ __('Simpan')}}</button>
                </div>
            </div>
        </form>

    </div>
</div>

<script>

    function openKomenModal(refNo,komenType, urlId = 0){

        console.log(refNo,komenType, urlId);

        $('#komenRefNo').val(refNo);
        $('#komenType').val(komenType);
        $('#komenUrlId').val(urlId);

        M.textareaAutoResize($('textarea'));
    }

</script>

