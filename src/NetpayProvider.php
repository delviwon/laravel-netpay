<?php

namespace Lewee\Netpay;

use Illuminate\Support\ServiceProvider;

class NetpayProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton('netpay', function ($app) {
            return new Netpay($app['session'], $app['config']);
        });
    }

    public function provides()
    {
        return [
            'netpay',
        ];
    }
}
