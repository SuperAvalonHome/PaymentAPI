<?php

namespace SuperAvalon\Payment;

use \UnexpectedValueException;

use SuperAvalon\Payment\Interfaces\PaymentHandlerInterface;


/**
 * PaymentFactory
 * 聚合支付类-工厂模式
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
class PaymentFactory {
    
    protected $_env;
    
    protected $_class;
    
	protected $_config;
    
    protected $_mer_no = '';
    
    protected $_pay_mode = '';
    
    protected $_trade_type = '';
    
    protected $_dynamic_config = '';
    
    
	/**
	 * Class constructor
	 *
	 * @param	array	$apiParams	Configuration parameters
	 * @return	void
	 */
	public function __construct(array $apiParams = array())
	{
		if (isset($apiParams['env'])) {
            $this->_env = $apiParams['env'];
        }
        
		if (isset($apiParams['mer_no'])) {
            $this->_mer_no = $apiParams['mer_no'];
        }
        
		if (isset($apiParams['pay_mode'])) {
            $this->_pay_mode = $apiParams['pay_mode'];
        }
        
		if (isset($apiParams['trade_type'])) {
            $this->_trade_type = $apiParams['trade_type'];
        }
        
		if (isset($apiParams['dynamic_config'])) {
            $this->_dynamic_config = json_decode($apiParams['dynamic_config'], true);
        }
        
		// Load Payment
		$payment_calss = $this->_pay_load_classes($this->_pay_mode, $this->_trade_type);
        $payment_calss = __NAMESPACE__ . '\\' . $payment_calss;
        
		// Load Configuration
		$this->_pay_load_configure($this->_mer_no, $this->_pay_mode, $this->_trade_type);
        
        // Instancing Payment
        $this->_class = new $payment_calss($this->_config);
        
        // Verify Payment
        if (!$this->_class instanceof PaymentHandlerInterface) {
            throw new UnexpectedValueException("Payment: driver '".$payment_calss."' doesn't implement PaymentHandlerInterface. Aborting.");
        }
	}
    
    
	/**
	 * Load Payment
	 *
	 * Handle input parameters and load payment
	 *
	 * @param	string	$pay_mode       支付方式
	 * @param	mixed	$trade_type     交易类型
	 * @return	string  $payment_calss
	 */
    protected function _pay_load_classes($pay_mode, $trade_type = '')
    {
        $base_dir = dirname(__FILE__);
        
        interface_exists('PaymentHandlerInterface', FALSE) OR require_once('Interfaces' . DIRECTORY_SEPARATOR . 'PaymentHandlerInterface.php');
        
        $abstract_class = ucfirst($pay_mode) . 'Driver';
        if (!class_exists(__NAMESPACE__ . '\\' . $abstract_class, FALSE) && file_exists($file_path = $base_dir . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . $abstract_class . '.php')) {
            require_once($file_path);
        }
        
        if (!class_exists(__NAMESPACE__ . '\\' . $abstract_class, FALSE)) {
            throw new UnexpectedValueException("Payment: Configured driver '".$abstract_class."' was not found. Aborting.");
        }
        
        if ($trade_type) {
            $payment_calss = ucfirst($pay_mode) . ucfirst($trade_type) . 'Payment';
        } else {
            $payment_calss = ucfirst($pay_mode) . 'Payment';
        }
        
        if (!class_exists(__NAMESPACE__ . '\\' . $payment_calss, FALSE) && file_exists($file_path = $base_dir . DIRECTORY_SEPARATOR . 'Libraries' . DIRECTORY_SEPARATOR . ucfirst($pay_mode) . DIRECTORY_SEPARATOR . $payment_calss.'.php')) {
            require_once($file_path);
        }
        
        if (!class_exists(__NAMESPACE__ . '\\' . $payment_calss, FALSE)) {
            throw new UnexpectedValueException("Payment: Configured driver '".$payment_calss."' was not found. Aborting.");
        }
        
        return $payment_calss;
    }
    
    
	/**
	 * Load Configuration
	 *
	 * Handle input parameters and configuration defaults
	 *
	 * @param	string	$mer_no         商户号
	 * @param	string	$pay_mode       支付方式
	 * @param	mixed	$trade_type     交易类型
	 * @return	void
	 */
	protected function _pay_load_configure($mer_no, $pay_mode, $trade_type = '')
	{
        $pay_mode = strtolower($pay_mode);
        $trade_type = strtolower($trade_type);
        
        $dynamic_config = config('payment.' . $pay_mode . '.' . $mer_no, []);
    
        if ($this->_dynamic_config && is_array($this->_dynamic_config)) {
            $this->_config = $this->_dynamic_config;
        } elseif ($dynamic_config && isset($dynamic_config[$trade_type])) {
            $this->_config = array_merge($dynamic_config['default'], $dynamic_config[$trade_type]);
        } elseif (isset($dynamic_config['default'])) {
            $this->_config = $dynamic_config['default'];
        } else {
            throw new UnexpectedValueException("Payment: Configured config ".$mer_no." was not found. Aborting.");
        }
    
        if ($this->_env == 'dev') {
            $dev_config = config('payment.' . $pay_mode . '.dev', []);
            $this->_config = array_merge($this->_config, $dev_config, ['sys_mer_no' => $mer_no]);
        } else {
            $fixed_config = config('payment.' . $pay_mode . '.fixed', []);
            $this->_config = array_merge($this->_config, $fixed_config, ['sys_mer_no' => $mer_no]);
        }
    
        return true;
    }
    
    
	/**
	 * Get Configuration
	 *
	 * Handle get payment configuration items
	 *
	 * @param	string	$key
	 * @return	void
	 */
    public function getConfigureItem($key)
    {
        if (isset($this->_config[$key])) {
            return $this->_config[$key];
        }
        
        return false;
    }
    
    
	/**
	 * Validation Params
	 *
	 * Handle payment Validation Params
	 *
	 * @param	string	$key
	 * @return	void
	 */
    public function validationParams()
    {
        $extraFields = $this->_class->getExtraFields();
        
        foreach ($extraFields as $field) {
            if (array_key_exists($field, $_REQUEST) === FALSE) {
                $this->_class->response(['code' => 400, 'msg' => 'Missing parameter: ' . $field . ' .']);
            }
        }
    }
    
    
	/**
	 * doPay API
	 *
	 * Handle doPay process
	 *
	 * @param	array	$payParams
	 * @return	void
	 */
    public function doPay(&$payParams)
    {
        return $this->_class->doPay($payParams);
    }
    
    
	/**
	 * doTransfer API
	 *
	 * Handle doPay process
	 *
	 * @param	array	$payParams
	 * @return	void
	 */
    public function doTransfer(&$payParams)
    {
        return $this->_class->doTransfer($payParams);
    }
    
    
	/**
	 * doTransQuery API
	 *
	 * Handle doPay process
	 *
	 * @param	array	$payParams
	 * @return	void
	 */
    public function doTransQuery(&$queryParams)
    {
        return $this->_class->doTransQuery($queryParams);
    }
    
    
	/**
	 * doQuery API
	 *
	 * Handle doQuery process
	 *
	 * @param	array	$queryParams
	 * @return	void
	 */
    public function doQuery($queryParams = array())
    {
        $respData = $this->_class->doQuery($queryParams);
        
        $prettyData = [
            'trade_state' => '',
            'trade_amount' => '',
            'trade_time' => '',
            'bank_type' => '',
            'trade_type' => '',
            'out_trade_no' => '',
            'trade_desc' => '',
        ];
        
        switch ($this->_pay_mode) {
            case 'wechat' :
                if ($respData['code'] == 200) {
                    if ($respData['data']['result_code'] == 'SUCCESS' && $respData['data']['trade_state'] == 'SUCCESS') {
                        $prettyData['trade_state'] = 'success';
                        $prettyData['bank_type'] = $respData['data']['bank_type'] ?: '';
                        $prettyData['trade_time'] = $respData['data']['time_end'] ?: '';
                        $prettyData['trade_type'] = $respData['data']['trade_type'] ?: '';
                        $prettyData['out_trade_no'] = $respData['data']['transaction_id'] ?: '';
                    } else {
                        $prettyData['bank_type'] = '';
                        $prettyData['trade_type'] = '';
                        $prettyData['trade_time'] = '';
                        $prettyData['out_trade_no'] = '';
                        $prettyData['trade_state'] = strtolower($respData['data']['trade_state']);
                    }
                    $prettyData['trade_amount'] = $respData['data']['total_fee'];
                    $prettyData['trade_desc'] = $respData['data']['trade_state_desc'];
                }
                break;
            case 'alipay' :
                if ($respData['code'] == 200) {
                    if ($respData['data']['code'] == '10000' && $respData['data']['trade_status'] == 'TRADE_SUCCESS') {
                        $prettyData['trade_state'] = 'success';
                        $prettyData['bank_type'] = $respData['data']['bank_type'] ?? '';
                        $prettyData['trade_time'] = $respData['data']['send_pay_date'] ?? '';
                        $prettyData['trade_type'] = $respData['data']['trade_type'] ?? '';
                        $prettyData['out_trade_no'] = $respData['data']['trade_no'] ?? '';
                    } else {
                        $prettyData['bank_type'] = '';
                        $prettyData['trade_type'] = '';
                        $prettyData['trade_time'] = '';
                        $prettyData['out_trade_no'] = '';
                        $prettyData['trade_state'] = strtolower($respData['data']['trade_status']);
                    }
                    $prettyData['trade_amount'] = $respData['data']['total_amount'];
                    $prettyData['trade_desc'] = $respData['data']['msg'];
                }
                break;
            case 'unionpay' :
                break;
        }

        $respData['prettyData'] = $prettyData;
        
        return $respData;
    }
    
    
	/**
	 * doRefund API
	 *
	 * Handle doRefund process
	 *
	 * @param	array	$refundParams
	 * @return	void
	 */
    public function doRefund($refundParams = array())
    {
        return $this->_class->doRefund($refundParams);
    }
    
    
	/**
	 * doRefundQuery API
	 *
	 * Handle doRefundQuery process
	 *
	 * @param	array	$queryParams
	 * @return	array
	 */
    public function doRefundQuery($queryParams = array())
    {
        $respData = $this->_class->doRefundQuery($queryParams);
        
        $prettyData = [
            'order_no' => '',
            'refund_no' => '',
            'refund_amount' => '',
            'refund_status' => '',
            'refund_time' => '',
            'out_trade_no' => '',
            'trade_desc' => '',
        ];
        
        switch ($this->_pay_mode) {
            case 'wechat' :
                if ($respData['code'] == 200) {
                    if ($respData['data']['result_code'] == 'SUCCESS' && $respData['data']['return_code'] == 'SUCCESS') {
                        $prettyData['refund_status'] = 'success';
                    } else {
                        $prettyData['refund_status'] = strtolower($respData['data']['return_code']);'';
                    }
                    $prettyData['order_no'] = $respData['data']['out_trade_no'] ?: '';
                    $prettyData['refund_no'] = $respData['data']['out_refund_no_0'] ?: '';
                    $prettyData['out_trade_no'] = $respData['data']['refund_id_0'] ?: '';
                    $prettyData['refund_amount'] = $respData['data']['refund_fee'] ?: '';
                    $prettyData['refund_time'] = $respData['data']['refund_success_time_0'] ?: '';
                    $prettyData['trade_amount'] = $respData['data']['total_fee'];
                    $prettyData['trade_desc'] = $respData['data']['refund_recv_accout_0'] ?: '';
                }
                break;
            case 'alipay' :
                if ($respData['code'] == 200) {
                    if ($respData['data']['code'] == '10000') {
                        $prettyData['refund_status'] = 'success';
                        $prettyData['order_no'] = $respData['data']['out_trade_no'] ?? '';
                        $prettyData['refund_no'] = $respData['data']['out_request_no'] ?? '';
                        $prettyData['refund_amount'] = $respData['data']['refund_amount'] ?? '';
                        $prettyData['trade_amount'] = $respData['data']['total_amount'] ?? '';
                        $prettyData['out_trade_no'] = $respData['data']['trade_no'] ?? '';
                    } else {
                        $prettyData['refund_status'] = '';
                        $prettyData['order_no'] = $respData['data']['out_trade_no'] ?? '';
                        $prettyData['refund_no'] = $respData['data']['out_request_no'] ?? '';
                        $prettyData['refund_amount'] = $respData['data']['refund_amount'] ?? '';
                        $prettyData['trade_amount'] = $respData['data']['total_amount'] ?? '';
                        $prettyData['out_trade_no'] = $respData['data']['trade_no'] ?? '';
                    }
                }
                break;
            case 'unionpay' :
                break;
        }

        $respData['prettyData'] = $prettyData;
        
        return $respData;
    }
    
    
	/**
	 * payNotifyHandler Callback
	 *
	 * Hander pay callback
	 *
	 * @param	string	$input
	 * @return	mixed
	 */
    public function payNotifyHandler(&$input)
    {
        return $this->_class->payNotifyHandler($input);
    }
    
    
	/**
	 * refundNotifyHandler Callback
	 *
	 * Hander refund callback
	 *
	 * @param	string	$input
	 * @return	mixed
	 */
    public function refundNotifyHandler(&$input)
    {
        return $this->_class->refundNotifyHandler($input);
    }
}
