

<script>

    $(document).ready(function(){
        notificationDatatable();
        toggleBtnAdditional();
        // Get the flag value from the URL
        var urlParams = new URLSearchParams(window.location.search);
        var flag = urlParams.get('nid');
        // Convert the flag to an integer and use it to open the corresponding tab
        if (flag !== null || flag==0) {
            console.log(flag);
            openNotifModal(flag);
            setTimeout(function() {
                // Get the modal instance
                var modalInstance = M.Modal.getInstance($('#notifModal'));
                // Open the modal using the modal instance
                modalInstance.open();
            }, 300);
            console.log(flag);
        }

    });
    function notificationDatatable(){

        if ($.fn.DataTable.isDataTable('#table-notif')) {
            // If it exists, destroy it first
            $('#table-notif').DataTable().destroy();
        }

        notifType = $('#notifType').val();
        refNo = $('#refNo').val();

        var table = $('#table-notif').DataTable({
            dom: 'lfrtip',
            @include('layouts._partials.lengthMenu')
            processing: true,
            serverSide: false,
            ordering:false,
            ajax:  {
                "url" :"{{ route('notification.notificationDatatable') }}",
                "method": 'POST',
                "data": {
                        typeCode: notifType,
                        refNo: refNo,
                    }
            },
            columns: [
                { name: 'action', data: 'action', class: 'text-center'  },
                { name: 'NOTitle', data: 'NOTitle', class: 'dt-body-left'  },
                { name: 'NOCD', data: 'NOCD', class: 'text-center'  },
                // { name: 'NODescription', data: 'NODescription', class: 'dt-body-left'  },
            ]

        });
        table.buttons().container().appendTo('.button-table-export');
    }


    function markAsRead(){

        var table = $('table').DataTable();
        var checkedCheckboxes = table.column(0).nodes().to$().find('input[type="checkbox"].my-checkbox:checked');

        // Get all input checkboxes with class "my-checkbox" that are checked
        // const checkedCheckboxes = Array.from(document.querySelectorAll('input[type="checkbox"].my-checkbox:checked'));

        if(checkedCheckboxes.length > 0){

            // Extract and return the values of the checked checkboxes
            var checkedValues = [];

            // Iterate through the selected checkboxes and get their values
            checkedCheckboxes.each(function () {
                checkedValues.push($(this).val());
            });
            // console.log(checkedValues);
            var formData = new FormData();

            formData.append('notiID',checkedValues);

            $.ajax({
                url: "{{ route('notification.update.readNotification') }}",
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
                    var message = resp.message;
                    swal.fire("{{ __('Berjaya')}}", message, "success");
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


    }

    function deleteMark(){
        // Get all input checkboxes with class "my-checkbox" that are checked
        const checkedCheckboxes = Array.from(document.querySelectorAll('input[type="checkbox"].my-checkbox:checked'));

        if(checkedCheckboxes.length > 0){

            // Extract and return the values of the checked checkboxes
            checkboxVal = checkedCheckboxes.map(checkbox => checkbox.value);
            // console.log(checkboxVal);
            var formData = new FormData();

            formData.append('notiID',checkboxVal);

            $.ajax({
                url: "{{ route('notification.update.deleteNotification') }}",
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
                    var message = resp.message;
                    swal.fire("{{ __('Berjaya')}}", message, "success");
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


    }

    function markAll(){
        // Get all checkboxes in the DataTable
        var table = $('#table-notif').DataTable();
        var checkboxes = table.column(0).nodes().to$().find('input[type="checkbox"]');
        console.log(checkboxes);

        // Determine if all checkboxes are currently checked
        var allChecked = checkboxes.length > 0 && checkboxes.toArray().every(checkbox => checkbox.checked);

        // Toggle the checkboxes
        checkboxes.each(function() {
            this.checked = !allChecked;
        });

        // Update the link text and icon based on the checkbox state
        var toggleText = document.getElementById('toggleText');
        var toggleIcon = document.getElementById('toggleIcon');

        if (allChecked) {
            toggleText.textContent = '{{ __('Tanda semua')}}';
            toggleIcon.textContent = 'mail';
        } else {
            toggleText.textContent = '{{ __('Batal')}}';
            toggleIcon.textContent = 'cancel';
        }

        toggleBtnAdditional();

    }

    function checkThis(label) {
        // Find the input checkbox within the label
        const checkbox = label.querySelector('input[type="checkbox"]');

        // Check if the checkbox is not checked, then set it as checked
        if (!checkbox.checked) {
            checkbox.checked = true;
            // If you want to add the "checked" attribute as well, you can use:
            checkbox.setAttribute('checked', 'checked');
        }else{
            checkbox.checked = false;
            checkbox.removeAttribute('checked');
        }

        toggleBtnAdditional();
    }

    function toggleBtnAdditional() {
        if ($('.my-checkbox').is(':checked')) {
            $('#btnAdditional').show();
        } else {
            $('#btnAdditional').hide();
        }
    }

</script>
