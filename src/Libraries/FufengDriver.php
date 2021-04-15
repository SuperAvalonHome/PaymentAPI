<?php

namespace SuperAvalon\Payment;

use SuperAvalon\Payment\Utils\PaymentUtils;
use SuperAvalon\Payment\Utils\CommonUtils;
use SuperAvalon\Payment\Interfaces\PaymentHandlerInterface;


/**
 * FufengDriver
 * FufengPays底层抽象类
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
abstract class FufengDriver implements PaymentHandlerInterface {
    
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
	 * @return array $retval
	 *      code            int             状态码
	 *      data            string          支付凭证
	 */
	protected function createOrder($payParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['type']		        = $payParams['channel'];
		$apiParams['total']	            = $payParams['total_fee'];
		$apiParams['api_order_sn']	    = $payParams['trade_no'];
		$apiParams['timestamp']	        = $this->getMillisecond();
		$apiParams['notify_url']	    = $this->_config['notify_url'] . '/' . $payParams['mer_no'];
        $apiParams['sign']              = $this->sign($apiParams, $this->_config['secret_key']);
        
        $sResponse = $this->request($this->_config['order_api'], $apiParams);
        $aResponse = json_decode($sResponse, true);
        
        if ($aResponse['error_code'] == '200') {
            $retval = ['code' => 200, 'data' => $aResponse, 'msg' => 'Success.'];
        } else {
            $retval = ['code' => 403, 'data' => $aResponse, 'msg' => 'Error.'];
        }
        
        return $retval;
	}
    
    
	/**
	 * 手机网站支付接口2.0
	 *
	 * @param array $payParams 
     *      method          string          接口名称[Y]
	 * @return array $retval
	 *      code            int             状态码
	 *      data            string          支付凭证
	 */
	protected function doPay($payParams)
	{
		$apiParams = [];
        
        // 公共入参
		$apiParams['amount']	        = $payParams['total_fee'];
		$apiParams['out_trade_no']		= $payParams['trade_no'];
        $apiParams['sign']              = $this->signSimple($this->_config['secret_key'], $apiParams);
		$apiParams['account_id']		= $this->_config['mch_id'];
		$apiParams['s_key']		        = $this->_config['mer_key'];
		$apiParams['thoroughfare']		= $payParams['channel'];    //支付通道：支付宝（公开版）：alipay_auto、微信（公开版）：wechat_auto、服务版（免登陆/免APP）：service_auto
		$apiParams['api_order_sn']	    = $payParams['trade_no'];
		$apiParams['timestamp']	        = $this->getMillisecond();
		$apiParams['notify_url']	    = $this->_config['notify_url'] . '/' . $payParams['mer_no'];
		$apiParams['robin']	            = '2';
		$apiParams['keyId']	            = '';
		$apiParams['type']	            = $payParams['type'] ?? '';
        
        return $apiParams;
	}
    
    
    protected function notify($aParams)
    {
        // 公共入参
		$apiParams['amount']	        = $aParams['amount'];
		$apiParams['out_trade_no']		= $aParams['out_trade_no'];
        $apiSign                        = $this->signSimple($this->_config['secret_key'], $apiParams);
        
        if ($apiSign != $apiParams['sign']) {
            return ['code' => 407, 'data' => $aResponse, 'msg' => 'Verify Error.'];
        }
        
        return ['code' => 200, 'data' => $aParams, 'msg' => 'Success.'];
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
    
        
    function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }
    
        
    function sign($params = [], $secret = '')
    {
        unset($params['sign']);
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            $str = $str . $k . $v;
        }
        $str = $secret . $str . $secret;
        return strtoupper(md5($str));
    }
    
    
    function signSimple($key_id, $array)
    {
         $data = md5(sprintf("%.2f", $array['amount']) . $array['out_trade_no']);
        $key[] ="";
        $box[] ="";
        $pwd_length = strlen($key_id);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++)
        {
            $key[$i] = ord($key_id[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++)
        {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++)
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            
            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }
        return md5($cipher);
    }
}
