<?php

namespace NotificationChannels\Textlocal;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Application;

class TextlocalServiceProvider extends ServiceProvider implements DeferrableProvider
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


        $this->app->bind(Textlocal::class, function () {
            return new Textlocal(
                config('textlocal')['username'],
                config('textlocal')['hash'],
                config('textlocal')['api_key'],
                config('textlocal')['country']
            );
        });

        
        $this->app->singleton(TextlocalChannel::class, function (Application $app) {
            return new TextlocalChannel(
                $app->make(Textlocal::class)
            );
        });


        //* creating a Textlocal instance when needs by app.
       /* $this->app->when(TextlocalChannel::class)
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
            );*/
            
    
    
    }
    
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            Textlocal::class,
            TextlocalChannel::class,
        ];
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
