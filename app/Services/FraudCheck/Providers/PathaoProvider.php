<?php

namespace App\Services\FraudCheck\Providers;

class PathaoProvider extends UnverifiedProvider
{
    public function key(): string
    {
        return 'pathao';
    }

    public function label(): string
    {
        return 'Pathao Courier';
    }
}
