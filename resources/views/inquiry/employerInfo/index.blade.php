@extends('layouts.app')
@push('css')
@endpush
@section('content')
    <div class="section index-div">
        <div id="inline-form" class="card card card-default scrollspy">
            <div class="card-content">
                <div class="row">
                <div class="col l12">
                    <form id="report-form">
                        <h5 >{{ __('Employer Info')}}</h5></br>
                        <div class="row">
							<div class="col s12 m6 l4 form-group">
								<label for="fullName">{{ __('Company Name')}} </label>
								{!! Form::text('compName', '', ['id' => 'compName', 'class' => 'form-control']) !!}
							</div>
							<div class="col s12 m6 l4 form-group">
								<label for="ssmNo">{{ __('SSM No')}} </label>
								{!! Form::text('ssmNo', '', ['id' => 'ssmNo', 'class' => 'form-control']) !!}
							</div>
                            <div class="form-row">
                                <div class="col s12 m12 l12" style="text-align:right;">
                                    <button class="btn btn-primary" id="submit">{{ __('Search')}}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                </div>
            </div>
        </div>

    </div>

    <div class="section" id="report-detail" style="display:none">
        <div class="card card-tabs">
            <div class="card-content">
                <div class="card-title">
                    <div class="row">
                        <div class="col s12 m12 l12" style="text-align:right;">
                            <button id="backDet" type="button" class="btn waves-effect waves-light gradient-45deg-green-teal" style="float: left">{{ __('Back')}}</button>
                       </div>
                    </div>
                </div>
                <div class="section">
                    <div class="row">
                        <div class="col s12">
							<table id="page-detail" class="display responsive-table" style=" width:100%; max-height: 1500px;overflow-x: auto;">
								<thead>
									<tr>
									<th class="dt-head-center" width="5px">No</th>
									<th class="dt-head-center">{{ __('Company Name')}}</th>
									<th class="dt-head-center">{{ __('SSM No')}}</th>
									<th class="dt-head-center">{{ __('Tel No')}}</th>
									<th class="dt-head-center">{{ __('Fax No')}}</th>
									<th class="dt-head-center">{{ __('Email')}}</th>
									</tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

	

	
@endsection
@push('script')
    <script type="text/javascript">
	
        (function ($) {
			
            $('#report-detail').fadeOut();

            searching = false;
			
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };

            $('form').on('submit', function (e) {
                e.preventDefault();
                $(".form-group").removeClass("has-error");
                $(".form-control").removeClass("was-validated invalid is-invalid custom-select.is-invalid valid is-valid custom-select.is-valid");
                $(".form-group").children("span.help-block").remove();

                toggleLoader();
				
                $.ajax({
                    type: 'POST',
                    url: "{!! route('inquiry.employerInfo.validation') !!}",
                    data: $('form').serialize(),
                    success: function ($response) {
				
                        $('.index-div').hide();
						
						$('#page-detail').DataTable().destroy();
						$('#report-detail').fadeIn();
						// Datatable settings
						
						// DETAIL 
						var configDet = ConfigDTGlobal;
									
						configDet["columns"] = [
								{ data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, class: 'dt-body-center' },
								{ data: 'compName', name: 'compName', class: 'dt-body-left' },
								{ data: 'ssmNo', name: 'ssmNo', class: 'dt-body-left' },
								{ data: 'compTelNo', name: 'compTelNo', class: 'dt-body-left' },
								{ data: 'compFaxNo', name: 'compFaxNo', class: 'dt-body-left' },
								{ data: 'compEmail', name: 'compEmail', class: 'dt-body-left' },
						];
						
						 configDet["language"] =  {
								search :  "{{trans('datatable.General.search')}}",
								searchPlaceholder : "{{trans('datatable.General.searchPlaceholder')}}",
								zeroRecords: "{{trans('datatable.General.zeroRecords')}}",
								emptyTable:  "{{trans('datatable.General.emptyTable')}}",
								info : "{{trans('datatable.General.info')}}",
								infoEmpty : "{{trans('datatable.General.infoEmpty')}}",
								infoPostFix : "",
								infoFiltered : "",
								processing : "{{trans('datatable.General.processing')}}",
								loadingRecords : "{{trans('datatable.General.loadingRecords')}}",
								paginate : {
									first :    "{{trans('datatable.General.start')}}",
									last :     "{{trans('datatable.General.end')}}",
									previous : "{{trans('datatable.General.previous')}}",
									next :     "{{trans('datatable.General.next')}}"
								},
								lengthMenu: "{{trans('datatable.General.lengthMenu')}}",
							};

						configDet["buttons"] = {
							dom: {
								button: {
									className: ''
								}
							},
							buttons: []
						};

						configDet["searching"] = false;
						
						configDet["ajax"] = {
							url: "{{ route('inquiry.employerInfo.datatable') }}",
							type: 'POST',
							data: {
								compName : $('#compName').val(),
								ssmNo 	: $('#ssmNo').val(),
							},
						};
						 $('#page-detail').DataTable(configDet);
							 
							
						toggleLoader();
                    },
                    error: function (xhr, status) {
                        toggleLoader();
                        var response = xhr.responseJSON;

                        if ( $.isEmptyObject(response.errors) ){
                            var message = response.message;

                            if (! message.length && response.exception){
                                message = response.exception;
                            }

                            swal.fire("Warning", message, "warning");
                        }
                        else {
                            var errors = '<p id="fontSize" style="margin-top:2%; margin-bottom:1%; font-size: 25px;"><i>Invalid Information</i></p>';
                            $.each(response.errors, function (key, message) {
                                errors = errors;
                                errors += '<p style="margin-top:2%; margin-bottom:1%">'+message;
                                errors += '</p>';
                                if (key.indexOf('.') !== -1) {
                                    var splits = key.split('.');
                                    key = '';

                                    $.each(splits, function(i, val) {
                                        if (i === 0) {
                                            key = val;
                                        } else {
                                            key += '[' + val + ']';
                                        }
                                    });
                                }

                                $('[name="' + key + '"]').closest('.form-group').addClass("has-error");
                                $('[name="' + key + '"]').addClass("was-validated is-invalid invalid custom-select.is-invalid");
                                $('#Valid'+key).empty();
                                $('[name="' + key + '"]').closest('.form-group').append("<span id='Valid"+key+"' class=\"help-block\" style='color:red; font-family:Nunito, sans-serif;'>" + message[0] + "</span>");
                            });

                            swal.fire('Warning', errors, 'warning',{html:true});
                        }
                    }
                });
            });

            $('#backDet').click(function() {
                $('.index-div').fadeIn();
                $('#report-detail').hide();
            });

		
        })(jQuery);
    </script>
@endpush