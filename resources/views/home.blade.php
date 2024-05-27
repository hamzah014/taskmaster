@extends('layouts.app')

@push('css')
    <style>
    </style>
@endpush
@section('content')


<div id="kt_app_content_container" class="app-container d-flex justify-content-center align-items-center">
	<div class="card mb-5 mb-xl-10 bg-transparent card-no-border w-100">
		<div id="kt_account_settings_profile_details">
			<div class="card-body p-9">

                <div class="row flex-row mb-5">
                    <div class="col-md-12">
                        <h2>Overview Dashboard</h2>
                    </div>

                </div>
                
                <div class="row gy-5 g-xl-10 mb-6">

                    <div class="col-sm-6 col-xl-3">
                        <div class="card">
                            <div class="card-body d-flex justify-content-between align-items-start flex-column">
                                <div class="d-flex flex-column text-center">
                                    <div class="m-0">
                                        <img src="{{ asset('assets/images/icon/dashboard/icon-green-g.svg') }}">
                                        <span class="fw-bold fs-6 text-dark">Total Certificate</span>
                                    </div>
                                    <div class="m-0 text-center">
                                        <span class="fw-bold fs-3 text-gray-800">{{ $dataTotal['totalcertificate'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card">
                            <div class="card-body d-flex justify-content-between align-items-start flex-column">
                                <div class="d-flex flex-column text-center">
                                    <div class="m-0">
                                        <img src="{{ asset('assets/images/icon/dashboard/icon-green-g.svg') }}">
                                        <span class="fw-bold fs-6 text-dark">Total Active Certificate</span>
                                    </div>
                                    <div class="m-0">
                                        <span class="fw-bold fs-3 text-gray-800">{{ $dataTotal['totalActiveCert'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card">
                            <div class="card-body d-flex justify-content-between align-items-start flex-column">
                                <div class="d-flex flex-column text-center">
                                    <div class="m-0">
                                        <img src="{{ asset('assets/images/icon/dashboard/icon-red-g.svg') }}">
                                        <span class="fw-bold fs-6 text-dark">Total Revoke Certificate</span>
                                    </div>
                                    <div class="m-0 text-center">
                                        <span class="fw-bold fs-3 text-gray-800">{{ $dataTotal['totalRevokeCert'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card">
                            <div class="card-body d-flex justify-content-between align-items-start flex-column">
                                <div class="d-flex flex-column text-center">
                                    <div class="m-0">
                                        <img src="{{ asset('assets/images/icon/dashboard/icon-red-g.svg') }}">
                                        <span class="fw-bold fs-6 text-dark">Total Expired Certificate</span>
                                    </div>
                                    <div class="m-0 text-center">
                                        <span class="fw-bold fs-3 text-gray-800">{{ $dataTotal['totalExpiredCert'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                
                <div class="row g-5 g-xl-10">
                    <div class="col-md-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-2">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-900">Today Request Certificate</span>
                                </h3>
                                <div class="card-toolbar">
                                    <ul class="nav" id="kt_chart_widget_19_tabs">
                                        <li class="nav-item">
                                            <a class="px-4 me-1 text-dark">View all <i class="fas fa-arrow-right text-dark"></i></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-center">
                                    <div id="chartdonut" class="w-100 h-100 d-flex flex-center"></div>
                                </div>
                                <div class="row d-flex flex-center">
                                    <div class="col-md-6 col-lg-6 col-sm-12 card p-2 card-shadow">
                                        <div class="row mb-2 d-flex flex-center">
                                            <div class="col-lg-9 col-md-9 col-sm-4 fw-bold text-start"><input type="checkbox" disabled class="form-check-input bg-danger opacity-100"> New Request</div>
                                            <div class="col-lg-3 col-md-3 col-sm-4 fw-bold text-end">{{ $dataTotal['totalNewReq'] }}</div>
                                        </div>
                                        <div class="row mb-2 d-flex flex-center">
                                            <div class="col-lg-9 col-md-9 col-sm-4 fw-bold text-start"><input type="checkbox" disabled class="form-check-input bg-primary opacity-100"> Renew Request</div>
                                            <div class="col-lg-3 col-md-3 col-sm-4 fw-bold text-end">{{ $dataTotal['totalRenewReq'] }}</div>
                                        </div>
                                        <div class="row mb-2 d-flex flex-center">
                                            <div class="col-lg-9 col-md-9 col-sm-4 text-start fw-bold"><input type="checkbox" disabled class="form-check-input bg-success opacity-100"> Revoke Request</div>
                                            <div class="col-lg-3 col-md-3 col-sm-4 fw-bold text-end">{{ $dataTotal['totalRevokeReq'] }}</div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-2">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-900">Upcoming Expiration</span>
                                </h3>
                                <div class="card-toolbar">
                                    <ul class="nav" id="kt_chart_widget_19_tabs">
                                        <li class="nav-item">
                                            <a class="px-4 me-1 text-dark">View all <i class="fas fa-arrow-right text-dark"></i></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row p-10">
                                    <div class="col-md-6 mb-6">
                                        <div class="card bg-primary p-4 card-shadow h-lg-200px">
                                            <div class="card-title">
                                                <h4 class="text-light">Expired</h4>
                                            </div>
                                            <div class="card-body text-center p-0 ">
                                                <h2 class="fw-bold fs-6x text-light">{{ $dataTotal['totalExpiredCert'] }}</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <div class="card bg-danger p-4 card-shadow h-lg-200px">
                                            <div class="card-title">
                                                <h4 class=" text-light">7 days</h4>
                                            </div>
                                            <div class="card-body text-center p-0 ">
                                                <h2 class="fw-bold fs-6x text-light">{{ $dataExpired['totalExp7day'] }}</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <div class="card bg-warning p-4 card-shadow h-200px">
                                            <div class="card-title">
                                                <h4 class=" text-light">30 days</h4>
                                            </div>
                                            <div class="card-body text-center p-0 ">
                                                <h2 class="fw-bold fs-6x text-light">{{ $dataExpired['totalExp30day'] }}</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <div class="card bg-success p-4 card-shadow h-200px">
                                            <div class="card-title">
                                                <h4 class=" text-light">90 days</h4>
                                            </div>
                                            <div class="card-body text-center p-0 ">
                                                <h2 class="fw-bold fs-6x text-light">{{ $dataExpired['totalExp90day'] }}</h2>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
				
			</div>
		</div>
	</div>
</div>

@endsection
@push('script')
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/radar.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

    <script src="{{ asset('assets/js/custom/widgets.js') }}"></script>      
    <script>
        "use strict";
        
        var element = document.querySelector('#chartdonut');     

        var height = parseInt(KTUtil.css(element, 'height'));
        var width = parseInt(KTUtil.css(element, 'width'));

        console.log(height, 'height');
        console.log(width, 'width');

        var data = @json($dataAmtRequest);

        var options = {
            series: data,                 
            chart: {           
                fontFamily: 'inherit', 
                type: 'donut',
                width: width - (width * 0.3),
                cssClass: 'text-center'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '50%',
                        labels: {
                            value: {
                                fontSize: '10px'
                            }
                        }                        
                    }
                }
            },
            colors: [
                KTUtil.getCssVariableValue('--bs-danger'),
                KTUtil.getCssVariableValue('--bs-primary'), 
                KTUtil.getCssVariableValue('--bs-success'), 
            ],           
            stroke: {
            width: 0
            },
            labels: @json($dataRequest),
            legend: {
                show: false,
            },
            fill: {
                type: 'false',          
            },
            dataLabels: {
                enabled: false // Set to false to hide labels
            }     
        };                     

        var chart = new ApexCharts(element, options);
        chart.render();
        
        // Webpack support
        if (typeof module !== 'undefined') {
            module.exports = KTChartsWidget22;
        }

        // On document ready
        KTUtil.onDOMContentLoaded(function() {
            KTChartsWidget22.init();
        });
    </script>
@endpush
