<?php

namespace SuperAvalon\Payment;

use SuperAvalon\Payment\Utils\PaymentUtils;
use SuperAvalon\Payment\Utils\CommonUtils;
use SuperAvalon\Payment\Interfaces\PaymentHandlerInterface;

/**
 * UnionpayDriver
 * 中国银联支付底层抽象类
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
abstract class UnionpayDriver implements PaymentHandlerInterface {
    
    use PaymentUtils, CommonUtils;
    
	protected $_config;
    
	protected $_runtime = [];
    
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
	 * 组织订单支付申请报文
	 *
	 * @param array $payParams 支付订单参数
	 * @return array response
	 */
    public function datagram($payParams)
    {
        $this->export_signcert();
        
		$datagram['version']		= $this->_config['version'];
		$datagram['encoding']		= $this->_config['encoding'];
		$datagram['certId']		    = $this->_runtime['sign_ssl_cert_id'];
		$datagram['signMethod']		= $this->_config['sign_method'];
		$datagram['txnType']		= '01';
		$datagram['txnSubType']		= '01';
		$datagram['bizType']		= $this->_config['biz_type'];
		$datagram['channelType']	= $this->_config['channel_type'];
		$datagram['frontUrl']		= $this->_config['notify_url'];
		$datagram['backUrl']		= $this->_config['return_url'];
		$datagram['accessType']		= $this->_config['access_type'];
		$datagram['merId']	        = $this->_config['mch_id'];
		$datagram['orderId']	    = $payParams['trade_no'];
		$datagram['txnTime']	    = $payParams['trade_time'];
		$datagram['txnAmt']         = $payParams['total_fee'];
		$datagram['currencyCode']	= $this->_config['currency_code'];
		$datagram['defaultPayType']	= $this->_config['default_type'];
		$datagram['customerIp']	    = $this->get_client_ip();
        
        if (isset($payParams['discountCode'])) {
            $datagram['reqReserved'] = '{discountCode=' . $this->_config['discount_code'] .'}';
        }

        $datagram['signature'] = $this->signature($datagram);

        return $datagram;
    }
    
    
	/**
	 * 订单支付状态查询接口
	 *
	 * @param array $queryParams 订单查询参数
	 *          string $trade_no        支付单号
	 *          string $trade_time      支付时间
	 *          string $trade_amount    支付金额
	 * @return array $aRetval
	 *          code    int         状态码
	 *          data    string      接口返回报文
	 */
    public function order_query($queryParams)
    {
        $this->export_signcert();
        
		$datagram['txnType']		= '00';
		$datagram['txnSubType']		= '00';
		$datagram['orderId']	    = $queryParams['trade_no'];
		$datagram['txnTime']	    = $queryParams['trade_time'];
		$datagram['txnAmt']         = $queryParams['trade_amount'];
		$datagram['version']		= $this->_config['version'];
		$datagram['encoding']		= $this->_config['encoding'];
		$datagram['certId']		    = $this->_runtime['sign_ssl_cert_id'];
		$datagram['signMethod']		= $this->_config['sign_method'];
		$datagram['bizType']		= $this->_config['biz_type'];
		$datagram['channelType']	= $this->_config['channel_type'];
		$datagram['accessType']		= $this->_config['access_type'];
		$datagram['merId']	        = $this->_config['mch_id'];
		$datagram['currencyCode']	= $this->_config['currency_code'];
        $datagram['signature']      = $this->signature($datagram);
        
        $sRequest       = http_build_query($datagram);
        $sResponse      = $this->request($this->_config['query_api'], $sRequest);
        $aResponse      = $this->parse_unionpay_params($sResponse);
        $bVerifyResult  = $this->verify_sign($aResponse);
        
        $this->write_log(__METHOD__, $queryParams['trade_no'], $datagram, $aResponse);
        
        $aRetval = [];
        if ($bVerifyResult === true) {
			if ($aResponse['respCode'] == '00' && $aResponse['origRespCode'] == '00') {
               $aRetval = ['code' => 200, 'data' => $aResponse];
			} else {
               $aRetval = ['code' => 502, 'data' => $aResponse];
			}
		}
        
        if (empty($aRetval)) {
            $aRetval = ['code' => 500, 'data' => $aResponse];
        }
        
        return $aRetval;
    }
    
    
    
    
	/**
	 * 退款申请接口
	 *
	 * @param array $refundParams   退款单数据 
	 *      trade_no    string      订单支付单号
	 *      refund_no    string     订单退款单号
	 *      total_fee    float      订单实付金额
	 *      refund_fee   float      退款申请金额
	 *      refund_time  datetime   退款申请时间
	 * @return array $aRetval
	 *      code    int         状态码
	 *      data    string      接口返回报文
	 */
    public function refund($refundParams)
    {
        $this->export_signcert();
        
		$datagram = [];
		$refundFee = (int)bcmul($refundParams['refund_fee'], 100);
        
		$datagram['version']		= $this->_config['version'];
		$datagram['encoding']		= $this->_config['encoding'];
		$datagram['certId']		    = $this->_runtime['sign_ssl_cert_id'];
		$datagram['signMethod']		= $this->_config['sign_method'];
		$datagram['txnType']		= '04';
		$datagram['txnSubType']		= '00';
		$datagram['bizType']		= $this->_config['biz_type'];
		$datagram['channelType']	= $this->_config['channel_type'];
		$datagram['accessType']		= $this->_config['access_type'];
		$datagram['merId']	        = $this->_config['mch_id'];
		$datagram['orderId']	    = $refundParams['refund_no'];
		$datagram['origQryId']	    = $refundParams['trade_no'];
		$datagram['txnTime']        = $refundParams['refund_time'];
		$datagram['txnAmt']         = $refundFee;
		$datagram['backUrl']        = $this->_config['refund_notify_url'];
        $datagram['signature']      = $this->signature($datagram);
        
        $aRequest = http_build_query($datagram);
        $sResponse = $this->request($this->_config['trans_api'], $aRequest);
        $aResponse = $this->string_to_array($sResponse);
        $bVerifyResult  = $this->verify_sign($aResponse);
        
        $this->write_log(__METHOD__, $refundParams['trade_no'], $datagram, $aResponse);
        
        $aRetval = [];
        if ($bVerifyResult === true) {
			if ($aResponse['respCode'] == '00') {
               $aRetval = ['code' => 200, 'data' => $aResponse];
			} else {
               $aRetval = ['code' => 502, 'data' => $aResponse];
			}
		}
        
        if (empty($aRetval)) {
            $aRetval = ['code' => 500, 'data' => $aResponse];
        }
        
        return $aRetval;
    }
    
    
	/**
	 * 支付/退款异步通知
	 *
	 * @param string $input     通知报文 
	 * @return array $retval
	 *      code    int         状态码
	 *      data    string      接口返回报文
	 */
	public function notify($aResponse)
	{
        $verifySign = $this->verify_sign($aResponse);
        
        if ($verifySign !== true) {
            return ['code' => 407, 'data' => $aResponse, 'msg' => 'Verify Error.'];
		}
        
        if ($aResponse['respCode'] == '00') {
            $retval = ['code' => 200, 'data' => $aResponse, 'msg' => 'Success.'];
        } else {
            $retval = ['code' => 403, 'data' => $aResponse, 'msg' => 'Error.'];
        }
        
        return $retval;
	}
    
    
	/**
	 * 生成签名
	 *
	 * @param array $postParams 接口请求报文
	 * @return string $sSignature 签名
	 */
    protected function signature($postParams)
    {
        ksort($postParams);

        $strInfo = '';
        foreach ($postParams as $key => $val) {
            if ($strInfo) {
                $strInfo .= "&".$key."=".$val;
            } else {
                $strInfo = $key."=".$val;
            }
        }

        $sha1x16 = sha1($strInfo, false);
        if (openssl_sign($sha1x16, $signature, $this->_runtime['sign_ssl_cert_key'], OPENSSL_ALGO_SHA1)) { 
            $sSignature = base64_encode($signature);
        }

        return $sSignature ? $sSignature : false;
    }
    
    
	/**
	 * 接口验签
	 *
	 * @param array $aData 接口返回报文
	 * @return bool $isSuccess 1:成功,0:失败
	 */
    protected function verify_sign($aData) 
    {
        $isSuccess = 0;
        $this->export_verifycert();
        
        if ($this->_runtime['verify_cert_id'] == $aData['certId']) {
            $sSignature = $aData['signature'];
            unset($aData['signature']);
            $sData = $this->array_to_string($aData);
            $sSha1x16 = sha1($sData, FALSE);
            $isSuccess = openssl_verify($sSha1x16, base64_decode($sSignature), $this->_runtime['verify_pub_key_id'], OPENSSL_ALGO_SHA1);
        }
        
        return $isSuccess === 1 ? true : false;
    }
    
	/**
	 * 导出商户私钥证书
	 *
	 * @param void
	 * @return bool
	 */
    protected function export_signcert()
    {
        $pkcs12 = file_get_contents(storage_path($this->_config['sign_ssl_cert']));

        if (openssl_pkcs12_read($pkcs12, $certs, $this->_config['sign_ssl_pass'])) { 
            $x509data = $certs['cert'];
            openssl_x509_read($x509data);
            $certdata = openssl_x509_parse($x509data);
            $this->_runtime['sign_ssl_cert_key'] = $certs['pkey'];
            $this->_runtime['sign_ssl_cert_id'] = $certdata['serialNumber'];
            return true;
        } else {
            return false;
        }
    }
    
    
	/**
	 * 导出银联公钥证书
	 *
	 * @param void
	 * @return bool
	 */
    protected function export_verifycert()
    {
        $x509data = file_get_contents(storage_path($this->_config['verify_ssl_cert']));
        openssl_x509_read($x509data);
        $certdata = openssl_x509_parse($x509data);
        $this->_runtime['verify_cert_id'] = $certdata['serialNumber'];
        $this->_runtime['verify_pub_key_id'] = $x509data;
        return true;
    }
}
