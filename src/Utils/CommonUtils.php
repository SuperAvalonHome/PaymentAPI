<?php

namespace SuperAvalon\Payment\Utils;

/**
 * CommonUtils
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
trait CommonUtils {
    
	/**
	 * response JSON
	 *
	 * @param array $resp 返回的数据
	 * @return json 返回数据
	 */
    public function response($resp)
    {
        @header('Content-type: application/json');
        exit(json_encode($resp));
    }
    
    public function retval($resp)
    {
        // Todo
        return $resp;
    }
    
    
    /**
     * 通用日志函数
     * @param string $content 日志内容
     * @param string $module 日志业务模块
     * @param string $slice 日志分割类型（按天分：day，按月分：month）
     * @param string $type 日志类型(运行日志：run、错误日志：error、输出日志：output)
     */
    function write_file_log($content, $module, $slice = 'day', $type = 'run')
    {
        $boot = config('app.log_root');

        // boot目录不存在，非生产环境
        if (!file_exists($boot)) {
            return false;
        }

        if ($slice == 'month') {
            $dir = $boot . $module . "/";
        } else {
            $dir = $boot . $module . "/" . date("Y_m") . "/";
        }

        if (!file_exists($dir)) {
            mkdir($dir, 0700, true);
        }

        if ($slice == 'month') {
            $file = $dir . $type . "_" . date("Ym") . ".log";
        } else {
            $file = $dir . $type . "_" . date("Ymd") . ".log";
        }

        error_log($content, 3, $file);
    }
    
    
	/**
	 * 查询支付中心集群可用公网ip
	 *
	 * @return string ip
	 */
    function query_dns()
    {
        $CI =& get_instance();
        $CI->load->driver('cache');
        
        $cloudIps = $CI->cache->redis->sunion('PAY_DNS_IPS');
        $usingIp = $CI->cache->redis->get('PAY_DNS_USING');
        
        if (!$usingIp) {
            $usingIp = PAY_CLOUD_DEFAULT_IP;
            $CI->cache->redis->set('PAY_DNS_USING', $usingIp);
        }
        
        if ($cloudIps && in_array($usingIp, $cloudIps) === false) {
            $usingIp = array_pop($cloudIps);
            $CI->cache->redis->set('PAY_DNS_USING', $usingIp);
        }
        
        return $usingIp;
    }
    
    function create_sign($params, $secret)
    {
        ksort($params);
        $query = "";
        foreach ($params as $k => $v) {
            $query .= $k."=".$v."&";
        }
        $sign = md5(substr(md5($query. $secret), 0,-1)."w");
        return $sign;
    }
    
    
	/**
	 * 执行一个 HTTP POST请求
	 *
	 * @param string $url 执行请求的url
	 * @param string $sData post数据
	 * @param string $second Time Out
	 * @param string $aHeader set Header
	 * @param string $aCertfile cert file path
	 * @return string 返回数据
	 */
    function request($url, $sData, $second = 30, $aHeader = [], $aCertfile = [])
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        if (isset($aCertfile['cert'])) {
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $aCertfile['cert']);
        }
        
        if (isset($aCertfile['key'])){
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $aCertfile['key']);
        }
     
        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }
        
        if ($sData) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $sData);
        }
     
        $data = curl_exec($ch);
        
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            // todo
            $error = curl_errno($ch);
            curl_close($ch);
            return false;
        }
    }
    
    
    function cert_file($file)
    {
        $filePath = app()->basePath() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $file;
        
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        } else {
            return null;
        }
    }
    
    
    function write_log($api, $order_no, $request = [], $response = [])
    {
        $es_host = config('app.es_host');
        
        if (!$es_host) {
            return false;
        }
        
        $document = [
            'api' => $api,
            'order_no' => $order_no,
            'request' => json_encode($request),
            'response' => json_encode($response),
            'time' => date("Y-m-d H:i:s"),
            'ip' => '0.0.0.0',
        ];
        
        $response = $this->custom_curl('POST', $es_host . '/logs/test', json_encode($document));
        
        $result = json_decode($response, true);
        
        return $result;
    }
    
    
    function query_log($api, $order_no, $begin_date, $end_date)
    {
        $es_host = config('app.es_host');
        
        if (!$es_host) {
            return false;
        }
        
        $query_body = array(
            'query' => array(
                'bool' => array(
                    'must' => array(
                        array(
                            'match' => array('order_no' => $order_no)
                        )
                    )
                )
            )
        );
        
        if ($api) {
             $query_body['query']['bool']['must'][] = array(
                'match' => array(
                    'api' => $api
                )
            );
        }
        
        if ($begin_date && $end_date) {
             $query_body['query']['bool']['must'][] = array(
                'range' => array(
                    'time' => array(
                        'gte' => $begin_date,
                        'lte' => $end_date
                    )
                )
            );
        }
        //echo $es_host . '/logs/_search';
        
        
        $response = $this->custom_curl('POST', $es_host . '/logs/_search', json_encode($query_body));
        
        $result = json_decode($response, true);
        
        return $result;
    }
    

    function custom_curl($method, $url, $data, $return_code = false)
    {
        $curl = curl_init (); 

        curl_setopt( $curl, CURLOPT_URL, $url ); 
        curl_setopt( $curl, CURLOPT_FILETIME, true ); 
        curl_setopt( $curl, CURLOPT_FRESH_CONNECT, false ); 
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ); 
        curl_setopt( $curl, CURLOPT_TIMEOUT, 30 ); 
        curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 120 ); 
        curl_setopt( $curl, CURLOPT_NOSIGNAL, true ); 
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $method ); 
        
        if ($data) {
            if ($return_code === true) {
                curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Content-Type: application/vnd.kafka.json.v1+json'));
            } else {
                curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            }
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
        } 

        $res = curl_exec ( $curl );
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE); 

        if ($res === false) {
            $err = curl_error($curl);
        } else {
            $err = '';
        }
        
        curl_close($curl);
        
        if ($return_code === true) {
            return array(
                'err' => $err,
                'code' => $code,
                'response' => $res
            );
        } else {
            return $res;
        }
    }

    
    
    function submit_form($url, $params)
    {
        $encodeType = isset($params['encoding']) ? $params['encoding'] : 'UTF-8';
        
        $html = <<<HTML
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_formxx.submit();">
    <form id="pay_form" name="pay_form" action="{$url}?charset=UTF-8" method="POST">
HTML;

    foreach ($params as $key => $value) {
        $val = str_replace("'", "&apos;", $value);
        $html .= "<input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
    }
    
    $html .= <<<HTML
    <input type="submit" type="hidden">
    </form>
</body>
</html>
HTML;

        exit($html);
    }
    
    
	function get_client_ip()
    {
		$ip = '';
        
		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		} elseif (isset($_SERVER["REMOTE_ADDR"])) {
			$ip = $_SERVER["REMOTE_ADDR"];
		} elseif (getenv("HTTP_X_FORWARDED_FOR")) {
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		} elseif (getenv("HTTP_CLIENT_IP")) {
			$ip = getenv("HTTP_CLIENT_IP");
		} elseif (getenv("REMOTE_ADDR")) {
			$ip = getenv("REMOTE_ADDR");
		} else {
			$ip = "223.104.5.199";
		}
        
        if (strpos($ip, ',') !== FALSE) {
            $ips = explode(',', $ip);
            return $ips[0];
        }

		return $ip;
	}
}
