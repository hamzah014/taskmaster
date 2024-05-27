(function ($) {
    ajaxSubmitForm('form.ajax-form');
    ajaxSubmitFormConfirm('form.ajax-form-confirm');

    $(document).on("click", "#delete", function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        var url = $(this).data('url');
        swal.fire({
            title: deleteTitle,
            text: deleteText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: yes,
            cancelButtonText: no,
            allowOutsideClick: false
        }).then(function(confirmed) {
            toggleLoader();

            if (confirmed.value) {
                ajaxFormXHR = $.ajax({
                    url: url,
                    type: 'POST',
                    data: {id: id},
                    success: function (resp) {
                        toggleLoader();

                        var is_html = false;

                        if (resp.html) {
                            is_html = true;
                        }

                        swal.fire({
                            title: "Success",
                            text: resp.message,
                            icon: "success",
                            html: is_html
                        }).then(function () {
                            if (resp.redirect) {
                                if (resp.redirect === window.location.href) {
                                    location.reload();
                                }
                                else {
                                    location.href = resp.redirect;
                                }
                            }
                        });
                    },
                    error: function (xhr, status) {
                        toggleLoader();
                        swal.fire({
                            title: errorTitle,
                            text: errorText,
                            icon: "error"
                        });
                    }
                })
            }else{
                toggleLoader();
			}
        }).catch(swal.noop);
    });

	$(document).on("click", "#printPDF", function (e) {
		e.preventDefault();
		var id = $(this).data('id');
		var docno = $(this).data('docno');
		var url = $(this).data('url');
		toggleLoader();

		$.ajax({
			type: 'POST',
			url: url,
			data: {id: id},
			xhrFields: {
				responseType: 'blob'
			},
			success: function (response) {
				toggleLoader();

				var blob = new Blob([response]);
				var link = document.createElement('a');
				link.href = window.URL.createObjectURL(blob);

				link.download = docno+".pdf";
				link.click();

				swal.fire('Success', 'The pdf report is being generated.', 'success');
			},
			error: function (xhr, status) {
				toggleLoader();
				swal.fire({
					title: errorTitle,
					text: errorText,
					icon: "error"
				});
			}
		})
	});
})(jQuery);

var ajaxFormXHR = null;

function toggleLoader(close) {
    close = close || false;

    var el = $(".loadscreen");

    if (el.css('display') == 'none' && close == false) {
		$(".loadscreen").fadeIn("slow");
		$(".load").fadeIn("slow");
    }
    else {
		$(".loadscreen").fadeOut("slow");
		$(".load").fadeOut("slow");
    }
}

function ajaxSubmitForm(form, callback) {
    $(form).on("submit", function (e) {
        e.preventDefault();
        urlAction = $(this).attr("action");
        var formData = new FormData(this);
        $(".form-group").removeClass("has-error");
        $(".form-control").removeClass("was-validated invalid is-invalid custom-select.is-invalid valid is-valid custom-select.is-valid");
        $(".form-group").children("span.help-block").remove();

        toggleLoader();

        ajaxFormXHR = $.ajax({
            url: urlAction,
            type: 'POST',
            contentType: false,
            data: formData,
            processData: false,
            cache: false,
            success: function (resp) {
                toggleLoader();

                // Set all form is valid by default
                $('.form-control').addClass("was-validated valid is-valid custom-select.is-valid");

                if (typeof callback == 'function') {
                    callback(resp);
                } else if ($(form).attr('data-success') !== undefined) {
                    eval($(form).attr('data-success') + '(resp)');
                } else {

                    if (typeof resp.datatables != "undefined") {
                        resp.datatables.forEach(function(element) {
                            $('#'+element).DataTable().ajax.reload();
                        });
                    }

                    if (resp.message) {
                        var is_html = false;

                        if (resp.html)
                        {
                            is_html = true;
                        }

                        swal.fire({
                            title: "Success",
                            text: resp.message,
                            icon: "success",
                            html: is_html
                        }).then(function()  {
                            if (resp.redirect)
                            {
                                if (resp.redirect === window.location.href)
                                {
                                    location.reload();
                                }
                                else
                                {
                                    location.href = resp.redirect;
                                }
                            }

                            if (resp.dt_reload) {
                                $('#'+resp.dt_reload).click();
                            }

                            if (resp.btn_back) {
                                $('#'+resp.btn_back).click();
                            }
                        });
                    } else {
                        if (resp.redirect)
                            location.href = resp.redirect;

                    }
                }
            },
            error: function (xhr, status) {
                toggleLoader();
                var response = xhr.responseJSON;

                if ( $.isEmptyObject(response.errors) )
                {
                    var message = response.message;

                    if (! message.length && response.exception)
                    {
                        message = response.exception;
                    }

                    swal.fire("Warning", message, "warning");
                }
                else
                {
                    var errors = '<p  id="fontSize" style="margin-top:2%; margin-bottom:1%; font-size: 25px;"><i>Invalid Information</i></p>';
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
                    swal.fire("Warning", errors, "warning",{html:true});
                    $('html, body').animate({
                        scrollTop: ($(".has-error").first().offset().top) - 200
                    }, 500);
                }
            }
        })
    });
}

function ajaxSubmitFormConfirm(form, callback) {
    $(form).on("submit", function (e) {
        e.preventDefault();
        urlAction = $(this).attr("action");
        var formData = new FormData(this);
        $(".form-group").removeClass("has-error");
        $(".form-control").removeClass("was-validated invalid is-invalid custom-select.is-invalid valid is-valid custom-select.is-valid");
        $(".form-group").children("span.help-block").remove();

        swal.fire({
            title: saveTitle,
            text: saveText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: yes,
            cancelButtonText: no,
            allowOutsideClick: false
        }).then(function(confirmed) {
            toggleLoader();

            // Set all form is valid by default
            $('.form-control').addClass("was-validated valid is-valid custom-select.is-valid");

            if (confirmed.value) {
                ajaxFormXHR = $.ajax({
                    url: urlAction,
                    type: 'POST',
                    contentType: false,
                    data: formData,
                    processData: false,
                    cache: false,
                    success: function (resp) {
                        toggleLoader();

                        if (typeof callback == 'function') {
                            callback(resp);
                        } else if ($(form).attr('data-success') !== undefined) {
                            eval($(form).attr('data-success') + '(resp)');
                        } else {

                            if (typeof resp.datatables != "undefined") {
                                resp.datatables.forEach(function (element) {
                                    $('#' + element).DataTable().ajax.reload();
                                });
                            }

                            if (resp.message) {
                                var is_html = false;

                                if (resp.html) {
                                    is_html = true;
                                }

                                swal.fire({
                                    title: "Berjaya",
                                    text: resp.message,
                                    icon: "success",
                                    html: is_html
                                }).then(function () {
                                    if (resp.redirect) {
                                        if (resp.redirect === window.location.href) {
                                            location.reload();
                                        }
                                        else {
                                            location.href = resp.redirect;
                                        }
                                    }
                                });
                            } else {
                                if (resp.redirect)
                                    location.href = resp.redirect;

                            }

                            if (resp.dt_reload) {
                                $('#'+resp.dt_reload).click();
                            }

                            if (resp.btn_back) {
                                $('#'+resp.btn_back).click();
                            }
                        }
                    },
                    error: function (xhr, status) {
                        toggleLoader();
                        var response = xhr.responseJSON;

                        if ($.isEmptyObject(response.errors)) {
                            var message = response.message;

                            if (!message.length && response.exception) {
                                message = response.exception;
                            }

                            swal.fire("Warning", message, "warning");
                        }
                        else {
                            swal.fire("Warning", "Invalid Information", "warning");

                            $.each(response.errors, function (key, message) {
                                if (key.indexOf('.') !== -1) {

                                    var splits = key.split('.');

                                    key = '';

                                    $.each(splits, function (i, val) {
                                        if (i === 0) {
                                            key = val;
                                        }
                                        else {
                                            key += '[' + val + ']';
                                        }
                                    });
                                }

                                $('[name="' + key + '"]').closest('.form-group').addClass("has-error");
                                $('[name="' + key + '"]').addClass("was-validated is-invalid invalid custom-select.is-invalid");
                                $('#Valid'+key).empty();
                                $('[name="' + key + '"]').closest('.form-group').append("<span id='Valid"+key+"' class=\"help-block\" style='color:red; font-family:Nunito, sans-serif;'>" + message[0] + "</span>");
                            });

                            $('html, body').animate({
                                scrollTop: ($(".has-error").first().offset().top) - 200
                            }, 500);
                        }
                    }
                })
            }else{
                toggleLoader();
            }
        }).catch(swal.noop);
    });
}

function resetFormValidation () {
    $(".form-group").removeClass("has-error");
    $(".form-control").removeClass("was-validated invalid is-invalid custom-select.is-invalid valid is-valid custom-select.is-valid");
    $(".form-group").children("span.help-block").remove();
}
