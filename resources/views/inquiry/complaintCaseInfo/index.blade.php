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
                        <h5 >{{ __('Complaint Case Inquiry')}}</h5></br>
                        <div class="row">
                            <div class="col s12 m6 l4 form-group">
								<label for="dateFrom">Date From<span style="color: red;">*</span></label>
								{!! Form::date('dateFrom', \Carbon\Carbon::today()->format('Y-m-d'), ['id' => 'dateFrom', 'class' => 'form-control datepicker', 'autocomplete' => 'off']) !!}
							</div>
                            <div class="col s12 m6 l4 form-group">
								<label for="dateTo">Date To<span style="color: red;">*</span></label>
								{!! Form::date('dateTo', \Carbon\Carbon::now()->format('Y-m-d'), ['id' => 'dateTo', 'class' => 'form-control datepicker', 'autocomplete' => 'off']) !!}
							</div>
							<div class="col s12 m6 l4 form-group">
								<label for="caseStatus">{{ __('Case Status')}}</label>
								{!! Form::select('caseStatus', $caseStatus, null, ['id' => 'caseStatus', 'class' => 'select2 form-control', 'placeholder' => trans('message.dropdown_default')]) !!}
							</div>
							<div class="col s12 m6 l4 form-group">
								<label for="fullName">{{ __('Company Name')}} </label>
								{!! Form::text('compName', '', ['id' => 'compName', 'class' => 'form-control']) !!}
							</div>
							<div class="col s12 m6 l4 form-group">
								<label for="ssmNo">{{ __('SSM No')}} </label>
								{!! Form::text('ssmNo', '', ['id' => 'ssmNo', 'class' => 'form-control']) !!}
							</div>
							<div class="col s12 m6 l4 form-group">
								<label for="name">{{ __('Name')}} </label>
								{!! Form::text('name', '', ['id' => 'name', 'class' => 'form-control']) !!}
							</div>
							<div class="col s12 m6 l4 form-group">
								<label for="passportNo">{{ __('Passport No')}} </label>
								{!! Form::text('passportNo', '', ['id' => 'passportNo', 'class' => 'form-control']) !!}
							</div>
							<div class="col s12 m6 l4 form-group">
								<label for="nationality">{{ __('Nationality')}}</label>
								{!! Form::select('nationality', $nationality, null, ['id' => 'nationality', 'class' => 'select2 form-control', 'placeholder' => trans('message.dropdown_default')]) !!}
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
							<table id="page-detail" class="display responsive-table" style="display:block; width:100%; max-height: 1500px;overflow-x: auto;">
								<thead>
								<tr>
									<th class="dt-head-center" width="5px">No</th>
									<th class="dt-head-center" >{{ __('Case No')}}</th>
									<th class="dt-head-center" >{{ __('Case Date')}}</th>
									<th class="dt-head-center" >{{ __('Status')}}</th>
									<th class="dt-head-center" >{{ __('Category')}}</th>
									<th class="dt-head-center" >{{ __('Reported By')}}</th>
									<th class="dt-head-center" >{{ __('Company Name')}}</th>
									<th class="dt-head-center" >{{ __('SSM No')}}</th>
									<th class="dt-head-center" >{{ __('Employee Name')}}</th>
									<th class="dt-head-center" >{{ __('Passport No')}}</th>
									<th class="dt-head-center" >{{ __('Nationality')}}</th>
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
                    url: "{!! route('inquiry.complaintCaseInfo.validation') !!}",
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
								{ data: 'caseNo', name: 'caseNo', class: 'dt-body-left' },
								{ name: 'caseDate.timestamp', data: {'_': 'caseDate.display', 'sort': 'caseDate.timestamp'}},
								{ data: 'caseStatus', name: 'caseStatus', class: 'dt-body-left' },
								{ data: 'caseCategory', name: 'caseCategory', class: 'dt-body-left' },
								{ data: 'reportedBy', name: 'reportedBy', class: 'dt-body-left' },
								{ data: 'compName', name: 'compName', class: 'dt-body-left' },
								{ data: 'ssmNo', name: 'ssmNo', class: 'dt-body-left' },
								{ data: 'fullName', name: 'fullName', class: 'dt-body-left' },
								{ data: 'passportNo', name: 'passportNo', class: 'dt-body-left' },
								{ data: 'nationality', name: 'nationality', class: 'dt-body-left' },
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
							url: "{{ route('inquiry.complaintCaseInfo.datatable') }}",
							type: 'POST',
							data: {
								dateFrom : $('#dateFrom').val(),
								dateTo : $('#dateTo').val(),
								caseStatus : $('#caseStatus').val(),
								compName : $('#compName').val(),
								ssmNo : $('#ssmNo').val(),
								name : $('#name').val(),
								passportNo 	: $('#passportNo').val(),
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
