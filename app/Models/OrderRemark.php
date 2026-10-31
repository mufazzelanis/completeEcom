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

    /** One line for lists / tooltips: "📞 No answer — rang twice". */
    public function summary(): string
    {
        $o = $this->outcome ? self::OUTCOMES[$this->outcome] ?? null : null;
        $head = $o ? $o[1] . ' ' . $o[0] : $this->meta()[1] . ' ' . $this->meta()[0];

        return trim($head . ($this->body ? ' — ' . $this->body : ''));
    }
}
