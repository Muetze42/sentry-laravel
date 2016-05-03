<?php

namespace Sentry\SentryLaravel;

use Illuminate\Support\ServiceProvider;

class SentryLumenServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->configure('sentry');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('sentry', function ($app) {
            $user_config = $app['config']['sentry'];

            $config = array_merge(array(
                'environment' => $app->environment(),
                'prefixes' => array(base_path()),
                'app_path' => base_path() . '/app',
            ), $user_config);

            $client = new \Raven_Client($config);

            // bind user context if available
            try {
                if ($app['auth']->check()) {
                    $user = $app['auth']->user();
                    $client->user_context(array(
                        'id' => $user->getAuthIdentifier(),
                    ));
                }
            } catch (\Exception $e) {
            }

            return $client;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('sentry');
    }
}