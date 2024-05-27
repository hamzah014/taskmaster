/*================================================================================
	Item Name: Materialize - Material Design Admin Template
	Version: 5.0
	Author: PIXINVENT
	Author URL: https://themeforest.net/user/pixinvent/portfolio
================================================================================

NOTE:
------
PLACE HERE YOUR OWN JS CODES AND IF NEEDED.
WE WILL RELEASE FUTURE UPDATES SO IN ORDER TO NOT OVERWRITE YOUR CUSTOM SCRIPT IT'S BETTER LIKE THIS. */


//clock
window.onload = setInterval(clock,1000);
function clock()
{
    var d = new Date();

    var date = d.getDate();

    var month = d.getMonth();
    var montharr =["Jan","Feb","Mar","April","May","June","July","Aug","Sep","Oct","Nov","Dec"];
    month=montharr[month];

    var year = d.getFullYear();

    var day = d.getDay();
    var dayarr =["Sun","Mon","Tues","Wed","Thurs","Fri","Sat"];
    day=dayarr[day];

    var hour =d.getHours();
    var min = d.getMinutes();
    var sec = d.getSeconds();
    var session = "AM";

    if(hour == 0){
        hour = 12;
        session = "AM";
    }

    if(hour > 12){
        hour = hour - 12;
        session = "PM";
    }

    if(hour == 12){
        session = "PM";
    }

    hour = (hour < 10) ? "0" + hour : hour;
    min = (min < 10) ? "0" + min : min;
    sec = (sec < 10) ? "0" + sec : sec;

    document.getElementById("dateTime").innerHTML="Date:"+"\xa0\xa0\xa0\xa0\xa0\xa0\xa0"+day+" "+date+" "+month+" "+year+"\xa0\xa0\xa0\xa0\xa0\xa0\xa0"+hour+":"+min+":"+sec+" "+session;

}

// // For datatable
// const ConfigDTGlobal = {
//     dom : '',
//     language : {},
//     ordering : false,
//     searching : true,
//     processing: true,
//     serverSide: true,
//     destroy: true,
//     retrieve:true,
//     ajax: '',
//     columns: []
// };