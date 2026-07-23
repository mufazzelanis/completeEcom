<?php

namespace App\Support;

class IpMatcher
{
    /**
     * Checks an IP against a newline-separated allowlist of plain IPs and/or CIDR
     * ranges (e.g. 203.0.113.0/24). An empty list matches everything — callers
     * decide what "not configured" should mean for their own feature.
     */
    public static function matchesAny(?string $ip, string $list): bool
    {
        if (!$ip) {
            return false;
        }

        foreach (preg_split('/\r\n|\r|\n/', $list) as $line) {
            $entry = trim($line);
            if ($entry === '') {
                continue;
            }

            if (!str_contains($entry, '/')) {
                if ($entry === $ip) {
                    return true;
                }
                continue;
            }

            [$subnet, $bits] = explode('/', $entry, 2);
            if (self::ipInCidr($ip, $subnet, (int) $bits)) {
                return true;
            }
        }

        return false;
    }

    private static function ipInCidr(string $ip, string $subnet, int $bits): bool
    {
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $totalBits = strlen($ipBin) * 8;
        $bits = max(0, min($bits, $totalBits));
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = chr((0xFF << (8 - $remainder)) & 0xFF);

        return (substr($ipBin, $bytes, 1) & $mask) === (substr($subnetBin, $bytes, 1) & $mask);
    }
}
