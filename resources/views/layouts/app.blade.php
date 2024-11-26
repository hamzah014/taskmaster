
<!DOCTYPE html>
<html lang="en">

    <head>

        <title>{{ env('APP_NAME') }}</title>
        @include('layouts._partials.head')
        <style>
            .nav-staff{
                background: #FFFFFF7D;
            }
        </style>

        @stack('css')

    </head>

    <body id="kt_app_body" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default"
        data-kt-app-page-loading-enabled="true" data-kt-app-page-loading="on"
        style="background-color:whitesmoke;">

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">

        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            <!--Top nav-->
            @include('layouts._partials.header')
            <!--Top nav-->

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">

                <!--Sidebar-->
                @include('layouts._partials.nav')
                <!--Sidebar-->

                <!-- CONTENT -->
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">

                    <!--Content wrapper-->
                    <div class="d-flex flex-column flex-column-fluid">
                        <div id="kt_app_content" class="app-content flex-column-fluid">
                            @yield('content')
                        </div>

                    </div>

                    <div id="kt_app_footer" class="app-footer">
                        <div class="app-container container-fluid d-flex flex-column py-3 text-center">
                            <div class="text-gray-900">
                                <span class="text-muted fw-semibold me-1">2024&copy;</span>
                                <a href="https://keenthemes.com" target="_blank" class="text-gray-800 text-hover-primary">{{ env('APP_NAME') }} (Imran Harith)</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>

    @stack('modals')
    @include('layouts.preloader')

    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </div>

    </body>

    @include('layouts._partials.scripts')
    @stack('script')

</html>
