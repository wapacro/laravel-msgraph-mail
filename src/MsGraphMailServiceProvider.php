<?php


namespace LaravelMsGraphMail;

use Illuminate\Support\ServiceProvider;
use LaravelMsGraphMail\Exceptions\CouldNotSendMail;

class MsGraphMailServiceProvider extends ServiceProvider {

    /**
     * Boot any application services.
     * @return void
     * @throws CouldNotSendMail
     */
    public function boot() {
        $this->app->get('mail.manager')->extend('microsoft-graph', function (array $config) {
            if (!isset($config['client']) || !isset($config['secret']) || !isset($config['transport']) || !$this->app['config']->get('mail.from.address', false)) {
                throw CouldNotSendMail::invalidConfig();
            }

            return new MsGraphMailTransport($config);
        });
    }

}
