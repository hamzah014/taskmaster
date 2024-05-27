$('body').on('click', '#delete-table', function (e) {
    var url = $(this).attr('data-href');
    var id = $(this).attr('data-id');
    swal({
        title: "Are Sure Want to Delete?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
    }).then(function(confirmed)  {
        if (confirmed) {
            $.ajax({
                type: "DELETE",
                url: url,
                success: function(res){
                    if (res.message) {
                        swal({
                            title: "Successful Deleted",
                            text: res.message,
                            type: "success"
                        }).then(function()  {
                            if (res.redirect)
                                location.href = res.redirect;
                        });
                    } else {
                        if (res.redirect)
                            location.href = res.redirect;

                    }
                },
                error: function (xhr, status) {
                    var response = xhr.responseJSON;
                    swal("Warning", response.message, "warning");
                }
            });
        }
    });
});

$('body').on('click', '#approved-table', function (e) {
    var url = $(this).attr('data-href');
    var id = $(this).attr('data-id');
    swal({
        title: "Are Sure Want to Inactive This Account?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
    }).then(function(confirmed)  {
        if (confirmed) {
            $.ajax({
                type: "POST",
                url: url,
                success: function(res){
                    if (res.message) {
                        swal({
                            title: "Successful Inactivated",
                            text: res.message,
                            type: "success"
                        }).then(function()  {
                            if (res.redirect)
                                location.href = res.redirect;
                        });
                    } else {
                        if (res.redirect)
                            location.href = res.redirect;

                    }
                },
                error: function (xhr, status) {
                    var response = xhr.responseJSON;
                    swal("Warning", response.message, "warning");
                }
            });
        }
    });
});

$('body').on('click', '#reject-table', function (e) {
    var url = $(this).attr('data-href');
    var id = $(this).attr('data-id');
    swal({
        title: "Are Sure Want to Activate This Account?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
    }).then(function(confirmed)  {
        if (confirmed) {
            $.ajax({
                type: "POST",
                url: url,
                success: function(res){
                    if (res.message) {
                        swal({
                            title: "Successful Activated",
                            text: res.message,
                            type: "success"
                        }).then(function()  {
                            if (res.redirect)
                                location.href = res.redirect;
                        });
                    } else {
                        if (res.redirect)
                            location.href = res.redirect;

                    }
                },
                error: function (xhr, status) {
                    var response = xhr.responseJSON;
                    swal("Warning", response.message, "warning");
                }
            });
        }
    });
});

$('body').on('click', '#approved-leave', function (e) {
    var url = $(this).attr('data-href');
    var id = $(this).attr('data-id');
    swal({
        title: "Are Sure Want to Approved this Leave?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
    }).then(function(confirmed)  {
        if (confirmed) {
            $.ajax({
                type: "GET",
                url: url,
                success: function(res){
                    if (res.message) {
                        swal({
                            title: "Successful Approved",
                            text: res.message,
                            type: "success"
                        }).then(function()  {
                            if (res.redirect)
                                location.href = res.redirect;
                        });
                    } else {
                        if (res.redirect)
                            location.href = res.redirect;

                    }
                },
                error: function (xhr, status) {
                    var response = xhr.responseJSON;
                    swal("Warning", response.message, "warning");
                }
            });
        }
    });
});

$('body').on('click', '#reject-leave', function (e) {
    var url = $(this).attr('data-href');
    var id = $(this).attr('data-id');
    swal({
        title: "Are Sure Want to Reject this Leave?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
    }).then(function(confirmed)  {
        if (confirmed) {
            $.ajax({
                type: "GET",
                url: url,
                success: function(res){
                    if (res.message) {
                        swal({
                            title: "Successful Rejected",
                            text: res.message,
                            type: "success"
                        }).then(function()  {
                            if (res.redirect)
                                location.href = res.redirect;
                        });
                    } else {
                        if (res.redirect)
                            location.href = res.redirect;

                    }
                },
                error: function (xhr, status) {
                    var response = xhr.responseJSON;
                    swal("Warning", response.message, "warning");
                }
            });
        }
    });
});

$('body').on('click', '#cancel-leave', function (e) {
    var url = $(this).attr('data-href');
    var id = $(this).attr('data-id');
    swal({
        title: "Are Sure Want to Cancel this Leave?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
    }).then(function(confirmed)  {
        if (confirmed) {
            $.ajax({
                type: "GET",
                url: url,
                success: function(res){
                    if (res.message) {
                        swal({
                            title: "Successful Cancel",
                            text: res.message,
                            type: "success"
                        }).then(function()  {
                            if (res.redirect)
                                location.href = res.redirect;
                        });
                    } else {
                        if (res.redirect)
                            location.href = res.redirect;

                    }
                },
                error: function (xhr, status) {
                    var response = xhr.responseJSON;
                    swal("Warning", response.message, "warning");
                }
            });
        }
    });
});

$('body').on('click', '#approved-detail', function (e) {
    var url = $(this).attr('data-href');
    var id = $(this).attr('data-id');
    swal({
        title: "Are Sure Want to Approved?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
    }).then(function(confirmed)  {
        if (confirmed) {
            $.ajax({
                type: "GET",
                url: url,
                success: function(res){
                    if (res.message) {
                        swal({
                            title: "Successful Approved",
                            text: res.message,
                            type: "success"
                        }).then(function()  {
                            if (res.redirect)
                                location.href = res.redirect;
                        });
                    } else {
                        if (res.redirect)
                            location.href = res.redirect;

                    }
                },
                error: function (xhr, status) {
                    var response = xhr.responseJSON;
                    swal("Warning", response.message, "warning");
                }
            });
        }
    });
});

$('body').on('click', '#reject-detail', function (e) {
    var url = $(this).attr('data-href');
    var id = $(this).attr('data-id');
    swal({
        title: "Are Sure Want to Reject?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
    }).then(function(confirmed)  {
        if (confirmed) {
            $.ajax({
                type: "GET",
                url: url,
                success: function(res){
                    if (res.message) {
                        swal({
                            title: "Successful Rejected",
                            text: res.message,
                            type: "success"
                        }).then(function()  {
                            if (res.redirect)
                                location.href = res.redirect;
                        });
                    } else {
                        if (res.redirect)
                            location.href = res.redirect;

                    }
                },
                error: function (xhr, status) {
                    var response = xhr.responseJSON;
                    swal("Warning", response.message, "warning");
                }
            });
        }
    });
});