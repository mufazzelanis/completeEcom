@extends('layouts.admin')
@section('title', 'AI Blog Settings')

@section('content')
@php
    use App\Services\AiBlog\Settings as S;
    $input = 'w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500';
@endphp
<div class="max-w-4xl" x-data="{ provider: {{ Js::from(old('ai_blog_provider', S::provider())) }} }">
    @include('admin.ai-blog._nav')

    <form method="POST" action="{{ route('admin.ai-blog.settings.save') }}" class="space-y-6" autocomplete="off">
        @csrf

        {{-- AI service --}}
        <section class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-6 space-y-5">
            <div>
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">1. AI service</h2>
                <p class="text-xs text-gray-500 mt-0.5">An <strong>API key</strong> is separate from a Claude Pro / ChatGPT Plus subscription: you pay per use from prepaid credit. Set a monthly spend limit on the provider's site too.</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                @foreach(S::PROVIDERS as $key => $label)
                    <label class="flex items-center gap-3 rounded-xl ring-1 p-3 cursor-pointer" :class="provider === '{{ $key }}' ? 'ring-indigo-500 bg-indigo-50/50' : 'ring-gray-200'">
                        <input type="radio" name="ai_blog_provider" value="{{ $key }}" x-model="provider" class="text-indigo-600">
                        <span class="text-sm font-medium">{{ $label }}</span>
                        @if(S::keyHint($key))<span class="ml-auto text-xs text-green-600">key saved</span>@endif
                    </label>
                @endforeach
            </div>

            <div x-show="provider === 'anthropic'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Claude API key</label>
                    <input type="password" name="anthropic_key" class="{{ $input }} font-mono" placeholder="{{ S::keyHint('anthropic') ? 'Saved: ' . S::keyHint('anthropic') . ' — leave empty to keep' : 'sk-ant-…' }}">
                    <p class="text-xs text-gray-400 mt-1">platform.claude.com → API Keys → Create Key. Stored encrypted; never shown again.
                        @if(S::keyHint('anthropic'))<label class="ml-2 text-red-600"><input type="checkbox" name="forget_anthropic_key" value="1" class="rounded"> remove saved key</label>@endif
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Writing model</label>
                    <select name="ai_blog_model" class="{{ $input }}">
                        @foreach(S::ANTHROPIC_MODELS as $id => [$label, $in, $out])
                            <option value="{{ $id }}" @selected(old('ai_blog_model', setting('ai_blog_model', 'claude-opus-5-5')) === $id)>{{ $label }} — ${{ $in }} / ${{ $out }} per 1M tokens</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">One bilingual post ≈ Opus $0.35 (৳45) · Sonnet $0.18 (৳22). Topic ideas always use the cheap Haiku.</p>
                </div>
            </div>

            <div x-show="provider === 'openai'" x-cloak class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OpenAI API key</label>
                    <input type="password" name="openai_key" class="{{ $input }} font-mono" placeholder="{{ S::keyHint('openai') ? 'Saved: ' . S::keyHint('openai') . ' — leave empty to keep' : 'sk-…' }}">
                    <p class="text-xs text-gray-400 mt-1">platform.openai.com → API keys.
                        @if(S::keyHint('openai'))<label class="ml-2 text-red-600"><input type="checkbox" name="forget_openai_key" value="1" class="rounded"> remove saved key</label>@endif
                    </p>
                </div>
                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Model name</label>
                        <input type="text" name="ai_blog_openai_model" value="{{ old('ai_blog_openai_model', setting('ai_blog_openai_model', 'gpt-5.5')) }}" class="{{ $input }} font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Input $ / 1M</label>
                        <input type="number" step="0.01" min="0" name="ai_blog_openai_price_in" value="{{ old('ai_blog_openai_price_in', setting('ai_blog_openai_price_in', '5')) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Output $ / 1M</label>
                        <input type="number" step="0.01" min="0" name="ai_blog_openai_price_out" value="{{ old('ai_blog_openai_price_out', setting('ai_blog_openai_price_out', '30')) }}" class="{{ $input }}">
                    </div>
                </div>
                <p class="text-xs text-gray-400">Copy the model's prices from OpenAI's pricing page — used for the cost dashboard and the monthly limit.</p>
            </div>
        </section>

        {{-- Schedule --}}
        <section class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-6 space-y-5">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">2. Schedule</h2>
            <label class="flex items-center justify-between gap-3 rounded-xl bg-gray-50 dark:bg-gray-800 p-4 cursor-pointer">
                <span>
                    <span class="font-medium text-gray-800 dark:text-gray-100">Autopilot</span>
                    <span class="block text-xs text-gray-500">Writes the next queued topic at each posting time. "Write now" on a topic works even when this is off.</span>
                </span>
                <input type="hidden" name="ai_blog_enabled" value="0">
                <input type="checkbox" name="ai_blog_enabled" value="1" @checked(old('ai_blog_enabled', setting('ai_blog_enabled', '0')) === '1') class="w-5 h-5 rounded text-indigo-600">
            </label>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Posts per day</label>
                    <select name="ai_blog_posts_per_day" class="{{ $input }}">
                        @for($n = 0; $n <= S::HARD_DAILY_CAP; $n++)
                            <option value="{{ $n }}" @selected((int) old('ai_blog_posts_per_day', setting('ai_blog_posts_per_day', '1')) === $n)>{{ $n }}{{ $n === 0 ? ' (paused)' : '' }}{{ $n === 1 || $n === 2 ? ' — recommended' : '' }}</option>
                        @endfor
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Posting times ({{ S::timezone() }})</label>
                    <input type="text" name="ai_blog_slots" value="{{ old('ai_blog_slots', setting('ai_blog_slots', '10:00, 18:00')) }}" class="{{ $input }} font-mono" placeholder="10:00, 18:00">
                    <p class="text-xs text-gray-400 mt-1">24-hour, comma-separated. The first "posts per day" times are used.</p>
                </div>
            </div>
            <p class="text-xs text-amber-700 bg-amber-50 rounded-xl px-3 py-2">Google penalises sites that publish lots of thin AI pages. 1–2 good posts a day is the safe range; the system never writes more than {{ S::HARD_DAILY_CAP }}.</p>
        </section>

        {{-- Writing --}}
        <section class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-6 space-y-5">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">3. Writing</h2>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Default language</label>
                    <select name="ai_blog_default_lang" class="{{ $input }}">
                        @foreach(\App\Models\BlogPost::LANGS as $code => $label)
                            <option value="{{ $code }}" @selected(old('ai_blog_default_lang', S::defaultLang()) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Readers see this version first.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Article length (words)</label>
                    <input type="number" name="ai_blog_words" min="500" max="2500" step="50" value="{{ old('ai_blog_words', S::words()) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monthly spend limit ($)</label>
                    <input type="number" name="ai_blog_monthly_budget" min="0" step="1" value="{{ old('ai_blog_monthly_budget', S::monthlyBudget()) }}" class="{{ $input }}">
                    <p class="text-xs text-gray-400 mt-1">0 = no limit. Stops writing when reached.</p>
                </div>
            </div>
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="hidden" name="ai_blog_bilingual" value="0">
                <input type="checkbox" name="ai_blog_bilingual" value="1" @checked(old('ai_blog_bilingual', setting('ai_blog_bilingual', '1')) === '1') class="mt-0.5 rounded text-indigo-600">
                <span class="text-sm"><span class="font-medium text-gray-800 dark:text-gray-100">Bangla + English</span>
                    <span class="block text-xs text-gray-500">Every post also gets the other language at its own URL, with a বাংলা | English switch (roughly doubles the cost).</span></span>
            </label>
            <div>
                <span class="block text-sm font-medium text-gray-700 mb-2">When a post is written</span>
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach(['draft' => ['Keep as draft (recommended)', 'You read it, adjust a few lines, then publish.'], 'auto' => ['Publish automatically', 'Only when every quality check passes and the topic has a Mitavin Note; anything else stays a draft.']] as $val => [$title, $desc])
                        <label class="flex items-start gap-3 rounded-xl ring-1 ring-gray-200 p-3 cursor-pointer has-[:checked]:ring-indigo-500">
                            <input type="radio" name="ai_blog_publish_mode" value="{{ $val }}" @checked(old('ai_blog_publish_mode', S::publishMode()) === $val) class="mt-0.5 text-indigo-600">
                            <span class="text-sm"><span class="font-medium">{{ $title }}</span><span class="block text-xs text-gray-500">{{ $desc }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                <input type="text" name="ai_blog_tone" maxlength="500" value="{{ old('ai_blog_tone', setting('ai_blog_tone')) }}" class="{{ $input }}" placeholder="{{ S::tone() }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Style guide <span class="text-gray-400 font-normal">(optional — how your shop talks, words to use/avoid, a sample paragraph)</span></label>
                <textarea name="ai_blog_style_guide" rows="5" maxlength="5000" class="{{ $input }}" placeholder="e.g. Call readers 'আপনি'. Mention that all products are 100% original imported. Never use the word 'cheap' — say 'affordable'.">{{ old('ai_blog_style_guide', setting('ai_blog_style_guide')) }}</textarea>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Disclaimer (বাংলা)</label>
                    <textarea name="ai_blog_disclaimer_bn" rows="3" maxlength="500" class="{{ $input }}">{{ old('ai_blog_disclaimer_bn', S::disclaimer('bn')) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Disclaimer (English)</label>
                    <textarea name="ai_blog_disclaimer_en" rows="3" maxlength="500" class="{{ $input }}">{{ old('ai_blog_disclaimer_en', S::disclaimer('en')) }}</textarea>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Author shown on posts</label>
                    <select name="ai_blog_author_id" class="{{ $input }}">
                        <option value="">— none —</option>
                        @foreach($authors as $u)
                            <option value="{{ $u->id }}" @selected((int) old('ai_blog_author_id', setting('ai_blog_author_id')) === $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">A real name builds trust (Google's E-E-A-T).</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Default blog category</label>
                    <select name="ai_blog_category_id" class="{{ $input }}">
                        <option value="">— none —</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected((int) old('ai_blog_category_id', setting('ai_blog_category_id')) === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Save settings</button>
        </div>
    </form>

    @if(S::ready())
    <form method="POST" action="{{ route('admin.ai-blog.settings.test') }}" class="mt-4" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <button type="submit" :disabled="busy" class="text-sm font-semibold text-indigo-700 border border-indigo-200 hover:bg-indigo-50 rounded-xl px-4 py-2 disabled:opacity-60">
            <span x-show="!busy">Test connection</span><span x-show="busy" x-cloak>Testing…</span>
        </button>
        <span class="text-xs text-gray-400 ml-2">Sends one tiny request (under ৳0.10) with the saved key.</span>
    </form>
    @endif
</div>
@endsection
