@extends('layouts.admin')
@section('title')@yield('report-title', 'Reports')@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="flex gap-5">
    @include('admin.reports._nav')
    <div class="flex-1 min-w-0">
        @yield('report-content')
    </div>
</div>
@endsection
