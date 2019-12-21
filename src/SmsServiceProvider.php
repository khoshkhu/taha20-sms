<?php

namespace Taha20\Sms;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
        $this->publishes([
            __DIR__.'/../migrations' => database_path('migrations')
        ],'migrations');

        $this->publishes([
            __DIR__.'/../config/sms.php' => config_path('sms.php')
        ],'config');

        $this->app->singleton('SmsSender',function (){
            return new SmsSender();
        });
    }
}
