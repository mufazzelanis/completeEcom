<?php

namespace App\Support;

use App\Models\Crm\CrmContact;
use App\Services\Crm\CrmMetrics;

/**
 * View helpers for the CRM screens. Colour names map onto the bg/text/ring-{colour}-{100|500|600|700}
 * classes that tailwind.config.js safelists, so any colour picked at runtime (tags, segments,
 * stages) still has its CSS in the compiled build.
 */
class CrmUi
{
    public static function badge(?string $color): string
    {
        $c = in_array($color, ['gray', 'red', 'orange', 'amber', 'green', 'teal', 'blue', 'indigo', 'purple', 'pink', 'sky', 'yellow'], true) ? $color : 'gray';

        return "bg-{$c}-100 text-{$c}-700";
    }

    public static function dot(?string $color): string
    {
        $c = in_array($color, ['gray', 'red', 'orange', 'amber', 'green', 'teal', 'blue', 'indigo', 'purple', 'pink', 'sky', 'yellow'], true) ? $color : 'gray';

        return "bg-{$c}-500";
    }

    public static function lifecycle(string $stage): array
    {
        return CrmContact::LIFECYCLE[$stage] ?? ['label' => ucfirst($stage), 'color' => 'gray'];
    }

    public static function rfmColor(?string $segment): string
    {
        return CrmMetrics::SEGMENT_INFO[$segment]['color'] ?? 'gray';
    }

    public static function money(float|int|string|null $n): string
    {
        return '৳' . number_format((float) $n);
    }

    public static function churnColor(int $risk): string
    {
        return $risk >= 70 ? 'red' : ($risk >= 40 ? 'amber' : 'green');
    }

    /** Colour-coded avatar background from the name, so the same person always looks the same. */
    public static function avatar(string $name): string
    {
        $palette = ['bg-indigo-500', 'bg-teal-500', 'bg-pink-500', 'bg-amber-500', 'bg-purple-500', 'bg-sky-500', 'bg-orange-500', 'bg-green-500'];

        return $palette[crc32($name) % count($palette)];
    }
}
