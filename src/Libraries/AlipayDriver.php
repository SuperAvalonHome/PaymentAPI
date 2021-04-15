<?php

namespace SuperAvalon\Payment;

use SuperAvalon\Payment\Utils\PaymentUtils;
use SuperAvalon\Payment\Utils\CommonUtils;
use SuperAvalon\Payment\Interfaces\PaymentHandlerInterface;


/**
 * AlipayDriver
 * 支付宝底层抽象类
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
abstract class AlipayDriver implements PaymentHandlerInterface {
    
    use PaymentUtils, CommonUtils;
    
	protected $_config;
    
	protected $_extra;
    
	/**
	 * Class constructor
	 *
	 * @param	array	$apiConfig	Configuration parameters
	 * @return	void
	 */
	public function __construct(&$apiConfig)
	{
		$this->_config =& $apiConfig;
	}
    
    
    public function getExtraFields()
    {
        return $this->_extra;
    }
    
    
    
    
    
	/**
	 * 手机网站支付接口2.0
	 *
	 * @param array $payParams 
     *      method          string          接口名称[Y]
     *      auth_code       string          付款条码[N]
     *      product_code    string          销售产品码[N]    当面付快捷版:OFFLINE_PAYMENT      其它:FACE_TO_FACE_PAYMENT
	 * @return array $retval
	 *      code            int             状态码
	 *      type            string          支付凭证类型：prepay_id|code_url|mweb_url
	 *      data            string          支付凭证
	 */
	protected function wapPay($payParams, $bizParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.trade.wap.pay';
		$apiParams['format']	        = $this->_config['api_format'];
		$apiParams['charset']	        = $this->_config['api_charset'];
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = $this->_config['api_version'];
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
		$apiParams['return_url']	    = $this->_config['return_url'] . '/' . $payParams['mer_no'];
		$apiParams['notify_url']	    = $this->_config['notify_url'] . '/' . $payParams['mer_no'];
        
        $apiParams['biz_content'] = json_encode($bizParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign'] = $this->sign($apiParams, $apiParams['sign_type']);
        
        $html = $this->buildRequestForm($this->_config['order_api'], $apiParams);
        
        return $html;
	}
    
    
	/**
	 * 统一收单交易创建接口
	 *
	 * @param array $payParams 
     *      method          string          接口名称[Y]
     *      auth_code       string          付款条码[N]
     *      product_code    string          销售产品码[N]    当面付快捷版:OFFLINE_PAYMENT      其它:FACE_TO_FACE_PAYMENT
	 * @return array $retval
	 *      code            int             状态码
	 *      type            string          支付凭证类型：prepay_id|code_url|mweb_url
	 *      data            string          支付凭证
	 */
	protected function tradeCrate($payParams, $bizParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.trade.create';
		$apiParams['format']	        = $this->_config['api_format'];
		$apiParams['charset']	        = $this->_config['api_charset'];
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = $this->_config['api_version'];
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
		$apiParams['notify_url']	    = $this->_config['notify_url'] . '/' . $this->_config['sys_mer_no'];
        
        $apiParams['biz_content']       = json_encode($bizParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        $postParams['biz_content'] = $apiParams['biz_content'];
        
        unset($apiParams['biz_content']);
        
		$requestUrl = $this->_config['order_api'] . "?";
		foreach ($apiParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
        
		$requestUrl = substr($requestUrl, 0, -1);
        $sResponse = $this->request($requestUrl, $postParams);
        $aResponse = json_decode($sResponse, true);
        
        return $aResponse;
	}
    
    
	/**
	 * 统一收单交易支付接口
	 *
	 * @param array $payParams 
     *      method          string          接口名称[Y]
     *      auth_code       string          付款条码[N]
     *      product_code    string          销售产品码[N]    当面付快捷版:OFFLINE_PAYMENT      其它:FACE_TO_FACE_PAYMENT
	 * @return array $retval
	 *      code            int             状态码
	 *      type            string          支付凭证类型：prepay_id|code_url|mweb_url
	 *      data            string          支付凭证
	 */
	protected function tradePay($payParams, $bizParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.trade.pay';
		$apiParams['format']	        = $this->_config['api_format'];
		$apiParams['charset']	        = $this->_config['api_charset'];
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = $this->_config['api_version'];
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
		$apiParams['notify_url']	    = $this->_config['notify_url'] . '/' . $this->_config['sys_mer_no'];
        
        $apiParams['biz_content']       = json_encode($bizParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        $postParams['biz_content'] = $apiParams['biz_content'];
        
        unset($apiParams['biz_content']);
        
		$requestUrl = $this->_config['order_api'] . "?";
		foreach ($apiParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
        
		$requestUrl = substr($requestUrl, 0, -1);
        $sResponse  = $this->request($requestUrl, $postParams);
        $aResponse  = json_decode($sResponse, true);
        $postSign   = $aResponse['sign'];
        $aResponse  = $aResponse['alipay_trade_pay_response'];
        
        if ($aResponse['code'] == '10000') {
           $retval = ['code' => 200, 'data' => $aResponse, 'msg' => 'Success.'];
        } else {
           $retval = ['code' => 403, 'data' => $aResponse, 'msg' => 'Error.'];
        }
        
        return $retval;
	}
    
     
    
	/**
	 * 统一收单线下交易预创建
	 *
	 * @param array $payParams 
     *      method          string          接口名称[Y]
     *      auth_code       string          付款条码[N]
	 * @return array $retval
	 *      code            int             状态码
	 *      data            string          支付凭证
	 */
	protected function preCreate($payParams, $bizParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.trade.precreate';
		$apiParams['format']	        = $this->_config['api_format'];
		$apiParams['charset']	        = $this->_config['api_charset'];
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = $this->_config['api_version'];
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
		$apiParams['notify_url']	    = $this->_config['notify_url'] . '/' . $this->_config['sys_mer_no'];
        
        $apiParams['biz_content']       = json_encode($bizParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        $postParams['biz_content'] = $apiParams['biz_content'];
        
        unset($apiParams['biz_content']);
        
		$requestUrl = $this->_config['order_api'] . "?";
		foreach ($apiParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
        
		$requestUrl = substr($requestUrl, 0, -1);
        $sResponse  = $this->request($requestUrl, $postParams);
        $aResponse  = json_decode($sResponse, true);
        $postSign   = $aResponse['sign'];
        $aResponse  = $aResponse['alipay_trade_precreate_response'];
        
        if ($aResponse['code'] == '10000') {
           $retval = ['code' => 200, 'data' => $aResponse, 'msg' => 'Success.'];
        } else {
           $retval = ['code' => 403, 'data' => $aResponse, 'msg' => 'Error.'];
        }
        
        return $retval;
	}
    
    
	/**
	 * app支付接口
	 * @link    https://opendocs.alipay.com/apis/api_1/alipay.trade.app.pay
     *
	 * @param array $payParams 
     *      method          string          接口名称[Y]
     *      auth_code       string          付款条码[N]
     *      product_code    string          销售产品码[N]    当面付快捷版:OFFLINE_PAYMENT      其它:FACE_TO_FACE_PAYMENT
	 * @return array $retval
	 *      code            int             状态码
	 *      type            string          支付凭证类型：prepay_id|code_url|mweb_url
	 *      data            string          支付凭证
	 */
	protected function tradeAppPay($payParams, $bizParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.trade.app.pay';
		$apiParams['format']	        = $this->_config['api_format'];
		$apiParams['charset']	        = $this->_config['api_charset'];
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = $this->_config['api_version'];
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
		$apiParams['notify_url']	    = $this->_config['notify_url'] . '/' . $this->_config['sys_mer_no'];
        
        $apiParams['biz_content']       = json_encode($bizParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        return http_build_query($apiParams);
	}
    
    
	/**
	 * 统一收单线下交易查询
	 *
	 * @param string $tradeNo   订单支付单号 
	 * @return array $retval
	 *      code    int         状态码
	 *      data    string      接口返回报文
	 */
    public function tradeQuery($payParams, $bizParams)
    {
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.trade.query';
		$apiParams['format']	        = $this->_config['api_format'];
		$apiParams['charset']	        = $this->_config['api_charset'];
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = $this->_config['api_version'];
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
		$apiParams['notify_url']	    = $this->_config['notify_url'] . '/' . $this->_config['sys_mer_no'];
        
        $apiParams['biz_content']       = json_encode($bizParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        $postParams['biz_content'] = $apiParams['biz_content'];
        
        unset($apiParams['biz_content']);
        
		$requestUrl = $this->_config['order_api'] . "?";
		foreach ($apiParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
        
		$requestUrl = substr($requestUrl, 0, -1);
        $sResponse  = $this->request($requestUrl, $postParams);
        $aResponse  = json_decode($sResponse, true);
        $postSign   = $aResponse['sign'];
        $aResponse  = $aResponse['alipay_trade_query_response'];
        
        if ($aResponse['code'] == '10000') {
           $retval = ['code' => 200, 'data' => $aResponse, 'msg' => 'Success.'];
        } else {
           $retval = ['code' => 403, 'data' => $aResponse, 'msg' => 'Error.'];
        }
        
        return $retval;
    }
    
    
	/**
	 * 统一收单交易退款接口
	 *
	 * @param string $tradeNo   订单支付单号 
	 * @return array $retval
	 *      code    int         状态码
	 *      data    string      接口返回报文
	 */
    public function tradeRefund($payParams, $bizParams)
    {
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.trade.refund';
		$apiParams['format']	        = $this->_config['api_format'];
		$apiParams['charset']	        = $this->_config['api_charset'];
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = $this->_config['api_version'];
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
		$apiParams['notify_url']	    = $this->_config['notify_url'] . '/' . $this->_config['sys_mer_no'];
        
        $apiParams['biz_content']       = json_encode($bizParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        $postParams['biz_content'] = $apiParams['biz_content'];
        
        unset($apiParams['biz_content']);
        
		$requestUrl = $this->_config['order_api'] . "?";
		foreach ($apiParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
        
		$requestUrl = substr($requestUrl, 0, -1);
        $sResponse  = $this->request($requestUrl, $postParams);
        $aResponse  = json_decode($sResponse, true);
        $postSign   = $aResponse['sign'];
        $aResponse  = $aResponse['alipay_trade_refund_response'];
        
        if ($aResponse['code'] == '10000') {
           $retval = ['code' => 200, 'data' => $aResponse, 'msg' => 'Success.'];
        } else {
           $retval = ['code' => 403, 'data' => $aResponse, 'msg' => 'Error.'];
        }
        
        return $retval;
    }
    
    
	/**
	 * 统一收单交易退款查询
	 *
	 * @param string $tradeNo   订单支付单号 
	 * @return array $retval
	 *      code    int         状态码
	 *      data    string      接口返回报文
	 */
    public function tradeRefundQuery($payParams, $bizParams)
    {
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.trade.fastpay.refund.query';
		$apiParams['format']	        = $this->_config['api_format'];
		$apiParams['charset']	        = $this->_config['api_charset'];
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = $this->_config['api_version'];
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
		$apiParams['notify_url']	    = $this->_config['notify_url'] . '/' . $this->_config['sys_mer_no'];
        
        $apiParams['biz_content']       = json_encode($bizParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        $postParams['biz_content'] = $apiParams['biz_content'];
        
        unset($apiParams['biz_content']);
        
		$requestUrl = $this->_config['order_api'] . "?";
		foreach ($apiParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
        
		$requestUrl = substr($requestUrl, 0, -1);
        $sResponse  = $this->request($requestUrl, $postParams);
        $aResponse  = json_decode($sResponse, true);
        $postSign   = $aResponse['sign'];
        $aResponse  = $aResponse['alipay_trade_fastpay_refund_query_response'];
        
        if ($aResponse['code'] == '10000') {
           $retval = ['code' => 200, 'data' => $aResponse, 'msg' => 'Success.'];
        } else {
           $retval = ['code' => 403, 'data' => $aResponse, 'msg' => 'Error.'];
        }
        
        return $retval;
    }
    
    
	/**
	 * 分账关系绑定
	 *
	 * @param array $payParams 
     *      method          string          接口名称[Y]
	 * @return array $retval
	 *      code            int             状态码
	 *      data            string          支付凭证
	 */
	protected function royaltyBind($aParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.trade.royalty.relation.bind';
		$apiParams['format']	        = 'JSON';
		$apiParams['charset']	        = 'UTF-8';
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = '1.0';
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
        
        $apiParams['biz_content']       = json_encode($aParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        $postParams['biz_content'] = $apiParams['biz_content'];
        
        unset($apiParams['biz_content']);
        
		$requestUrl = $this->_config['order_api'] . "?";
		foreach ($apiParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
        
		$requestUrl = substr($requestUrl, 0, -1);
        $sResponse = $this->request($requestUrl, $postParams);
        $aResponse = json_decode($sResponse, true);
        
        return $aResponse;
	}
    
    
	/**
	 * 统一收单交易结算接口
	 *
	 * @param array $payParams 
     *      method          string          接口名称[Y]
	 * @return array $retval
	 *      code            int             状态码
	 *      data            string          支付凭证
	 */
	protected function orderSettle($aParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.trade.order.settle';
		$apiParams['format']	        = 'JSON';
		$apiParams['charset']	        = 'UTF-8';
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = '1.0';
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
        
        $apiParams['biz_content']       = json_encode($aParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        $postParams['biz_content'] = $apiParams['biz_content'];
        
        unset($apiParams['biz_content']);
        
		$requestUrl = $this->_config['order_api'] . "?";
		foreach ($apiParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
        
		$requestUrl = substr($requestUrl, 0, -1);
        $sResponse = $this->request($requestUrl, $postParams);
        $aResponse = json_decode($sResponse, true);
        
        return $aResponse;
	}
     
    
	/**
	 * 资金授权冻结接口
	 *
	 * @param array $payParams 
     *      method          string          接口名称[Y]
	 * @return array $retval
	 *      code            int             状态码
	 *      data            string          支付凭证
	 */
	protected function fundFreeze($aParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.fund.auth.order.freeze';
		$apiParams['format']	        = 'JSON';
		$apiParams['charset']	        = 'UTF-8';
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = '1.0';
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
        
        $apiParams['biz_content']       = json_encode($aParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        $postParams['biz_content'] = $apiParams['biz_content'];
        
        unset($apiParams['biz_content']);
        
		$requestUrl = $this->_config['order_api'] . "?";
		foreach ($apiParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
        
		$requestUrl = substr($requestUrl, 0, -1);
        $sResponse = $this->request($requestUrl, $postParams);
        $aResponse = json_decode($sResponse, true);
        
        return $aResponse;
	}
    
     
	/**
	 * 统一转账接口
	 *
	 * @param array $payParams 
     *      method          string          接口名称[Y]
	 * @return array $retval
	 *      code            int             状态码
	 *      data            string          支付凭证
	 */
	protected function fundTransfer($aParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.fund.trans.uni.transfer';
		$apiParams['format']	        = 'JSON';
		$apiParams['charset']	        = 'UTF-8';
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = '1.0';
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
        
        $apiParams['biz_content']       = json_encode($aParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        $postParams['biz_content'] = $apiParams['biz_content'];
        
        unset($apiParams['biz_content']);
        
		$requestUrl = $this->_config['order_api'] . "?";
		foreach ($apiParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
        
		$requestUrl = substr($requestUrl, 0, -1);
        $sResponse = $this->request($requestUrl, $postParams);
        $aResponse = json_decode($sResponse, true);
        
        return $aResponse;
	}
    
    
	/**
	 * 查询转账订单接口
	 *
	 * @param array $payParams 
     *      method          string          接口名称[Y]
	 * @return array $retval
	 *      code            int             状态码
	 *      data            string          支付凭证
	 */
	protected function fundTransQuery($aParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['app_id']		    = $this->_config['app_id'];
		$apiParams['method']	        = 'alipay.fund.trans.order.query';
		$apiParams['format']	        = 'JSON';
		$apiParams['charset']	        = 'UTF-8';
		$apiParams['sign_type']	        = $this->_config['sign_type'] ?? 'RSA2';
		$apiParams['version']	        = '1.0';
		$apiParams['timestamp']	        = date("Y-m-d H:i:s");
        
        $apiParams['biz_content']       = json_encode($aParams, JSON_UNESCAPED_UNICODE);
        $apiParams['sign']              = $this->sign($apiParams, $apiParams['sign_type']);
        
        $postParams['biz_content'] = $apiParams['biz_content'];
        
        unset($apiParams['biz_content']);
        
		$requestUrl = $this->_config['order_api'] . "?";
		foreach ($apiParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
        
		$requestUrl = substr($requestUrl, 0, -1);
        $sResponse = $this->request($requestUrl, $postParams);
        $aResponse = json_decode($sResponse, true);
        
        return $aResponse;
	}
    
     
	/**
	 * 微信支付/退款异步通知
	 *
	 * @param string $input     通知报文 
	 * @return array $retval
	 *      code    int         状态码
	 *      data    string      接口返回报文
	 */
	public function notify(&$input)
	{
        
	}
    
    
	/**
	 * 退款申请接口
	 *
	 * @param array $refundParams   退款单数据 
	 *      trade_no    string      订单支付单号
	 *      refund_no    string     订单退款单号
	 *      total_fee    float      订单实付金额
	 *      refund_fee   float      退款申请金额
	 * @return array $retval
	 *      code    int         状态码
	 *      data    string      接口返回报文
	 */
    public function refund($refundParams)
    {
    
    }
    
    
	/**
	 * 订单退款状态查询接口
	 *
	 * @param string $refundNo  订单退款单号 
	 * @return array $retval
	 *      code    int         状态码
	 *      data    string      接口返回报文
	 */
    public function refund_query($refundNo)
    {
        
    }
    
    
	/**
	 * 计算签名
     *
	 * @param	array	$data       签名数据
	 * @param	string	$signType   签名类型    md5/sha1
	 * @return	string  签名结果
	 */
	protected function sign($data, $signType = "RSA2")
    {
        $sign = '';
        
        $sData = $this->getSignContent($data);
        
		if ($this->_config['alipay_rsa_private_key']) {
            $res = null;
            $priKey = $this->_config['alipay_rsa_private_key'];
			$priKey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($priKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
			$res = openssl_get_privatekey($priKey);
            
            if ("RSA2" == $signType) {
                openssl_sign($sData, $sign, $res, OPENSSL_ALGO_SHA256);
            } else {
                openssl_sign($sData, $sign, $res);
            }

            if ($res) {
                openssl_free_key($res);
            }
		}
        
		$sign = base64_encode($sign);
		return $sign;
	}
    
    
	public function getSignContent($params)
    {
		ksort($params);

		$stringToBeSigned = "";
		$i = 0;
		foreach ($params as $k => $v) {
			if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}

		unset ($k, $v);
		return $stringToBeSigned;
	}
    
    
	protected function buildRequestForm($gatewayUrl, $para_temp)
    {	
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$gatewayUrl."?charset=UTF-8' method='POST'>";
		foreach($para_temp as $key => $val) {
			if (false === $this->checkEmpty($val)) {
				$val = str_replace("'","&apos;",$val);
				$sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
			}
        }

        $sHtml = $sHtml."<input type='submit' value='ok' style='display:none;''></form>";
		$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
		
		return $sHtml;
	}
    
    
	/**
	 * 校验$value是否非空
	 *  if not set ,return true;
	 *    if is null , return true;
	 **/
	protected function checkEmpty($value)
    {
		if (!isset($value))
			return true;
		if ($value === null)
			return true;
		if (trim($value) === "")
			return true;

		return false;
	}
}
