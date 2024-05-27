// For datatable
var ConfigDTGlobal = {
    dom : 'Bfrtip',
    language : {
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
        lengthMenu :
            '<div class="form-group select2">'+
                'Tunjuk&nbsp;<select class="form-control select2">'+
                    '<option value="10">10</option>'+
                    '<option value="25">25</option>'+
                    '<option value="100">100</option>'+
                    '<option value="-1">Semua</option>'+
                '</select>'+
            '&nbsp;rekod&nbsp;&emsp;</div>'
    },
    ordering : true,
    searching : true,
    processing: true,
    serverSide: true,
    ajax: '',
    columns: []
};

