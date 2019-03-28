<?php

namespace Wired00\CustomQueue;

use Illuminate\Support\ServiceProvider;
use Wired00\CustomQueue\Connectors\CustomSqsConnector;

class CustomQueueServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes(
            [
                __DIR__.'/../config/customqueue.php' => config_path('customqueue.php'),
            ]
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->booted(
            function () {
                /**
                 * @var \Illuminate\Queue\QueueManager
                 */
                $manager = $this->app['queue'];
                $manager->addConnector(
                    'custom-sqs',
                    function () {
                        return new CustomSqsConnector();
                    }
                );
            }
        );
    }
}
