<?php

namespace Sentry\SentryLaravel;

use Log;
use Illuminate\Support\ServiceProvider;

class SentryLaravelServiceProvider extends ServiceProvider
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
        $app = $this->app;

        // Laravel 4.x compatibility
        if (version_compare($app::VERSION, '5.0') < 0) {
            $this->package('sentry/sentry-laravel', 'sentry');

            $app->error(function (\Exception $e) use ($app) {
                $app['sentry']->captureException($e);
            });

            $app->fatal(function ($e) use ($app) {
                $app['sentry']->captureException($e);
            });
        } else {
            // the default configuration file
            $this->publishes(array(
                __DIR__ . '/config.php' => config_path('sentry.php'),
            ), 'config');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('sentry', function ($app) {
            // sentry::config is Laravel 4.x
            $user_config = $app['config']['sentry'] ?: $app['config']['sentry::config'];

            $config = array_merge(array(
                'environment' => $app->environment(),
                'prefixes' => array(base_path()),
                'app_path' => app_path(),
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

            Log::info('Sentry SDK configured to report to ' . $client->server);

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