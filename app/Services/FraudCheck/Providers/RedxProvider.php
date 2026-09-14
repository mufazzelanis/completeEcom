<?php

namespace App\Services\FraudCheck\Providers;

class RedxProvider extends UnverifiedProvider
{
    public function key(): string
    {
        return 'redx';
    }

    public function label(): string
    {
        return 'RedX';
    }
}
