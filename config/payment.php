<?php


//商户自定义配置数据
$config['wechat']['dynamic']['default'] = array(
	'mch_id'	        => '1267875101',
	'app_id'	        => 'wx4ac884c285ee6421',
	'secret_key'	    => 'eaed9294835e68c79323cb3dc2c6ef83',
	'refund_key'	    => '1f62392e56036bedef7a06982c871762',
	'sign_type'	        => 'MD5',
	'refund_ssl_cert'	=> storage_path('certs' . DIRECTORY_SEPARATOR . 'wechat' . DIRECTORY_SEPARATOR . '1496340942_cert.pem'),
	'refund_ssl_key'	=> storage_path('certs' . DIRECTORY_SEPARATOR . 'wechat' . DIRECTORY_SEPARATOR . '1496340942_key.pem'),
    'notify_url'        => config('app.url') . '/notify/index/wechat',
    'wap_url'           => config('app.url') . '/notify/index/wechat',
);


$config['unionpay']['dynamic']['default'] = array(
	'channel_type'	    => '08',
	'mch_id'	        => '305310054990143',
	'discount_code'	    => '20170111tiantianbiaoqianlijian',
	'verify_ssl_cert'	=> storage_path('certs' . DIRECTORY_SEPARATOR . 'unionpay' . DIRECTORY_SEPARATOR . 'UpopRsaCert.cer'),
	'sign_ssl_cert'	    => storage_path('certs' . DIRECTORY_SEPARATOR . 'unionpay' . DIRECTORY_SEPARATOR . '305310054990143.pfx'),
	'sign_ssl_pass'	    => '181227',
    'return_url'        => config('app.url') . '/notify/index/unionpay',
    'notify_url'        => config('app.url') . '/notify/returnNotify/chinapayapp',
    'refund_notify_url' => config('app.url') . '/refund/notify/unionpay',
);

$config['unionpay']['dynamic']['pc'] = array(
	'channel_type'	    => '07',
);



//系统固定
$config['wechat']['fixed'] = array(
	'order_api'	        => 'https://api.mch.weixin.qq.com/pay/unifiedorder',
	'query_api'	        => 'https://api.mch.weixin.qq.com/pay/orderquery',
	'close_api'	        => 'https://api.mch.weixin.qq.com/pay/closeorder',
	'refund_api'	    => 'https://api.mch.weixin.qq.com/secapi/pay/refund',
	'down_bill_api'	    => 'https://api.mch.weixin.qq.com/pay/downloadbill',
	'refund_query_api'	=> 'https://api.mch.weixin.qq.com/pay/refundquery',
	'get_signkey_api'	=> 'https://api.mch.weixin.qq.com/pay/getsignkey',
	'auth_api'	        => 'https://open.weixin.qq.com/connect/oauth2/authorize',
);


$config['unionpay']['fixed'] = array(
	'version'	        => '5.0.0',
	'encoding'	        => 'utf-8',
	'sign_method'	    => '01',
	'txn_type'	        => '01',
	'sub_type'	        => '01',
	'biz_type'	        => '000201',
	'channel_type'	    => '08',
	'access_type'	    => '0',
	'currency_code'	    => '156',
	'default_type'	    => '0004',
	'order_api'	        => 'https://gateway.95516.com/gateway/api/appTransReq.do',
	'query_api'	        => 'https://gateway.95516.com/gateway/api/queryTrans.do',
	'trans_api'	        => 'https://gateway.95516.com/gateway/api/backTransReq.do',
);

return $config;