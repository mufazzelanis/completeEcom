<?php

namespace App\Database;

use Illuminate\Database\Connectors\MySqlConnector;
use PDOException;

/**
 * The hosting account allows 30 MySQL connections at once (max_user_connections). In a burst —
 * many shoppers at once, or a crawler opening pages in parallel — the 31st request was refused
 * and the visitor saw "Something went wrong" (live Error Log: SQLSTATE[HY000] [1226]). A busy
 * moment lasts well under a second, so instead of failing, wait a moment and try again: 0.25 s,
 * 0.5 s, 0.75 s. Any other connection error is thrown at once, as before.
 */
class RetryingMySqlConnector extends MySqlConnector
{
    public const WAITS_MS = [250, 500, 750];

    public function connect(array $config)
    {
        foreach (self::WAITS_MS as $waitMs) {
            try {
                return $this->attempt($config);
            } catch (PDOException $e) {
                if (!self::isConnectionLimit($e)) {
                    throw $e;
                }
                usleep($waitMs * 1000);
            }
        }

        return $this->attempt($config); // last try: its error (if any) is the one reported
    }

    /** One real connection attempt (a seam for tests). */
    protected function attempt(array $config)
    {
        return parent::connect($config);
    }

    /** 1226 = user's max_user_connections reached, 1040 = server's max_connections reached */
    public static function isConnectionLimit(\Throwable $e): bool
    {
        return (bool) preg_match('/\b(1226|1040)\b|max_user_connections|Too many connections/i', $e->getMessage());
    }
}
