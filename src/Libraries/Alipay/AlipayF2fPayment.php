<?php
namespace SuperAvalon\Payment;

use SuperAvalon\Payment\Interfaces\PaymentHandlerInterface;

/**
 * AlipayF2FPayment
 * 支付宝当面付支付中间层
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
class AlipayF2fPayment extends AlipayDriver implements PaymentHandlerInterface {
    
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
     *      alipay_scene         付款类型
     *      auth_code           付款条码
	 * @return array response
	 *      code    int         状态码
	 *      msg     string      接口消息
	 *      data    array       支付宝小程序发起支付报文
	 */
	public function doPay($payParams = array())
	{
        $bizParams = [];
        
        // 业务入参
        $bizParams['out_trade_no']      = $payParams['payment_no'];
        $bizParams['total_amount']      = $payParams['trade_amount'];
        $bizParams['auth_code']         = $payParams['auth_code'] ?? '';
        $bizParams['timeout_express']   = $payParams['expire_min'] ? $payParams['expire_min'] . 'm' : '30m';
        $bizParams['subject']           = $payParams['subject'];
        $bizParams['body']              = $payParams['pay_body'];
        $bizParams['body']              = $payParams['body'] ?? '';
        $bizParams['store_id']          = $payParams['store_id'] ?? '';
        $bizParams['operator_id']       = $payParams['operator_id'] ?? '';
        $bizParams['alipay_store_id']   = $payParams['alipay_store_id'] ?? '';
        
        if ($payParams['alipay_scene'] == 'bar_code') {
            if (!$payParams['auth_code']) {
                $response = ['code' => 400, 'data' => null, 'msg' => 'auth_code not.'];
            } else {
                $bizParams['scene'] = 'bar_code';
                $response = $this->tradePay($payParams, $bizParams);
            }
        } else if ($payParams['alipay_scene'] == 'qr_code') {
            $response = $this->preCreate($payParams, $bizParams);
        } else {
            $response = ['code' => 401, 'data' => null, 'msg' => 'alipay_scene not support.'];
        }
        
        return $response;
        
	}
    
    
	public function doPayTest($payParams = array())
	{
        $bizParams = [];
        
        // $this->doBind();
        // die;
        
        // 业务入参
        $bizParams['out_trade_no']      = $payParams['payment_no'];
        // $bizParams['total_amount']      = $payParams['trade_amount'];
        $bizParams['total_amount']      = '0.1';
        //$bizParams['timeout_express']   = $payParams['expire_min'] ? $payParams['expire_min'] . 'm' : '30m';
        $bizParams['subject']           = $payParams['subject'];
        $bizParams['buyer_id']          = '2088102061721002';
        $bizParams['settle_info']['settle_detail_infos'][] = [
                'trans_in_type'         => 'loginName',
                'trans_in'              => 'think2017@qq.com',
                'amount'                => '0.1',
        ];
        
        $response = $this->tradeCrate($payParams, $bizParams);
        
        return $response;
	}
    
    
    public function doBind()
    {
        $bizParams['receiver_list'] = [
            'type'  => 'loginName',
            'account'  => '15801793061',
            'name'  => '测试名称',
        ];
        $bizParams['out_request_no'] = time();
        $response = $this->royaltyBind($bizParams);
        
        return $response;
    }
    
    
    public function doSettle($payParams = array())
    {
        $bizParams['out_request_no'] = $payParams['trade_no'] . '99';
        $bizParams['trade_no'] = $payParams['out_trade_no'];
        $bizParams['royalty_parameters'][] = [
            'trans_in_type'  => 'loginName',
            'trans_in' => 'mr.qiutang@qq.com',
            'amount' => '0.1',
        ];
        
        $response = $this->orderSettle($bizParams);
        
        return $response;
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
        
        $response = $this->tradeQuery($queryParams, $bizParams);
        
        return $response;
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
        
        $response = $this->tradeRefund($refundParams, $bizParams);
        
        return $response;
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
        
        $response = $this->tradeRefundQuery($queryParams, $bizParams);
        
        return $response;
    }
    
	/**
	 * 资金授权冻结接口
	 *
	 * @param array $queryParams 订单退款单号
	 * @return array 
	 */
    public function doFundFreeze($Params = array())
    {
        $bizParams = [];
        
        // 业务入参
        $bizParams['auth_code']         = $queryParams['auth_code'];
        $bizParams['auth_code_type']    = 'bar_code';
        $bizParams['out_request_no']    = $queryParams['refund_no'];
        
        $response = $this->doTradeRefundQuery($queryParams, $bizParams);
        
        return $response;
    }
    
	/**
	 * 统一转账接口
	 *
	 * @param array $aParams 订单退款单号
	 * @return array 
	 */
    public function doTransfer($aParams = array())
    {
        $bizParams = [];
        
        // 业务入参
        $bizParams['out_biz_no']        = $aParams['payment_no'];
        $bizParams['trans_amount']      = $aParams['trade_amount'];
        $bizParams['product_code']      = 'TRANS_ACCOUNT_NO_PWD';
        $bizParams['payee_info']        = ['identity' => $aParams['identity'], 'identity_type' => $aParams['identity_type']];
        
        $response = $this->fundTransfer($aParams, $bizParams);
        
        return $response;
    }
    
	/**
	 * 查询转账订单接口
	 *
	 * @param array $aParams 订单退款单号
	 * @return array 
	 */
    public function doTransQuery($aParams = array())
    {
        $bizParams = [];
        
        // 业务入参
        $bizParams['order_id']          = $aParams['trade_no'];
        
        $response = $this->fundTransQuery($aParams, $bizParams);
        
        return $response;
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
