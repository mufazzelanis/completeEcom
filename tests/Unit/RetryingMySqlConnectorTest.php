<?php

namespace Tests\Unit;

use App\Database\RetryingMySqlConnector;
use PDOException;
use Tests\TestCase;

/** Live Error Log: "[1226] User … has exceeded the 'max_user_connections' resource (current value: 30)". */
class RetryingMySqlConnectorTest extends TestCase
{
    private function connector(array $failures): RetryingMySqlConnector
    {
        return new class($failures) extends RetryingMySqlConnector {
            public int $tries = 0;

            public function __construct(private array $failures) {}

            protected function attempt(array $config)
            {
                $this->tries++;
                if ($e = array_shift($this->failures)) {
                    throw $e;
                }
                return 'connected';
            }
        };
    }

    public function test_it_is_the_connector_the_app_uses(): void
    {
        $this->assertInstanceOf(RetryingMySqlConnector::class, app('db.connector.mysql'));
    }

    public function test_a_full_connection_limit_is_waited_out(): void
    {
        $limit = new PDOException("SQLSTATE[HY000] [1226] User 'x' has exceeded the 'max_user_connections' resource (current value: 30)");
        $c = $this->connector([$limit, $limit]);

        $this->assertSame('connected', $c->connect([]));
        $this->assertSame(3, $c->tries);
    }

    public function test_it_gives_up_after_the_last_wait(): void
    {
        $limit = new PDOException('SQLSTATE[HY000] [1040] Too many connections');
        $c = $this->connector(array_fill(0, 10, $limit));

        $this->expectExceptionMessage('Too many connections');
        try {
            $c->connect([]);
        } finally {
            $this->assertSame(count(RetryingMySqlConnector::WAITS_MS) + 1, $c->tries);
        }
    }

    public function test_other_errors_are_not_retried(): void
    {
        $c = $this->connector([new PDOException("SQLSTATE[HY000] [1045] Access denied for user 'x'")]);

        $this->expectExceptionMessage('Access denied');
        try {
            $c->connect([]);
        } finally {
            $this->assertSame(1, $c->tries);
        }
    }
}
