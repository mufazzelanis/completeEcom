<?php

namespace App\Services;

use App\Models\Order;

class FraudDetectionService
{
    private array $flags = [];

    private int $score = 0;

    public function analyze(Order $order): array
    {
        $this->flags = [];
        $this->score = 0;

        $order->loadMissing('user', 'items');

        // Orders placed by internal staff/admin accounts aren't customer transactions —
        // scoring them would only ever pick up testing noise (repeat orders, same-day account, etc).
        if ($order->user && in_array($order->user->role, ['admin', 'manager', 'staff'], true)) {
            return [
                'score' => 0,
                'flags' => [],
                'risk_level' => $this->riskLevel(0),
            ];
        }

        $this->checkOrderValue($order);
        $this->checkCustomerAge($order);
        $this->checkOrderHistory($order);
        $this->checkPaymentMethod($order);
        $this->checkRapidOrdering($order);
        $this->checkItemCount($order);
        $this->checkCancellationHistory($order);

        $score = min($this->score, 100);

        return [
            'score' => $score,
            'flags' => $this->flags,
            'risk_level' => $this->riskLevel($score),
        ];
    }

    private function checkOrderValue(Order $order): void
    {
        if ($order->total >= 20000) {
            $this->flag('Very high order value (৳'.number_format($order->total).')', 30);
        } elseif ($order->total >= 5000) {
            $this->flag('High order value (৳'.number_format($order->total).')', 20);
        }
    }

    private function checkCustomerAge(Order $order): void
    {
        if (! $order->user) {
            $this->flag('No customer account linked', 25);

            return;
        }

        $days = $order->user->created_at->diffInDays($order->created_at ?? now());

        if ($days < 1) {
            $this->flag('Account created on same day as order', 10);
        } elseif ($days < 7) {
            $this->flag('New customer account ('.$days.' days old)', 15);
        }
    }

    private function checkOrderHistory(Order $order): void
    {
        if (! $order->user_id) {
            return;
        }

        $prior = Order::where('user_id', $order->user_id)
            ->where('id', '!=', $order->id)
            ->get();

        // A first order is normal for every customer at some point — not a fraud signal on its own.
        if ($prior->isEmpty()) {
            return;
        }

        // New shipping city vs all prior orders
        if ($order->shipping_city) {
            $knownCities = $prior->pluck('shipping_city')->filter()->unique();
            if (! $knownCities->contains($order->shipping_city)) {
                $this->flag('New shipping city: '.$order->shipping_city, 10);
            }
        }
    }

    private function checkPaymentMethod(Order $order): void
    {
        if ($order->payment_method === 'cod' && $order->total >= 3000) {
            $this->flag('Cash on delivery for high-value order', 15);
        }
    }

    private function checkRapidOrdering(Order $order): void
    {
        if (! $order->user_id) {
            return;
        }

        $orderCreatedAt = $order->created_at ?? now();

        $recentCount = Order::where('user_id', $order->user_id)
            ->where('id', '!=', $order->id)
            ->where('created_at', '>=', $orderCreatedAt->copy()->subHour())
            ->where('created_at', '<=', $orderCreatedAt)
            ->count();

        if ($recentCount >= 3) {
            $this->flag($recentCount.' other orders placed within 1 hour', 35);
        } elseif ($recentCount >= 2) {
            $this->flag($recentCount.' other orders placed within 1 hour', 20);
        } elseif ($recentCount >= 1) {
            $this->flag('1 other order placed within 1 hour', 10);
        }

        $todayCount = Order::where('user_id', $order->user_id)
            ->where('id', '!=', $order->id)
            ->whereDate('created_at', $orderCreatedAt->toDateString())
            ->count();

        if ($todayCount >= 5) {
            $this->flag($todayCount.' orders placed on the same day', 20);
        }
    }

    private function checkItemCount(Order $order): void
    {
        $totalQty = $order->items->sum('quantity');
        if ($totalQty >= 50) {
            $this->flag('Unusually large quantity ('.$totalQty.' items)', 15);
        }
    }

    private function checkCancellationHistory(Order $order): void
    {
        if (! $order->user_id) {
            return;
        }

        $cancelledCount = Order::where('user_id', $order->user_id)
            ->where('id', '!=', $order->id)
            ->where('status', 'cancelled')
            ->count();

        if ($cancelledCount >= 5) {
            $this->flag($cancelledCount.' previously cancelled orders from this customer', 35);
        } elseif ($cancelledCount >= 3) {
            $this->flag($cancelledCount.' previously cancelled orders from this customer', 20);
        } elseif ($cancelledCount >= 1) {
            $this->flag($cancelledCount.' previously cancelled order(s) from this customer', 10);
        }
    }

    private function flag(string $message, int $points): void
    {
        $this->flags[] = $message;
        $this->score += $points;
    }

    private function riskLevel(int $score): string
    {
        return match (true) {
            $score >= 60 => 'critical',
            $score >= 50 => 'high',
            $score >= 20 => 'medium',
            default => 'low',
        };
    }
}
