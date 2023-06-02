<?php

namespace NotificationChannels\Textlocal;

use Illuminate\Support\ServiceProvider;

class TextlocalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Bootstrap code here.
        $this->mergeConfigFrom(
            __DIR__.'/../config/textlocal.php', 'textlocal'
        );


        //* creating a Textlocal instance when needs by app.
        $this->app->when(TextlocalChannel::class)
            ->needs(Textlocal::class)
            ->give(
                function () {
                    $config = config('textlocal');

                    return new Textlocal(
                        $config['username'],
                        $config['hash'],
                        $config['api_key'],
                        $config['country']
                    );
                }
            );
            
    
    
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/textlocal.php', 'textlocal'
        );
    }

}
