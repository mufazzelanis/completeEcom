<?php

namespace App\Services\FraudCheck\Providers;

class PaperflyProvider extends UnverifiedProvider
{
    public function key(): string
    {
        return 'paperfly';
    }

    public function label(): string
    {
        return 'Paperfly';
    }
}
