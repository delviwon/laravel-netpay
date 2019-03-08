<?php

namespace Lewee\Netpay\Facades;
use Illuminate\Support\Facades\Facade;

class Netpay extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'netpay';
    }
}
