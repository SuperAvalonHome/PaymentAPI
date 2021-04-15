<?php
namespace SuperAvalon\Payment;

use SuperAvalon\Payment\Interfaces\PaymentHandlerInterface;

/**
 * WechatH5Payment
 * 微信H5支付中间层
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
class WechatH5Payment extends WechatDriver implements PaymentHandlerInterface {
    
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
	 * @param array $tradeData 支付订单参数
	 * @return array response
	 *      code    int         状态码
	 *      msg     string      接口消息
	 *      data    array       微信小程序发起支付报文
	 */
	public function doPay($tradeData = array())
	{
		$totalFee = (int)bcmul($tradeData['trade_amount'], 100);
        
        $aSceneInfo = [
            'h5_info' => [
                'type'  => 'Wap',
                'wap_url'  => $this->_config['wap_url'],
                'wap_name'  => $this->_config['wap_name']
        ]];
        
        $aResponse = $this->unified_order([
            'trade_type' => 'MWEB',
            'total_fee' => $totalFee,
            'subject' => $tradeData['subject'],
            'trade_no' => $tradeData['payment_no'],
            'scene_info' => json_encode($aSceneInfo),
            'openid' => $tradeData['openid'] ?? '',
            'subject' => $tradeData['subject'] ?? '',
            'pay_body' => $tradeData['pay_body'] ?? '',
        ]);
        
        if ($aResponse['code'] == 200) {
            // todo
            return $this->retval(['code' => 200, 'data' => $aResponse, 'msg' => 'success.']);
        } else {
            return $this->retval(['code' => 500, 'data' => null, 'msg' => 'unifiedorder error.']);
        }
	}
    
    
	/**
	 * 订单查询接口
	 *
	 * @param array $queryParams 订单查询参数
	 * @return array 
	 */
    public function doQuery($queryParams = array())
    {
        return $this->order_query($queryParams['trade_no']);
    }
    
    
	/**
	 * 订单退款申请接口
	 *
	 * @param array $refundParams 退款单数据
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
	 * @param  mixed $input 通知报文
	 * @return array 
	 */
    public function refundNotifyHandler(&$input)
    {
        return;
    }
}
