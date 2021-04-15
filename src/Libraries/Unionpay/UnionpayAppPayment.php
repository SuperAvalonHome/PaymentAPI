<?php

namespace SuperAvalon\Payment;

use SuperAvalon\Payment\Interfaces\PaymentHandlerInterface;


/**
 * UnionpayAppPayment
 * 银联app支付中间层
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
class UnionpayAppPayment extends UnionpayDriver implements PaymentHandlerInterface {
    
	protected $_config;
    
    /**
    * Class constructor
    *
    * @param $apiParams array
    * @return void
    */
	public function __construct(&$apiConfig)
	{
		$this->_config =& $apiConfig;
	}
    
    
	/**
	 * 支付接口
	 *
	 * @param array $payParams 支付订单参数
	 * @return array
	 *      code    int         状态码
	 *      msg     string      接口消息
	 *      data    array      支付凭证数据、tn码
	 */
	public function doPay($payParams = array())
	{
		$totalFee = (int)bcmul($payParams['trade_amount'], 100);
        
        $aDatagram = $this->datagram([
            'total_fee' => $totalFee,
            'trade_no' => $payParams['payment_no'],
            'trade_time' => date('YmdHis')
        ]);
        
        $sRequest   = http_build_query($aDatagram);
        $sResponse  = $this->request($this->_config['order_api'], $sRequest);
        $aResponse  = $this->parse_unionpay_params($sResponse);
        
        if ($aResponse['respCode'] == '00') {
            return $this->response(['code' => 200, 'data' => ['tn' => $aResponse['tn']], 'msg' => 'api success.']);
        } else {
            return $this->response(['code' => 500, 'data' => null, 'msg' => 'api error.']);
        }
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
	 * @param string $refundParams 退款单数据
	 * @return array 
	 */
    public function doRefund($refundParams = array())
    {
        return $this->refund($refundParams);
    }
    
    
	/**
	 * 订单退款状态查询接口
	 *
	 * @param array $queryParams 订单退款单号
	 * @return array 
	 */
    public function doRefundQuery($queryParams = array())
    {
        return $this->refund_query($queryParams['refund_no']);
    }
    
    
	/**
	 * 支付通知报文解析验签
	 *
	 * @param  mixed $tradeNo 通知报文
	 * @return array 
	 */
    public function payNotifyHandler(&$input)
    {
        return $this->notify($input);
    }
    
    
	/**
	 * 退款通知报文解析验签
	 *
	 * @param  mixed $input 通知报文
	 * @return array 
	 */
    public function refundNotifyHandler(&$input)
    {
        return;
    }
}
