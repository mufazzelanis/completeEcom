@extends('layouts.admin')
@section('title', 'Weak Posts — AI Blog')

@section('content')
<div class="max-w-5xl">
    @include('admin.ai-blog._nav')

    <div class="mb-4 rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-5 text-sm text-gray-600 dark:text-gray-300">
        <p><strong>Posts live for 90+ days with no Google clicks and almost no readers.</strong> Google judges the blog as a whole — many pages nobody finds pull the good ones down.</p>
        <p class="mt-2 text-gray-500">For each: <strong>improve it</strong> (more useful detail, a better title, your own tips), <strong>merge it</strong> into a stronger post on the same subject, or <strong>delete it</strong> (and add a redirect in Settings → Redirects).</p>
    </div>

    <div class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-100 dark:border-gray-800">
                <tr><th class="px-5 py-3">Post</th><th class="px-3 py-3">Published</th><th class="px-3 py-3 text-right">Readers (90d)</th><th class="px-3 py-3 text-right">Google views</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <td class="px-5 py-3">
                            <span class="font-medium text-gray-800 dark:text-gray-100 line-clamp-1">{{ $row['post']->title }}</span>
                            @if($row['post']->ai_generated)<span class="text-xs text-indigo-500">AI</span>@endif
                        </td>
                        <td class="px-3 py-3 text-gray-500 whitespace-nowrap">{{ $row['post']->published_at?->format('d M Y') }}</td>
                        <td class="px-3 py-3 text-right">{{ $row['reads'] }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($row['impressions']) }}</td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.blog.posts.edit', $row['post']) }}" class="text-xs font-semibold text-indigo-600">Improve →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No weak posts — every post older than 90 days is being found. 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
