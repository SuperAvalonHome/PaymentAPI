<?php

namespace SuperAvalon\Payment;

use SuperAvalon\Payment\Interfaces\PaymentHandlerInterface;


/**
 * UnionpayWapPayment
 * 银联H5支付类
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
class UnionpayWapPayment extends UnionpayDriver implements PaymentHandlerInterface {
    
	protected $_config;
    
	/**
	 * Class constructor
	 *
	 * @param	array	$apiParams	Configuration parameters
	 * @return	void
	 */
	public function __construct(&$apiConfig)
	{
		$this->_config =& $apiConfig;
	}
    
    
	/**
	 * 支付接口
	 *
	 * @param array $payParams 支付订单参数
	 * @return void
	 */
	public function doPay($payParams = array())
	{
		$totalFee = (int)bcmul($payParams['trade_amount'], 100);
        
        $aDatagram = $this->datagram([
            'total_fee' => $totalFee,
            'trade_no' => $payParams['payment_no'],
            'trade_time' => date('YmdHis')
        ]);
        
        $this->submit_form($this->_config['front_trans_api'], $aDatagram);
	}
    
    
	/**
	 * 订单查询接口
	 *
	 * @param array $queryParams 订单查询参数
	 *          string $trade_no        支付单号
	 *          string $trade_time      支付时间
	 *          string $trade_amount   支付金额
	 * @return array 
	 */
    public function doQuery($queryParams = array())
    {
        return $this->order_query($queryParams);
    }
    
    
	/**
	 * 订单退款申请接口
	 *
	 * @param array $refundParams
	 * @return array 
	 */
    public function doRefund($refundParams = array())
    {
        return $this->refund($refundParams);
    }
    
	/**
	 * 订单退款状态查询接口
	 *
	 * @param array $queryParams
	 * @return array 
	 */
    public function doRefundQuery($queryParams = array())
    {
        return $this->refund_query($queryParams['refund_no']);
    }
    
	/**
	 * 支付通知报文解析验签
	 *
	 * @param  mixed $input
	 * @return array 
	 */
    public function payNotifyHandler(&$input)
    {
        return $this->notify($input);
    }
    
	/**
	 * 退款通知报文解析验签
	 *
	 * @param mixed $input
	 * @return array
	 */
    public function refundNotifyHandler(&$input)
    {
        return;
    }
}
