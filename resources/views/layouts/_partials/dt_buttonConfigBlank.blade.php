buttons: [
   
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
},