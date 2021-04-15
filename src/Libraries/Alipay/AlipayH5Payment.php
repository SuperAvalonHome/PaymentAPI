<?php
namespace SuperAvalon\Payment;

use SuperAvalon\Payment\Interfaces\PaymentHandlerInterface;

/**
 * AlipayH5Payment
 * 支付宝H5支付中间层
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
class AlipayH5Payment extends AlipayDriver implements PaymentHandlerInterface {
    
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
	 * @return array response
	 *      code    int         状态码
	 *      msg     string      接口消息
	 *      data    array       支付宝小程序发起支付报文
	 */
	public function doPay($payParams = [])
	{
		$apiParams = [];
        $bizParams = [];
        
        // 业务入参
        $bizParams['subject']           = $payParams['subject'];
        $bizParams['body']              = $payParams['pay_body'];
        $bizParams['out_trade_no']      = $payParams['payment_no'];
        $bizParams['total_amount']      = $payParams['trade_amount'];
        $bizParams['timeout_express']   = $payParams['expire_min'] ? $payParams['expire_min'] . 'm' : '30m';
        $bizParams['quit_url']          = $this->_config['return_url'];
        $bizParams['product_code']      = 'QUICK_WAP_WAY';
        
        $apiParams['biz_content'] = json_encode($bizParams, JSON_UNESCAPED_UNICODE);
        
        $apiParams['sign'] = $this->sign($apiParams, $this->_config['sign_type']);
        
        $html = $this->wapPay($payParams, $bizParams);
        
        exit($html);
	}
    
    
	/**
	 * 订单查询接口
	 *
	 * @param array $queryParams 订单查询参数
	 * @return array 
	 */
    public function doQuery($queryParams = array())
    {
        $bizParams = [];
        
        // 业务入参
        $bizParams['out_trade_no'] = $queryParams['trade_no'];
        
        return $this->tradeQuery($queryParams, $bizParams);
    }
    
    
	/**
	 * 订单退款申请接口
	 *
	 * @param array $refundParams 退款单数据
	 * @return array 
	 */
    public function doRefund($refundParams = array())
    {
        $bizParams = [];
        
        // 业务入参
        $bizParams['out_trade_no']      = $refundParams['trade_no'];
        $bizParams['out_request_no']    = $refundParams['refund_no'];
        $bizParams['refund_amount']     = $refundParams['refund_amount'];
        
        return $this->tradeRefund($refundParams, $bizParams);
    }
    
	/**
	 * 订单退款状态查询接口
	 *
	 * @param array $queryParams 订单退款单号
	 * @return array 
	 */
    public function doRefundQuery($queryParams = array())
    {
        $bizParams = [];
        
        // 业务入参
        $bizParams['out_trade_no']      = $queryParams['trade_no'];
        $bizParams['out_request_no']    = $queryParams['refund_no'];
        
        return $this->tradeRefundQuery($queryParams, $bizParams);
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
	 * @param  mixed $input 通知报文
	 * @return array 
	 */
    public function refundNotifyHandler(&$input)
    {
        return;
    }
}
