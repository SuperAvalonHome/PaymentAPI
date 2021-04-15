<?php

namespace SuperAvalon\Payment;

use SuperAvalon\Payment\Utils\PaymentUtils;
use SuperAvalon\Payment\Utils\CommonUtils;
use SuperAvalon\Payment\Interfaces\PaymentHandlerInterface;


/**
 * WechatDriver
 * 微信支付底层抽象类
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
abstract class WechatDriver implements PaymentHandlerInterface {
    
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
	 * 统一下单接口
	 *
	 * @param array $payParams 
	 * @return array $retval
	 *      code    int         状态码
	 *      type    string      支付凭证类型：prepay_id|code_url|mweb_url
	 *      data    string      支付凭证
	 */
	protected function unified_order($payParams)
	{
		$apiParams = [];
        
		$apiParams['body']		    = $payParams['subject'];
		$apiParams['appid']		    = $this->_config['app_id'];
		$apiParams['mch_id']	    = $this->_config['mch_id'];
		$apiParams['total_fee']	    = $payParams['total_fee'];
		$apiParams['trade_type']	= $payParams['trade_type'];
		$apiParams['out_trade_no']  = $payParams['trade_no'];
		$apiParams['notify_url']	= $this->_config['notify_url'];
		$apiParams['nonce_str']	    = $this->create_noncestr(32);
        
        if (isset($payParams['openid'])) {
            $apiParams['openid'] = $payParams['openid'];
        }
        
        if (isset($payParams['scene_info'])) {
            $apiParams['scene_info'] = $payParams['scene_info'];
        }
        
        if (isset($payParams['product_id'])) {
            $apiParams['product_id'] = $payParams['product_id'];
        }
        
        if (isset($payParams['spbill_create_ip'])) {
            $apiParams['spbill_create_ip'] = $payParams['spbill_create_ip'];
        } else {
            $apiParams['spbill_create_ip'] = $this->get_client_ip();
        }
        
        $retval = [];
        $apiParams['sign'] = $this->unified_sign($apiParams, $this->_config['sign_type']);
        
        $xmlInfo = $this->array_to_xml($apiParams);
        $sResponse = $this->request($this->_config['order_api'], $xmlInfo);
        $aResponse = $this->xml_to_array($sResponse);
        
        $this->write_log(__METHOD__, $payParams['trade_no'], $apiParams, $aResponse);
        
		if ($aResponse && $aResponse['return_code'] == 'SUCCESS' && $aResponse['result_code'] == 'SUCCESS') {
			if ($aResponse['trade_type'] == 'JSAPI' || $aResponse['trade_type'] == 'APP' || $aResponse['trade_type'] == 'WAP') {
				$retval = ['code' => 200, 'data' => ['type' => 'prepay_id', 'value' => $aResponse['prepay_id']]];
			} elseif ($aResponse['trade_type'] == 'NATIVE') {
                $retval = ['code' => 200, 'data' => ['type' => 'code_url', 'value' => $aResponse['code_url']]];
			} elseif ($aResponse['trade_type'] == 'MWEB') {
                $retval = ['code' => 200, 'data' => ['type' => 'mweb_url', 'value' => $aResponse['mweb_url']]];
			}
		}
        
        if (empty($retval)) {
            if (isset($aResponse['err_code']) && isset($aResponse['err_code_des'])) {
                $retval = ['code' => 500, 'api_err_code' => $aResponse['err_code'], 'api_error_msg' => $aResponse['err_code_des']];    
            } elseif (isset($aResponse['return_code']) && isset($aResponse['return_msg'])) {
                $retval = ['code' => 500, 'api_err_code' => $aResponse['return_code'], 'api_error_msg' => $aResponse['return_msg']];    
            }
        }
		
		return $retval;
	}
    
     
	/**
	 * 订单支付状态查询接口
	 *
	 * @param string $tradeNo   订单支付单号 
	 * @return array $retval
	 *      code    int         状态码
	 *      data    string      接口返回报文
	 */
    public function order_query($tradeNo)
    {
		$apiParams = [];
        
        $apiParams['out_trade_no']	= $tradeNo;
		$apiParams['appid']		    = $this->_config['app_id'];
		$apiParams['mch_id']		= $this->_config['mch_id'];
		$apiParams['nonce_str']	    = $this->create_noncestr(32);
        $apiParams['sign']          = $this->unified_sign($apiParams, $this->_config['sign_type']);
        
        $xmlInfo = $this->array_to_xml($apiParams);
        $sResponse = $this->request($this->_config['query_api'], $xmlInfo);
        $aResponse = $this->xml_to_array($sResponse);
        
        $this->write_log(__METHOD__, $tradeNo, $apiParams, $aResponse);
        
        $postSign = $aResponse['sign'];
        unset($aResponse['sign']);
        
        $retval = [];
        $paySign = $this->unified_sign($aResponse, $this->_config['sign_type']);
        
        if (strtolower($paySign) != strtolower($postSign)) {
            return ['code' => 407, 'data' => $aResponse, 'msg' => 'Verify Error.'];
		}
        
        if ($aResponse['return_code'] == 'SUCCESS' && $aResponse['result_code'] == 'SUCCESS') {
           $retval = ['code' => 200, 'data' => $aResponse, 'msg' => 'Success.'];
        } else {
           $retval = ['code' => 403, 'data' => $aResponse, 'msg' => 'Error.'];
        }
        
        return $retval;
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
        $aResponse = (array)simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
		
        $postSign = $aResponse['sign'];
        unset($aResponse['sign']);
        
        $retval = [];
        $paySign = $this->unified_sign($aResponse, $this->_config['sign_type']);
        
        if (strtolower($paySign) != strtolower($postSign)) {
            return ['code' => 407, 'data' => $aResponse, 'msg' => 'Verify Error.'];
		}

        if ($aResponse['return_code'] == 'SUCCESS' && $aResponse['result_code'] == 'SUCCESS') {
            $retval = ['code' => 200, 'data' => $aResponse, 'msg' => 'Success.'];
        } else {
            $retval = ['code' => 403, 'data' => $aResponse, 'msg' => 'Error.'];
        }
        
        return $retval;
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
        $apiParams = [];
        $totalFee = (int)bcmul($refundParams['total_fee'], 100);
        $refundFee = (int)bcmul($refundParams['refund_fee'], 100);

        $apiParams['out_trade_no']	= $refundParams['trade_no'];
        $apiParams['out_refund_no']	= $refundParams['refund_no'];
        $apiParams['total_fee']	    = $totalFee;
        $apiParams['refund_fee']	= $refundFee;
        $apiParams['appid']		    = $this->_config['app_id'];
        $apiParams['mch_id']		= $this->_config['mch_id'];
        $apiParams['nonce_str']	    = $this->create_noncestr(32);
        $apiParams['sign']          = $this->unified_sign($apiParams, $this->_config['sign_type']);

        $certFile = [
            'cert' => storage_path($this->_config['refund_ssl_cert']),
            'key' => storage_path($this->_config['refund_ssl_key']),
        ];
        
        $xmlInfo = $this->array_to_xml($apiParams);
        $sResponse = $this->request($this->_config['refund_api'], $xmlInfo, 10, [], $certFile);
        $aResponse = $this->xml_to_array($sResponse);
        
        $this->write_log(__METHOD__, $refundParams['trade_no'], $apiParams, $aResponse);
       
        $postSign = $aResponse['sign'];
        unset($aResponse['sign']);
        
        $retval = [];
        $paySign = $this->unified_sign($aResponse, $this->_config['sign_type']);
        
        if (strtolower($paySign) != strtolower($postSign)) {
            return ['code' => 407, 'data' => $aResponse, 'msg' => 'Verify Error.'];
		}
        
        if ($aResponse['return_code'] == 'SUCCESS' && $aResponse['result_code'] == 'SUCCESS') {
            $retval = ['code' => 200, 'data' => $aResponse, 'msg' => 'Success.'];
        } else {
            $retval = ['code' => 403, 'data' => $aResponse, 'msg' => 'Error.'];
        }
        
        return $retval;
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
		$apiParams = [];
        
        $apiParams['out_refund_no']	= $refundNo;
		$apiParams['appid']		    = $this->_config['app_id'];
		$apiParams['mch_id']		= $this->_config['mch_id'];
		$apiParams['nonce_str']	    = $this->create_noncestr(32);
        $apiParams['sign']          = $this->unified_sign($apiParams, $this->_config['sign_type']);
        
        $xmlInfo = $this->array_to_xml($apiParams);
        $sResponse = $this->request($this->_config['refund_query_api'], $xmlInfo);
        $aResponse = $this->xml_to_array($sResponse);
        
        $this->write_log(__METHOD__, $refundNo, $apiParams, $aResponse);
        
        $postSign = $aResponse['sign'];
        unset($aResponse['sign']);
        
        $retval = [];
        $paySign = $this->unified_sign($aResponse, $this->_config['sign_type']);
        
        if (strtolower($paySign) != strtolower($postSign)) {
            return ['code' => 407, 'data' => $aResponse, 'msg' => 'Verify Error.'];
		}
        
        if ($aResponse['return_code'] == 'SUCCESS' && $aResponse['result_code'] == 'SUCCESS') {
            $retval = ['code' => 200, 'data' => $aResponse, 'msg' => 'Success.'];
        } else {
            $retval = ['code' => 403, 'data' => $aResponse, 'msg' => 'Error.'];
        }
        
        return $retval;
    }
    
    
	/**
	 * 下载对账单
	 * @param string $billDate   对账单日期 
	 * @param string $billType   对账单类型 
	 * @return array $retval
	 *      code    int         状态码
	 *      data    string      接口返回报文
	 */
    protected function downloadbill($billDate, $billType)
    {
        $apiParams = [];

        $apiParams['bill_date']	    = $billDate;
        $apiParams['bill_type']	    = $billType;
        $apiParams['appid']		    = $this->_config['app_id'];
        $apiParams['mch_id']		= $this->_config['mch_id'];
        $apiParams['nonce_str']	    = $this->create_noncestr(32);
        $apiParams['sign']          = $this->unified_sign($apiParams, $this->_config['sign_type']);
        
        $xmlInfo = $this->array_to_xml($apiParams);
        $sResponse = $this->request($this->_config['down_bill_api'], $xmlInfo);
        $aResponse = $this->xml_to_array($sResponse);
        
        $postSign = $aResponse['sign'];
        unset($aResponse['sign']);
        
        $retval = [];
        $paySign = $this->unified_sign($aResponse, 'md5');
        
        if (strtolower($paySign) == strtolower($postSign)) {
			if ($aResponse['return_code'] == 'SUCCESS') {
                $retval = ['code' => 200, 'data' => $aResponse];
			}
		}
        
        if (empty($retval)) {
            $retval = ['code' => 500, 'data' => $aResponse];
        }
        
        return $retval;
    }
    
    
	/**
	 * 计算签名
     *
	 * @param	array	$data       签名数据
	 * @param	string	$signType   签名类型    md5/sha1
	 * @return	string  签名结果
	 */
    private function unified_sign($data, $signType = 'md5')
	{
        $strInfo = '';
        ksort($data);
		foreach ($data as $key => $val) {
            if ($val === '') {
                continue;
            }
			if ($strInfo) {
				$strInfo .= "&" . $key . "=" . $val;
			} else {
				$strInfo = $key . "=" . $val;
			}
		}
        
        if (strtolower($signType) == 'md5') {
            return strtoupper(md5($strInfo . '&key=' . $this->_config['secret_key']));
        } elseif (strtolower($signType) == 'sha1') {
            return sha1($strInfo . '&key=' . $this->_config['secret_key']);
        } else {
            return false;
        }
    }
}
