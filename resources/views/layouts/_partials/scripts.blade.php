
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/create-app.js') }}"></script>
    <script src="{{asset('js/ajaxSubmit.js')}}" type="text/javascript"></script>

    <script type="text/javascript">
        var token = '{{ csrf_token() }}';
        var windowLocation;
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

    </script>

    <script>

        var userRole = "{{ Auth::user() ? Auth::user()->USType : 'public'  }}";

        var warning = "{{ __('Warning') }}";
        var deleteTitle = "{{ __('Are you sure?') }}";
        var deleteText = "{{ __('Information will DELETED.') }}";
        var deleteSuccess = "{{ __('Information successfully deleted')}}";

        var saveTitle = "{{ __('Are you sure?') }}";
        var saveText = "{{ __('Information will SAVED.') }}";
        var saveSuccess = "{{ __('Information successfully saved')}}";

        var yes = "{{ __('Yes') }}";
        var no = "{{ __('No') }}";
        var successTitle = "{{ __('Success') }}";
        var errorTitle = "{{ __('Error') }}";
        var errorText = "{{ __('Server Error') }}";
        var invalidInfo = "{{ __('Information invalid.') }}";


        $(document).ready(function(){

            $('.select2-multiple').select2({
                tags: true,
                placeholder: "Select an Option",
                allowClear: true,
                width: '100%'
            });
        });

        function initializeSelect2(randomCode) {

            console.log('randomCode',randomCode);

            $('[id^="memberRole'+randomCode+'"]').select2({
                tags: true,
                placeholder: "Select an Option",
                allowClear: true,
                width: '100%'
            });


        }

        function toggleLoader(close) {
            close = close || false;

            var el = $(".page-loader");

            if (el.css('display') == 'none' && close == false) {

                KTApp.showPageLoading();

            }
            else {
                KTApp.hidePageLoading();
            }
        }

        function generateRandomCode() {

            length = 4;

            var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var code = '';

            for (var i = 0; i < length; i++) {
                var randomIndex = Math.floor(Math.random() * characters.length);
                code += characters.charAt(randomIndex);
            }

            return code;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const stepperItems = document.querySelectorAll('.stepper-item[data-step]');
            const contentItems = document.querySelectorAll('.forms [data-kt-stepper-element="content"]');

            stepperItems.forEach(stepper => {
                stepper.addEventListener('click', function() {
                    const step = this.getAttribute('data-step');

                    // Remove 'current' class from all stepper items and content items
                    stepperItems.forEach(item => item.classList.remove('current'));
                    contentItems.forEach(content => content.classList.remove('current'));

                    // Add 'current' class to the clicked stepper item and the corresponding content item
                    this.classList.add('current');
                    document.querySelector(`.forms [data-step="${step}"]`).classList.add('current');
                });
            });
        });


    </script>
