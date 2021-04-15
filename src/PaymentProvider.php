<?php
namespace SuperAvalon\Payment;

use Illuminate\Support\ServiceProvider;


/**
 * Payment
 * PaymentProvider
 * PHP 7.3 compatibility interface
 *
 * @package	    SuperAvalon
 * @subpackage	Payment
 * @category	Payment Libraries
 * @Framework   Lumen/Laravel
 * @author	    Eric <think2017@gmail.com>
 * @Github	    https://github.com/SuperAvalon/Payment/
 * @Composer	https://packagist.org/packages/superavalon/payment
 */
class PaymentProvider extends ServiceProvider
{
    public function boot()
    {
        if (!file_exists(config_path('payment.php'))) {
            $this->publishes([
                dirname(__DIR__) . '/config/payment.php' => config_path('payment.php'),
            ], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/payment.php', 'payment'
        );
    }
}