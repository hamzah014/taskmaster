
<!DOCTYPE html>
<html lang="en">

    <head>

        <title>CollabTech</title>
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
