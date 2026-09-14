<?php

namespace App\Services\FraudCheck\Providers;

class EcourierProvider extends UnverifiedProvider
{
    public function key(): string
    {
        return 'ecourier';
    }

    public function label(): string
    {
        return 'eCourier';
    }
}
