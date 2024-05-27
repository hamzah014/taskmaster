<!DOCTYPE html>
<html class="loading" lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-textdirection="ltr">
@include('layouts._partials.head')
@include('layouts._partials.preloaders')

<body class="vertical-layout page-header-light vertical-menu-collapsible vertical-menu-nav-dark preload-transitions 2-columns   " data-open="click" data-menu="vertical-menu-nav-dark" data-col="2-columns">

@if($type == 'SO')
    @include('layouts._partials.headerPelaksana')
@else
    @include('layouts._partials.headerPerolehan')
@endif
{{--@include('layouts._partials.nav')  --}}
        <div id="content" >
            <div class="row">
                <div class="content-wrapper-before blue-grey lighten-5"></div>
                <div class="col s12">
                    <div class="container">
                        @yield('content')

                        @include('layouts.globalModal')
                        @include('layouts.globalModalNotif')
                        @include('layouts.globalModalKomen')
                        @include('layouts.globalModalJemputan')

                    </div>
                    <div class="content-overlay"></div>
                </div>
            </div>
        </div>

@if($type == 'SO')
    @include('layouts._partials.footerPelaksana')
@else
    @include('layouts._partials.footerPerolehan')
@endif

    <!-- END: Footer-->
	@include('layouts._partials.scripts')
    @stack('script')
    @include('layouts._partials.scriptNotification')
</body>
</html>
