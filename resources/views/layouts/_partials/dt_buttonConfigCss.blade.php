
buttons: [
{
text: '<span class="ui-button-text">PDF</span><i class="material-icons right">picture_as_pdf</i>',
extend: 'pdfHtml5',
className: 'dt-button ui-button ui-state-default ui-button-text-only buttons-excel buttons-html5 waves-effect waves-light btn mb-1 gradient-45deg-purple-deep-orange',
filename: dt_export_filename,
extension: '.pdf',
orientation: dt_export_orientation, //portrait
pageSize: dt_export_pageSize, //A3 , A5 , A6 , legal , letter
exportOptions: {
columns: 'th:not(:last-child)',
search: 'applied',
order: 'applied',
stripNewlines: false,
stripHtml: true,
},
customize: function (doc) {
{{--doc.content[1].table.widths = [ '5%',  '10%', '10%', '10%',--}}
{{--'10%', '10%', '10%'];--}}
//Remove the title created by datatTables
doc.content.splice(0,1);
//Create a date string that we use in the footer. Format is dd-mm-yyyy
var now = new Date();
var jsDate = now.getDate()+'-'+(now.getMonth()+1)+'-'+now.getFullYear();

// A documentation reference can be found at
// https://github.com/bpampuch/pdfmake#getting-started
// Set page margins [left,top,right,bottom] or [horizontal,vertical]
// or one number for equal spread
// It's important to create enough space at the top for a header !!!
doc.pageMargins = [20,60,20,30];
// Set the font size fot the entire document
doc.defaultStyle.fontSize = 7;
// Set the fontsize for the table header
doc.styles.tableHeader.fontSize = 7;

doc['header']=(function() {
return {
columns: [
{
alignment: 'center',
italics: true,
text: dt_export_filename,
fontSize: 10,
margin: [10,0]
}
],
margin: 20
}
});
// Create a footer object with 2 columns
// Left side: report creation date
// Right side: current page and total pages
doc['footer']=(function(page, pages) {
return {
columns: [
{
alignment: 'left',
text: ['Generate Date: ', { text: jsDate.toString() }]
},
{
alignment: 'right',
text: ['M/S ', { text: page.toString() },  ' from ', { text: pages.toString() }]
}
],
margin: 20
}
});
// Change dataTable layout (Table styling)
// To use predefined layouts uncomment the line below and comment the custom lines below
// doc.content[0].layout = 'lightHorizontalLines'; // noBorders , headerLineOnly
var objLayout = {};
objLayout['hLineWidth'] = function(i) { return .5; };
objLayout['vLineWidth'] = function(i) { return .5; };
objLayout['hLineColor'] = function(i) { return '#aaa'; };
objLayout['vLineColor'] = function(i) { return '#aaa'; };
objLayout['paddingLeft'] = function(i) { return 4; };
objLayout['paddingRight'] = function(i) { return 4; };
doc.content[0].layout = objLayout;
}
},
{
text: '<span class="ui-button-text">Excel</span> <i class="material-icons right">grid_on</i>',
extend: 'excelHtml5',
className: 'dt-button ui-button ui-state-default ui-button-text-only buttons-excel buttons-html5 waves-effect waves-light btn mb-1 gradient-45deg-cyan-light-green',
filename: dt_export_filename,
title: dt_export_filename,
extension: '.xlsx',
exportOptions: {
columns: 'th:not(:last-child)',
search: 'applied',
order: 'applied',
stripNewlines: false,
stripHtml: true,
},
customize: function( xlsx ) {
var sheet = xlsx.xl.worksheets['sheet1.xml'];
$('row:gt(0) c', sheet).attr( 's', '55' );
}
}
],
initComplete: function () {
// Apply the search
table.columns().every( function () {
var that = this;

$( 'input', this.footer() ).on( 'keyup change', function () {
that
.search( this.value )
.draw();
} );
} );
cssCallBack(type);
},