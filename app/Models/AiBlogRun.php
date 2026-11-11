<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One AI request and what it cost (AI Blog dashboard + the monthly budget). */
class AiBlogRun extends Model
{
    protected $fillable = [
        'ai_blog_topic_id', 'purpose', 'provider', 'model', 'input_tokens', 'output_tokens',
        'cost_usd', 'ok', 'error', 'duration_ms',
    ];

    protected $casts = [
        'ok'       => 'boolean',
        'cost_usd' => 'float',
    ];

    public function topic() { return $this->belongsTo(AiBlogTopic::class, 'ai_blog_topic_id'); }

    /** Spend so far this calendar month (site timezone), in USD. */
    public static function monthSpend(): float
    {
        $tz = setting('timezone', 'Asia/Dhaka');

        return (float) static::where('created_at', '>=', now($tz)->startOfMonth()->utc())->sum('cost_usd');
    }
}
