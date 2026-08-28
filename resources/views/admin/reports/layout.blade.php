@extends('layouts.admin')
@section('title')@yield('report-title', 'Reports')@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
{{-- flex-col on mobile — the sidebar nav stacks above the report content full-width instead
     of squeezing into a fixed-width column beside it (w-52 next to a phone-width viewport
     left almost nothing for the actual page). Side-by-side again from lg: up. --}}
<div class="flex flex-col lg:flex-row gap-5">
    @include('admin.reports._nav')
    <div class="flex-1 min-w-0">
        @yield('report-content')
    </div>
</div>
@endsection
