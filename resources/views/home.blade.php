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
