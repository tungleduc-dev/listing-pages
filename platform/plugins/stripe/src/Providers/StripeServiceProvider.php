<?php

namespace Botble\Stripe\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class StripeServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register()
    {
        if (class_exists('Laravel\Cashier\Cashier')) {
            Cashier::ignoreMigrations();
        }
    }

    public function boot()
    {
        if (is_plugin_active('payment')) {
            $this->setNamespace('plugins/stripe')
                ->loadHelpers()
                ->loadAndPublishViews()
                ->publishAssets();

            $this->app->register(HookServiceProvider::class);
        }
    }
}
