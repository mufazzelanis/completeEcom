<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A staff remark on an order (see the create_order_remarks migration). */
class OrderRemark extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_pinned' => 'boolean',
        'follow_up_at' => 'datetime',
    ];

    /** type => [label, emoji, colour] */
    public const TYPES = [
        'note' => ['Note', '📝', '#6366f1'],
        'call' => ['Call', '📞', '#2563eb'],
        'courier' => ['Courier', '🚚', '#7c3aed'],
        'payment' => ['Payment', '💳', '#059669'],
        'customer' => ['Customer said', '💬', '#d97706'],
    ];

    /** call outcome => [label, emoji, colour] */
    public const OUTCOMES = [
        'confirmed' => ['Confirmed', '✅', '#059669'],
        'no_answer' => ['No answer', '📵', '#6b7280'],
        'call_later' => ['Call later', '🔁', '#d97706'],
        'cancel_request' => ['Wants to cancel', '❌', '#dc2626'],
        'wrong_number' => ['Wrong number', '⚠️', '#b91c1c'],
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meta(): array
    {
        return self::TYPES[$this->type] ?? self::TYPES['note'];
    }

    /**
     * The order lists' "last remark" line (admin/orders/_remark-line): tag, short text, who/when,
     * and the call-back time when the call ended with "call later". Plain data — the line is
     * re-drawn client-side from the same shape after every add / pin / delete.
     */
    public function listLine(): array
    {
        $tz = setting('timezone', 'Asia/Dhaka');
        [$label, $emoji, $color] = ($this->outcome ? self::OUTCOMES[$this->outcome] ?? null : null) ?? $this->meta();
        $fu = $this->outcome === 'call_later' && $this->follow_up_at ? $this->follow_up_at : null;

        return [
            'tag' => $emoji . ' ' . $label,
            'color' => $color,
            'body' => $this->body ? \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $this->body), 160) : null,
            'who' => $this->user?->name ?? 'Staff',
            'ago' => $this->created_at ? $this->created_at->diffForHumans(null, true, true) . ' ago' : '',
            'at' => $this->created_at?->timezone($tz)->format('d M, g:i A'),
            'fu' => $fu ? $fu->timezone($tz)->format($fu->timezone($tz)->isToday() ? '\T\o\d\a\y g:i A' : 'd M, g:i A') : null,
            'late' => $fu ? $fu->isPast() : false,
        ];
    }

    /** One line for lists / tooltips: "📞 No answer — rang twice". */
    public function summary(): string
    {
        $o = $this->outcome ? self::OUTCOMES[$this->outcome] ?? null : null;
        $head = $o ? $o[1] . ' ' . $o[0] : $this->meta()[1] . ' ' . $this->meta()[0];

        return trim($head . ($this->body ? ' — ' . $this->body : ''));
    }
}
