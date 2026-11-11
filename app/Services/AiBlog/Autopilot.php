<?php

namespace App\Services\AiBlog;

use App\Models\AiBlogRun;
use App\Models\AiBlogTopic;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Runs every minute (ai-blog:run). Writes at most one post per call:
 *   1. a topic the admin pressed "Write now" on, else
 *   2. the next queued topic, when one of today's posting times has come and hasn't been used yet.
 * Stops on its own when the monthly budget is spent, and never goes past the daily limit.
 */
class Autopilot
{
    public const HEARTBEAT = 'ai_blog:last_tick';

    public function __construct(private ?Writer $writer = null)
    {
    }

    /** What happened, for the command's output / tests. */
    public function tick(): string
    {
        Cache::put(self::HEARTBEAT, now()->toIso8601String(), now()->addDays(2));

        // a write that died mid-way (server restart, timeout) goes back to the queue
        AiBlogTopic::where('status', 'writing')->where('updated_at', '<', now()->subMinutes(30))
            ->update(['status' => 'queued', 'error' => 'Interrupted — will try again.']);

        if (!Settings::ready()) {
            return 'no API key';
        }
        if (Settings::monthlyBudget() > 0 && AiBlogRun::monthSpend() >= Settings::monthlyBudget()) {
            return 'monthly budget reached';
        }

        if ($topic = AiBlogTopic::where('status', 'queued')->where('run_now', true)->orderBy('updated_at')->first()) {
            return $this->process($topic, null);
        }

        if (!Settings::enabled()) {
            return 'autopilot off';
        }

        $tz = Settings::timezone();
        $now = now($tz);
        if ($this->writtenToday() >= Settings::HARD_DAILY_CAP) {
            return 'daily limit reached';
        }

        foreach (Settings::slots() as $slot) {
            $at = $now->copy()->setTimeFromTimeString($slot);
            // only today's times that have come — and not long ago (a server that was down all
            // day doesn't fire a burst of catch-up posts)
            if ($at->gt($now) || $at->lt($now->copy()->subHours(3))) {
                continue;
            }
            $key = $now->toDateString() . ' ' . $slot;
            if (Cache::has('ai_blog:slot:' . $key) || AiBlogTopic::where('slot_key', $key)->exists()) {
                continue;
            }
            $topic = $this->nextTopic();
            if (!$topic) {
                return 'queue empty';
            }
            // each posting time is used once, even if the write fails (the topic waits for the next one)
            if (!Cache::add('ai_blog:slot:' . $key, $topic->id, now()->addDays(2))) {
                continue;
            }

            return $this->process($topic, $key);
        }

        return 'nothing due';
    }

    /** Oldest due topic whose products weren't already written about twice in the last 30 days. */
    public function nextTopic(): ?AiBlogTopic
    {
        foreach (AiBlogTopic::due()->where('run_now', false)->orderByRaw('not_before IS NULL')->orderBy('not_before')->orderBy('id')->limit(25)->get() as $topic) {
            if (!$this->productsOverused($topic)) {
                return $topic;
            }
        }

        return null;
    }

    private function productsOverused(AiBlogTopic $topic): bool
    {
        $ids = array_filter(array_map('intval', (array) $topic->product_ids));
        if (!$ids) {
            return false;
        }

        return DB::table('blog_post_product')
            ->join('blog_posts', 'blog_posts.id', '=', 'blog_post_product.blog_post_id')
            ->whereIn('blog_post_product.product_id', $ids)
            ->where('blog_posts.ai_generated', true)
            ->where('blog_posts.created_at', '>=', now()->subDays(30))
            ->select('blog_post_product.product_id')
            ->groupBy('blog_post_product.product_id')
            ->havingRaw('COUNT(*) >= 2')
            ->exists();
    }

    private function writtenToday(): int
    {
        $start = now(Settings::timezone())->startOfDay()->utc();

        return AiBlogTopic::whereIn('status', ['done', 'writing'])->where('updated_at', '>=', $start)->count();
    }

    public function process(AiBlogTopic $topic, ?string $slotKey): string
    {
        $topic->update(['status' => 'writing', 'run_now' => false, 'slot_key' => $slotKey ?? $topic->slot_key,
            'attempts' => $topic->attempts + 1, 'error' => null]);

        try {
            $post = ($this->writer ??= new Writer())->write($topic);
            $topic->update(['status' => 'done', 'blog_post_id' => $post->id]);

            return "wrote post #{$post->id} ({$post->status})";
        } catch (\Throwable $e) {
            if (!$e instanceof AiBlogException) {
                report($e);
            }
            $topic->update([
                'status' => $topic->attempts >= 3 || $e instanceof AiBlogException && str_contains($e->getMessage(), 'No active product') ? 'failed' : 'queued',
                'error'  => mb_strimwidth($e->getMessage(), 0, 1000, '…'),
            ]);

            return 'failed: ' . $e->getMessage();
        }
    }
}
