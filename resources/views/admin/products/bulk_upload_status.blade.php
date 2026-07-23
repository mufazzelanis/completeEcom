@extends('layouts.admin')
@section('title', 'Import Progress')

@section('content')
<div class="max-w-3xl"
    x-data="{
        status: '{{ $import->status }}',
        totalRows: {{ $import->total_rows ?? 'null' }},
        processedRows: {{ $import->processed_rows }},
        createdCount: {{ $import->created_count }},
        skippedCount: {{ $import->skipped_count }},
        imagesMatchedCount: {{ $import->images_matched_count }},
        imagesMissingCount: {{ $import->images_missing_count }},
        progressPercent: {{ $import->progressPercent() }},
        errors: {{ Js::from($import->errors ?? []) }},
        poll() {
            if (this.status === 'completed' || this.status === 'failed') return;
            fetch('{{ route('admin.products.bulk-upload.status-data', $import) }}')
                .then(r => r.json())
                .then(data => {
                    this.status = data.status;
                    this.totalRows = data.total_rows;
                    this.processedRows = data.processed_rows;
                    this.createdCount = data.created_count;
                    this.skippedCount = data.skipped_count;
                    this.imagesMatchedCount = data.images_matched_count;
                    this.imagesMissingCount = data.images_missing_count;
                    this.progressPercent = data.progress_percent;
                    this.errors = data.errors;
                    if (this.status !== 'completed' && this.status !== 'failed') {
                        setTimeout(() => this.poll(), 1500);
                    }
                });
        }
    }"
    x-init="poll()">

    <a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Products</span>
    </a>

    <div class="bg-white rounded-2xl shadow-sm p-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-semibold text-gray-800 text-lg">Bulk Product Import</h2>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                :class="{
                    'bg-yellow-100 text-yellow-700': status === 'queued',
                    'bg-blue-100 text-blue-700': status === 'processing',
                    'bg-green-100 text-green-700': status === 'completed',
                    'bg-red-100 text-red-700': status === 'failed',
                }">
                <svg x-show="status === 'processing'" class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="status.charAt(0).toUpperCase() + status.slice(1)"></span>
            </span>
        </div>

        <p class="text-sm text-gray-500 mb-2">{{ $import->original_filename }}</p>

        <div class="w-full bg-gray-100 rounded-full h-3 mb-2 overflow-hidden">
            <div class="bg-indigo-600 h-3 rounded-full transition-all duration-500" :style="`width: ${progressPercent}%`"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-500 mb-6">
            <span x-text="totalRows ? `${processedRows} of ${totalRows} rows` : `${processedRows} rows processed`"></span>
            <span x-text="`${progressPercent}%`"></span>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-green-50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-green-600" x-text="createdCount"></p>
                <p class="text-xs text-green-700 mt-1">Products Created</p>
            </div>
            <div class="bg-red-50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-red-500" x-text="skippedCount"></p>
                <p class="text-xs text-red-600 mt-1">Rows Skipped</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-6" x-show="imagesMatchedCount > 0 || imagesMissingCount > 0">
            <div class="bg-indigo-50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-indigo-600" x-text="imagesMatchedCount"></p>
                <p class="text-xs text-indigo-700 mt-1">Images Matched</p>
            </div>
            <div class="bg-amber-50 rounded-xl p-4 text-center">
                <p class="text-2xl font-bold text-amber-500" x-text="imagesMissingCount"></p>
                <p class="text-xs text-amber-600 mt-1">Images Not Found</p>
            </div>
        </div>

        <template x-if="status === 'completed'">
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm mb-4">
                Import complete.
            </div>
        </template>
        <template x-if="status === 'failed'">
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
                Import failed — see errors below.
            </div>
        </template>

        <template x-if="errors.length > 0">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                    Errors <span x-show="errors.length >= 200">(showing first 200)</span>
                </p>
                <div class="max-h-64 overflow-y-auto space-y-1 bg-gray-50 rounded-xl p-3">
                    <template x-for="(err, i) in errors" :key="i">
                        <p class="text-xs text-gray-600" x-text="err"></p>
                    </template>
                </div>
            </div>
        </template>

        <div class="flex justify-end mt-6" x-show="status === 'completed' || status === 'failed'">
            <a href="{{ route('admin.products.index') }}" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
                View Products
            </a>
        </div>
    </div>
</div>
@endsection
