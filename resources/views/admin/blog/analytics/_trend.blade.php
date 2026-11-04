{{-- Readers (bars) and product clicks (line) over the period — both counts, one axis. Chart.js is
     loaded once from the admin layout's <head>. Colours: validated default categorical slots 1-2. --}}
<div class="bg-white rounded-2xl shadow-sm p-5">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h3 class="font-semibold text-gray-800">{{ $trend['hourly'] ? 'By hour' : 'By day' }}</h3>
        <div class="flex items-center gap-4 text-xs text-gray-500">
            <span class="inline-flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-sm" style="background:#2a78d6"></i>Readers</span>
            <span class="inline-flex items-center gap-1.5"><i class="w-3 h-0.5 rounded" style="background:#eb6834"></i>Product clicks</span>
        </div>
    </div>
    <div class="relative h-64"><canvas id="blog-trend" aria-label="Readers and product clicks {{ $trend['hourly'] ? 'per hour' : 'per day' }}" role="img"></canvas></div>
</div>
<script>
(function () {
    var el = document.getElementById('blog-trend');
    if (!el || !window.Chart) return;
    var old = Chart.getChart(el); if (old) old.destroy();
    var dark = document.documentElement.classList.contains('dark');
    var c1 = dark ? '#3987e5' : '#2a78d6', c2 = dark ? '#d95926' : '#eb6834';
    var ink = dark ? '#c3c2b7' : '#52514e', grid = dark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
    new Chart(el, {
        data: {
            labels: @json($trend['labels']),
            datasets: [
                { type: 'bar', label: 'Readers', data: @json($trend['readers']), backgroundColor: c1, borderRadius: 4, borderSkipped: 'bottom', maxBarThickness: 28, order: 2 },
                { type: 'line', label: 'Product clicks', data: @json($trend['clicks']), borderColor: c2, backgroundColor: c2, borderWidth: 2, pointRadius: {{ count($trend['labels']) < 4 ? 4 : 0 }}, pointHoverRadius: 5, pointHitRadius: 12, tension: .3, order: 1 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { padding: 10, boxPadding: 4, usePointStyle: true } },
            scales: {
                x: { grid: { display: false }, ticks: { color: ink, maxRotation: 0, autoSkip: true, maxTicksLimit: 12, font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: grid }, border: { display: false }, ticks: { color: ink, precision: 0, font: { size: 11 } } },
            },
        },
    });
})();
</script>
