<?php

namespace SuperAvalon\Payment\Utils;

/**
 * PaymentUtils
 *
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
trait PaymentUtils {
    
    
	/**
	 * 数组转换成xml
	 *
	 * @param   array   $arr 
	 * @return  string  $xml
	 */
	function array_to_xml($arr)
    {
        $xml = "<xml>";
        
        foreach ($arr as $key=>$val) {
        	 if (is_numeric($val)) {
        	 	$xml.="<".$key.">".$val."</".$key.">"; 
        	 } else {
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
             }
        }
        
        $xml .= "</xml>";
        return $xml; 
    }
    
    
	/**
	 * 解析银联接口返回数据
	 *
	 * @param   string  $str 
	 * @return  array   $result
	 */
    public function parse_unionpay_params($params)
    {
        $data = explode('&', $params);
        $result = array();
        foreach ($data as $key => $val) {
            $row = explode('=', $val);
            $result[$row[0]] = $row[1];
        }
        
        return $result;
    }
    
    
	/**
	 * 字符串转换为数组
	 *
	 * @param   string  $str 
	 * @return  array   $result
	 */
    public function string_to_array($str)
    {
        $result = array();
        if (!empty($str)) {
            $temp = preg_split('/&/', $str);
            if (!empty($temp)) {
                foreach($temp as $key => $val) {
                    $arr = preg_split('/=/', $val, 2);
                    if (!empty($arr)) {
                        $k = $arr['0'];
                        $v = $arr['1'];
                        $result[$k] = $v;
                    }
                }
            }
        }
        return $result;
    }
	
    
    /**
     * 数组排序后转化为字体串
     *
     * @param array $data
     * @return string
     */
    public function array_to_string($data) 
    {
        $signStr = '';
        ksort($data);
        foreach ($data as $key => $val) {
            //todo...
            if ($key == 'signature') {
                continue;
            }
            $signStr .= sprintf("%s=%s&", $key, $val);
        }
        return substr($signStr, 0, strlen($signStr) - 1);
    }
    
    
	/**
	 * 解析xml字符流为数组
	 *
	 * @param   string    $xml
	 * @return  array    $arr 
	 */
    function xml_to_array($xml)
    {
        $arr = array();
        $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
        
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                $key= $matches[1][$i];
                $val = $this->xml_to_array( $matches[2][$i] );
                if (array_key_exists($key, $arr)) {
                    if (is_array($arr[$key])) {
                        if (!array_key_exists(0,$arr[$key])) {
                            $arr[$key] = array($arr[$key]);
                        }
                    } else {
                        $arr[$key] = array($arr[$key]);
                    }
                    $arr[$key][] = str_replace(array('<![CDATA[', ']]>'), '', $val);
                } else {
                    $arr[$key] = str_replace(array('<![CDATA[', ']]>'), '', $val);
                }
            }
            return isset($arr['xml']) ? $arr['xml'] : $arr;
        } else {
            return $xml;
        }
    }
    
    
	/**
	 * 创建指定长度的随机字符串
	 * @param   int     $length     字符串长度
	 * @return  string  $str
	 */
	function create_noncestr($length = 16)
    {
        $str = "";
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		
		for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
        
		return $str;
	}
    
    
	/**
	 * DES加密    ECB模式
	 *
	 * @param   string    $value    明文
	 * @return  string    $ret      密文
	 */
    function encrypt($value)
    {
        $td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
        $key = substr(PAY_SECRET_KEY, 0, mcrypt_enc_get_key_size($td));
        mcrypt_generic_init($td, $key, $iv);
        $ret = base64_encode(mcrypt_generic($td, $value));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }
    
    
	/**
	 * DES解密    ECB模式
	 *
	 * @param   string    $ret      密文
	 * @return  string    $value    明文
	 */
    function decrypt($value)
    {
        $td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
        $key = substr(PAY_SECRET_KEY, 0, mcrypt_enc_get_key_size($td));
        mcrypt_generic_init($td, $key, $iv);
        $ret = trim(mdecrypt_generic($td, base64_decode($value))) ;
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }

    
	/**
	 * URL编码    安全模式，替换特殊字符：+/
	 *
	 * @param   string    $string
	 * @return  string    $data
	 */
    function url_safe_base64_encode($string) 
    {
        $data = base64_encode($string);
        $no_of_eq = substr_count($data, "=");
        $data = str_replace("=", "", $data);
        $data = str_replace(array('+','/'),array('-','_'),$data);
        return $data; 
    }
    
    
	/**
	 * URL解码    安全模式，替换特殊字符：+/
	 *
	 * @param   string    $string
	 * @return  string    $data
	 */
    function url_safe_base64_decode($string) 
    {
        $string = str_replace(array('-','_'),array('+','/'),$string);
        $data = base64_decode($string);
        return $data;
    }
}
