
<div id="kt_app_sidebar" class="app-sidebar flex-column bg-dark" data-kt-drawer="true" data-kt-drawer-name="app-sidebar" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
	<div class="app-sidebar-logo px-6 justify-content-center" id="kt_app_sidebar_logo">
		<a href="{{ route('dashboard.index') }}" class="d-flex flex-center text-center">
			<img alt="Logo" src="{{ asset('assets/images/logo/logo.png') }}" class="w-50 app-sidebar-logo-default" />
			<img alt="Logo" src="{{ asset('assets/images/logo/logo.png') }}" class="h-20px app-sidebar-logo-minimize" />
		</a>
		<div id="kt_app_sidebar_toggle" class="d-none app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="app-sidebar-minimize">
			<i class="ki-duotone ki-black-left-line fs-3 rotate-180">
				<span class="path1"></span>
				<span class="path2"></span>
			</i>
		</div>
	</div>
	<div class="app-sidebar-menu overflow-hidden flex-column-fluid">
		<div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
			<div id="kt_app_sidebar_menu_scroll" class="my-5 mx-3" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer" data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">

				<div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">

                    <div class="menu-item">
                        <a class="menu-link text-light" href="{{ route('dashboard.index') }}">
                            <span class="menu-icon">
                                <i class="fa-solid fa-gauge-high fs-2"></i>
                            </span>
                            <span class="menu-title">Dashboard</span>
                        </a>
                    </div>

                    {{--
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                        <span class="menu-link text-light">
                            <span class="menu-icon">
                                <i class="fa-solid fa-rocket fs-2"></i>
                            </span>
                            <span class="menu-title">Project Builder</span>
                            <span class="menu-arrow"></span>
                        </span>

                        <div class="menu-sub menu-sub-accordion text-light">
                            <div class="menu-item">
                                <a class="menu-link text-light" href="{{ route('project.index') }}">
                                    <span class="menu-bullet">
                                        <i class="fa fa-regular fa-circle"></i>
                                    </span>
                                    <span class="menu-title">View</span>
                                </a>
                            </div>
                        </div>

                        <div class="menu-sub menu-sub-accordion text-light">
                            <div class="menu-item">
                                <a class="menu-link text-light" href="{{ route('project.create') }}">
                                    <span class="menu-bullet">
                                        <i class="fa fa-regular fa-circle"></i>
                                    </span>
                                    <span class="menu-title">Add</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    --}}

                    <div class="menu-item">
                        <a class="menu-link text-light" href="{{ route('project.index') }}">
                            <span class="menu-icon">
                                <i class="fa-solid fa-rocket fs-2"></i>
                            </span>
                            <span class="menu-title">Project Builder</span>
                        </a>
                    </div>

                    <div class="menu-item pt-5">
                        <div class="menu-content">
                            <span class="menu-heading fw-bold text-uppercase fs-7">Information</span>
                        </div>
                    </div>

                    <div class="menu-item">
                        <a class="menu-link text-light" href="{{ route('info.about') }}">
                            <span class="menu-icon">
                                <i class="fa-solid fa-book-open fs-2"></i>
                            </span>
                            <span class="menu-title">About</span>
                        </a>
                    </div>

                    <div class="menu-item">
                        <a class="menu-link text-light" href="{{ route('info.analysis') }}">
                            <span class="menu-icon">
                                <i class="fa-solid fa-chart-bar fs-2"></i>
                            </span>
                            <span class="menu-title">Analysis & Scoring</span>
                        </a>
                    </div>

                    <div class="menu-item">
                        <a class="menu-link text-light" href="{{ route('info.risk') }}">
                            <span class="menu-icon">
                                <i class="fa-solid fa-triangle-exclamation fs-2"></i>
                            </span>
                            <span class="menu-title">Risk Management</span>
                        </a>
                    </div>

                    {{--
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                        <span class="menu-link text-light">
                            <span class="menu-icon">
                                <i class="fa-solid fa-chart-bar fs-2"></i>
                            </span>
                            <span class="menu-title">Analysis & Scoring</span>
                            <span class="menu-arrow"></span>
                        </span>

                        <div class="menu-sub menu-sub-accordion text-light">
                            <div class="menu-item">
                                <a class="menu-link text-light" href="{{ route('project.idea.analysis.index') }}">
                                    <span class="menu-bullet">
                                        <i class="fa fa-regular fa-circle"></i>
                                    </span>
                                    <span class="menu-title">Idea Analysis</span>
                                </a>
                            </div>
                        </div>

                        <div class="menu-sub menu-sub-accordion text-light">
                            <div class="menu-item">
                                <a class="menu-link text-light" href="{{ route('project.idea.scoring.index') }}">
                                    <span class="menu-bullet">
                                        <i class="fa fa-regular fa-circle"></i>
                                    </span>
                                    <span class="menu-title">Idea Scoring</span>
                                </a>
                            </div>
                        </div>

                        <div class="menu-sub menu-sub-accordion text-light">
                            <div class="menu-item">
                                <a class="menu-link text-light" href="{{ route('project.analysis.index') }}">
                                    <span class="menu-bullet">
                                        <i class="fa fa-regular fa-circle"></i>
                                    </span>
                                    <span class="menu-title">Project Analysis</span>
                                </a>
                            </div>
                        </div>

                    </div>
                    --}}

                    {{--
                    <div class="menu-item">
                        <a class="menu-link text-light" href="{{ route('risk.index') }}">
                            <span class="menu-icon">
                                <i class="fa-solid fa-triangle-exclamation fs-2"></i>
                            </span>
                            <span class="menu-title">Risk Management</span>
                        </a>
                    </div>
                    --}}

                    {{--
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">

                        <span class="menu-link text-light">
                            <span class="menu-icon">
                                <i class="fa-solid fa-list-check fs-2"></i>
                            </span>
                            <span class="menu-title">Task Management</span>
                            <span class="menu-arrow"></span>
                        </span>

                        <div class="menu-sub menu-sub-accordion text-light">

                            <div class="menu-item">
                                <a class="menu-link text-light" href="{{ route('task.index','PD') }}">
                                    <span class="menu-bullet">
                                        <i class="fa fa-regular fa-circle"></i>
                                    </span>
                                    <span class="menu-title">Project Design</span>
                                </a>
                            </div>

                        </div>

                        <div class="menu-sub menu-sub-accordion text-light">

                            <div class="menu-item">
                                <a class="menu-link text-light" href="{{ route('task.index','FD') }}">
                                    <span class="menu-bullet">
                                        <i class="fa fa-regular fa-circle"></i>
                                    </span>
                                    <span class="menu-title">Further Development</span>
                                </a>
                            </div>

                        </div>

                        <div class="menu-sub menu-sub-accordion text-light">

                            <div class="menu-item">
                                <a class="menu-link text-light" href="{{ route('task.index','PC') }}">
                                    <span class="menu-bullet">
                                        <i class="fa fa-regular fa-circle"></i>
                                    </span>
                                    <span class="menu-title">Project Closure</span>
                                </a>
                            </div>

                        </div>

                    </div>
                    --}}

				</div>

			</div>
		</div>
	</div>
</div>
