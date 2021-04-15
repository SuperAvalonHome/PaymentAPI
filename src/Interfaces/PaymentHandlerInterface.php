<?php

namespace SuperAvalon\Payment\Interfaces;

/**
 * PaymentHandlerInterface
 *
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
interface PaymentHandlerInterface {

	public function doPay($payParams = array());
	public function doQuery($queryParams = array());
	public function doRefund($refundParams = array());
	public function doRefundQuery($queryParams = array());
	public function payNotifyHandler(&$input);
	public function refundNotifyHandler(&$input);
}
