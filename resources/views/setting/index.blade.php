@extends('layouts.app')

@push('css')
@endpush
@section('content')
    <div class="section">
        <div class="section">
            <div class="row" >
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <div class="row">
                                <div class="col s12">
                                    <span class="header-button">
                                        <h4 class="card-title">{{ __('Dashboard')}}</h4>
                                    </span>
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
    <script>
    </script>
@endpush
