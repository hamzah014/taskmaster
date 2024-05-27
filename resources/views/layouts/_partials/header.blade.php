
<div id="kt_app_header" class="app-header bg-secondary" data-kt-sticky="true" data-kt-sticky-activate="{default: true, lg: true}" data-kt-sticky-offset="{default: '200px', lg: '0'}" data-kt-sticky-animation="false">

    <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">

        <div class="d-flex align-items-center d-lg-none ms-n3 me-1 me-md-2" title="Show sidebar menu">
            <div class="btn btn-icon btn-active-color-primary btn-secondary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
                <i class="ki-duotone ki-abstract-14 fs-2 fs-md-1">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </div>
        </div>


        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
            <a href="index.html" class="d-lg-none">
                <img alt="Logo" src="{{ asset('assets/images/logo/logo.png') }}" class="h-30px" />
            </a>
        </div>

        <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">
            <div class="app-header-menu app-header-mobile-drawer align-items-stretch" data-kt-drawer="true" data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="250px" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true" data-kt-swapper-mode="{default: 'append', lg: 'prepend'}" data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">

            </div>

            <div class="app-navbar flex-shrink-0">

                <div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">

                    <div class="cursor-pointer symbol symbol-35px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                        Welcome <br>
                        <span>{{ Auth::User()->USName }} <i class="fa fa-chevron-down text-dark"></i> </span>
                    </div>

                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">

                        <div class="menu-item px-3">
                            <div class="menu-content d-flex align-items-center px-3">

                                <div class="d-flex flex-column">
                                    <div class="fw-bold d-flex align-items-center">{{ Auth::user()->USName }}
                                    <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">{{ Auth::user()->role ? Auth::user()->role->RLName : "" }}</span></div>
                                    <a class="fw-semibold text-muted text-hover-primary fs-7">{{ Auth::user()->USEmail }} </a>
                                </div>

                            </div>
                        </div>


                        <div class="separator my-2"></div>

                        <div class="menu-item px-5">
                            <a class="menu-link px-5" href="{{ route('profile.index') }}">Profile</a>
                        </div>

                        <div class="menu-item px-5">
                            <a href="{{ route('signout') }}" class="menu-link px-5">Logout</a>
                        </div>

                    </div>


                </div>

            </div>

        </div>

    </div>

</div>
