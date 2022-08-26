<?php
namespace payment;

use think\facade\Log;

// 1 类名必须于文件名一致（去除 .php ），如 Alipay.php 则取 Alipay
// 2 类必须定义的方法
// 2.1 Config 配置方法
// 2.2 Pay 支付方法
// 2.3 Respond 回调方法
// 2.4 Notify 异步回调方法（可选、未定义则调用Respond方法）
// 2.5 Refund 退款方法（可选、未定义则不能发起原路退款）
// 3 可自定义输出内容方法
// 3.1 SuccessReturn 支付成功（可选）
// 3.2 ErrorReturn 支付失败（可选）
// PS：以上条件不满足则无法查看插件，将插件放入.zip压缩包中上传、支持一个压缩中包含多个支付插件

/**
 * 微信支付
 * @author   jiakuant 
 * @blog    jiakuant@gmail.com 1276018921@qq.com
 * @version 1.0.0
 * @date    2018-09-19
 * @desc    description
 */
class Pi
{
    // 插件配置参数
    private $config;

    /**
     * 构造方法
     * @author   jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2018-09-17
     * @desc    description
     * @param   [array]           $params [输入参数（支付配置参数）]
     */
    public function __construct($params = [])
    {
        $this->config = $params;
    }

    /**
     * 配置信息
     * @author   jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     */
    public function Config()
    {
        Log::info("Pi支付 配置信息");
        // 基础信息
        $base = [
            'name'          => 'Pi支付',  // 插件名称
            'version'       => '1.0.0',  // 插件版本
            'apply_version' => '不限',  // 适用系统版本描述
            'apply_terminal'=> ['pc', 'h5','pi'], // 适用终端 默认全部 ['pc', 'h5', 'app', 'alipay', 'weixin', 'baidu']
            'desc'          => '用于Pi浏览器支付',  // 插件描述（支持html）
            'author'        => 'jiakaunt',  // 开发者
            'author_url'    => 'jiakuant@gmail.com',  // 开发者主页
        ];

        // 配置信息
        $element = [
            [
                'element'       => 'input',
                'type'          => 'text',
                'default'       => '',
                'name'          => 'apiKey',
                'placeholder'   => 'apiKey',
                'title'         => 'apiKey',
                'is_required'   => 0,
                'message'       => '请填写Pi程序提供的apiKey',
            ],
        ];

        return [
            'base'      => $base,
            'element'   => $element,
        ];
    }

    /**
     * 支付入口
     * @author   jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Pay($params = [])
    {

        Log::info("Pi支付 支付入口:");
        Log::info($params);

        // 参数
        if(empty($params))
        {
            return DataReturn('参数不能为空', -1);
        }

        Log::info("Pi支付 配置:");
        Log::info($this->config);
        // 配置信息
        if(empty($this->config))
        {
            return DataReturn('支付缺少配置', -1);
        }

        // 平台
        Log::info("Pi支付 获取平台类型:");
        $client_type = $this->GetApplicationClientType();
        Log::info($client_type);

        // 微信中打开
        // if(APPLICATION_CLIENT_TYPE == 'pc' && IsWeixinEnv() && (empty($params['user']) || empty($params['user']['weixin_web_openid'])))
        // {
        //     exit(header('location:'.PluginsHomeUrl('weixinwebauthorization', 'pay', 'index', input())));
        // }

        // 获取支付参数
        Log::info("Pi支付 获取支付参数:");
        $ret = $this->GetPayParams($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }
        Log::info($ret);

        // QQ小程序使用微信支付
        // if($client_type == 'qq')
        // {
        //     // 获取QQ access_token
        //     $qq_appid = MyC('common_app_mini_qq_appid');
        //     $qq_appsecret = MyC('common_app_mini_qq_appsecret');
        //     $access_token = (new \base\QQ($qq_appid, $qq_appsecret))->GetAccessToken();
        //     if($access_token === false)
        //     {
        //         return DataReturn('QQ凭证AccessToken获取失败', -1);
        //     }

        //     // QQ小程序代理下单地址
        //     $request_url = 'https://api.q.qq.com/wxpay/unifiedorder?appid='.$qq_appid.'&access_token='.$access_token.'&real_notify_url='.urlencode($this->GetNotifyUrl($params));
        // } else {
        //     $request_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        // }

        // 请求接口处理
        $result = $this->XmlToArray($this->HttpRequest($request_url, $this->ArrayToXml($ret['data'])));
        if(!empty($result['return_code']) && $result['return_code'] == 'SUCCESS' && !empty($result['prepay_id']))
        {
            return $this->PayHandleReturn($ret['data'], $result, $params);
        }
        $msg = is_string($result) ? $result : (empty($result['return_msg']) ? '支付接口异常' : $result['return_msg']);
        if(!empty($result['err_code_des']))
        {
            $msg .= '-'.$result['err_code_des'];
        }
        return DataReturn($msg, -1);
    }

    /**
     * 终端
     * @author  jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2021-12-07
     * @desc    description
     */
    private function GetApplicationClientType()
    {
        // 平台
        $client_type = APPLICATION_CLIENT_TYPE;
        if($client_type == 'pc' && IsMobile())
        {
            $client_type = 'h5';
        }
        return $client_type;
    }

    /**
     * 支付返回处理
     * @author   jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2019-01-08
     * @desc    description
     * @param   [array]           $pay_params   [支付参数]
     * @param   [array]           $data         [支付返回数据]
     * @param   [array]           $params       [输入参数]
     */
    // private function PayHandleReturn($pay_params = [], $data = [], $params = [])
    // {
    //     $redirect_url = empty($params['redirect_url']) ? __MY_URL__ : $params['redirect_url'];
    //     $result = DataReturn('支付接口异常', -1);
    //     switch($pay_params['trade_type'])
    //     {
    //         // web支付
    //         case 'NATIVE' :
    //             if(empty($params['check_url']))
    //             {
    //                 return DataReturn('支付状态校验地址不能为空', -50);
    //             }
    //             if(APPLICATION == 'app')
    //             {
    //                 $data = [
    //                     'qrcode_url'    => MyUrl('index/qrcode/index', ['content'=>urlencode(base64_encode($data['code_url']))]),
    //                     'order_no'      => $params['order_no'],
    //                     'name'          => '微信支付',
    //                     'msg'           => '打开微信APP扫一扫进行支付',
    //                     'check_url'     => $params['check_url'],
    //                 ];
    //             } else {
    //                 $pay_params = [
    //                     'url'       => urlencode(base64_encode($data['code_url'])),
    //                     'order_no'  => $params['order_no'],
    //                     'name'      => urlencode('微信支付'),
    //                     'msg'       => urlencode('打开微信APP扫一扫进行支付'),
    //                     'check_url' => urlencode(base64_encode($params['check_url'])),
    //                 ];
    //                 $data = MyUrl('index/pay/qrcode', $pay_params);
    //             }
    //             $result = DataReturn('success', 0, $data);
    //             break;

    //         // h5支付
    //         case 'MWEB' :
    //             if(!empty($params['order_id']))
    //             {
    //                 // 是否需要urlencode
    //                 $redirect_url = (isset($this->config['is_h5_url_encode']) && $this->config['is_h5_url_encode'] == 1) ? urlencode($redirect_url) : $redirect_url;
    //                 $data['mweb_url'] .= '&redirect_url='.$redirect_url;
    //             }
    //             $result = DataReturn('success', 0, $data['mweb_url']);
    //             break;

    //         // 微信中/小程序支付
    //         case 'JSAPI' :
    //             $pay_data = [
    //                 'appId'         => $pay_params['appid'],
    //                 'package'       => 'prepay_id='.$data['prepay_id'],
    //                 'nonceStr'      => md5(time().rand()),
    //                 'signType'      => $pay_params['sign_type'],
    //                 'timeStamp'     => (string) time(),
    //             ];
    //             $pay_data['paySign'] = $this->GetSign($pay_data);

    //             // 微信中
    //             if(APPLICATION == 'web' && IsWeixinEnv())
    //             {
    //                 $html = $this->PayHtml($pay_data, $redirect_url);
    //                 die($html);
    //             } else {
    //                 $result = DataReturn('success', 0, $pay_data);
    //             }
    //             break;

    //         // APP支付
    //         case 'APP' :
    //             $pay_data = array(
    //                 'appid'         => $pay_params['appid'],
    //                 'partnerid'     => $pay_params['mch_id'],
    //                 'prepayid'      => $data['prepay_id'],
    //                 'package'       => 'Sign=WXPay',
    //                 'noncestr'      => md5(time().rand()),
    //                 'timestamp'     => (string) time(),
    //             );
    //             $pay_data['sign'] = $this->GetSign($pay_data);
    //             $result = DataReturn('success', 0, $pay_data);
    //             break;
    //     }
    //     return $result;
    // }

    /**
     * 支付代码
     * @author   jiakaunt
     * @blog     1276018921@qq.com
     * @version  1.0.0
     * @datetime 2019-05-25T00:07:52+0800
     * @param    [array]                   $pay_data     [支付信息]
     * @param    [string]                  $redirect_url [支付结束后跳转url]
     */
    // private function PayHtml($pay_data, $redirect_url)
    // {
    //     // 支付代码
    //     return '<html>
    //         <head>
    //             <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    //             <title>微信安全支付</title>
    //             <meta name="apple-mobile-web-app-capable" content="yes">
    //             <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1, maximum-scale=1">
    //             <body style="text-align:center;padding-top:10%;">
    //                 <p style="color:#999;">正在支付中...</p>
    //                 <p style="color:#f00;margin-top:20px;">请不要关闭页面！</p>
    //             </body>
    //             <script type="text/javascript">
    //                 function onBridgeReady()
    //                 {
    //                    WeixinJSBridge.invoke(
    //                         \'getBrandWCPayRequest\', {
    //                             "appId":"'.$pay_data['appId'].'",
    //                             "timeStamp":"'.$pay_data['timeStamp'].'",
    //                             "nonceStr":"'.$pay_data['nonceStr'].'",
    //                             "package":"'.$pay_data['package'].'",     
    //                             "signType":"'.$pay_data['signType'].'",
    //                             "paySign":"'.$pay_data['paySign'].'"
    //                         },
    //                         function(res) {
    //                             window.location.href = "'.$redirect_url.'";
    //                         }
    //                     ); 
    //                 }
    //                 if(typeof WeixinJSBridge == "undefined")
    //                 {
    //                    if( document.addEventListener )
    //                    {
    //                        document.addEventListener("WeixinJSBridgeReady", onBridgeReady, false);
    //                    } else if (document.attachEvent)
    //                    {
    //                        document.attachEvent("WeixinJSBridgeReady", onBridgeReady); 
    //                        document.attachEvent("onWeixinJSBridgeReady", onBridgeReady);
    //                    }
    //                 } else {
    //                    onBridgeReady();
    //                 }
    //             </script>
    //         </head>
    //     </html>';
    // }

    /**
     * 获取支付参数
     * @author   jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2019-01-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GetPayParams($params = [])
    {
        // {"appid":"","mch_id":"","key":"","apiclient_cert":"","apiclient_key":""}
        $trade_type = empty($params['trade_type']) ? $this->GetTradeType() : $params['trade_type'];
        if(empty($trade_type))
        {
            return DataReturn('支付类型不匹配', -1);
        }

        // 平台
        $client_type = $this->GetApplicationClientType();

        // openid
        // if($client_type == 'weixin')
        // {
        //     $openid = isset($params['user']['weixin_openid']) ? $params['user']['weixin_openid'] : '';
        // } else {
        //     $openid = isset($params['user']['weixin_web_openid']) ? $params['user']['weixin_web_openid'] : '';
        // }

        // appid
        // $appid = $this->PayAppID($client_type);

        // 异步地址处理
        // $notify_url = ($client_type == 'qq') ? 'https://api.q.qq.com/wxpay/notify' : $this->GetNotifyUrl($params);


        // $this->config['mini_appid']
        // 请求参数
        // $data = [
        //     'appid'             => $appid,
        //     'mch_id'            => $this->config['mch_id'],
        //     'body'              => $params['site_name'].'-'.$params['name'],
        //     'nonce_str'         => md5(time().$params['order_no']),
        //     'notify_url'        => $notify_url,
        //     'openid'            => ($trade_type == 'JSAPI') ? $openid : '',
        //     'out_trade_no'      => $params['order_no'],
        //     'spbill_create_ip'  => GetClientIP(),
        //     'total_fee'         => (int) (($params['total_price']*1000)/10),
        //     'trade_type'        => $trade_type,
        //     'attach'            => empty($params['attach']) ? $params['site_name'].'-'.$params['name'] : $params['attach'],
        //     'sign_type'         => 'MD5',
        //     'time_expire'       => $this->OrderAutoCloseTime(),
        // ];
        // $data['sign'] = $this->GetSign($data);



        $data = [
            'apiKey'             => $this->config['apiKey']
        ];

        return DataReturn('success', 0, $data);
    }

    /**
     * appid获取
     * @author  jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2021-04-25
     * @desc    description
     * @param   [string]          $client_type [客户端类型]
     */
    // public function PayAppID($client_type)
    // {
    //     $arr = [
    //         'weixin'    => $this->config['mini_appid'],
    //         'ios'       => $this->config['app_appid'],
    //         'android'   => $this->config['app_appid'],
    //     ];
    //     return array_key_exists($client_type, $arr) ? $arr[$client_type] : $this->config['appid'];
    // }

    /**
     * 订单自动关闭的时间
     * @author  jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2021-03-24
     * @desc    description
     */
    // public function OrderAutoCloseTime()
    // {
    //     $time = intval(MyC('common_order_close_limit_time', 30, true))*60;
    //     return date('YmdHis', time()+$time);
    // }

    /**
     * 获取异步通知地址
     * @author  jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2020-09-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    // private function GetNotifyUrl($params)
    // {
    //     return (__MY_HTTP__ == 'https' && isset($this->config['agreement']) && $this->config['agreement'] == 1) ? 'http'.mb_substr($params['notify_url'], 5, null, 'utf-8') : $params['notify_url'];
    // }

    /**
     * 获取支付交易类型
     * @author   jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2019-01-08
     * @desc    description
     */
    private function GetTradeType()
    {
        // 平台
        $client_type = $this->GetApplicationClientType();

        // 平台类型定义
        $type_all = [
            'pc'        => 'NATIVE',
            'weixin'    => 'JSAPI',
            'h5'        => 'MWEB',
            'toutiao'   => 'MWEB',
            'qq'        => 'MWEB',
            'app'       => 'APP',
            'ios'       => 'APP',
            'android'   => 'APP',
        ];

        // h5
        if($client_type == 'h5')
        {
            // 微信中打开
            if(IsWeixinEnv())
            {
                $type_all['h5'] = $type_all['weixin'];
            } else {
                // 非手机访问h5则使用NATIVE二维码的方式
                if(!IsMobile())
                {
                    $type_all['h5'] = $type_all['pc'];
                }
            }
        }

        return isset($type_all[$client_type]) ? $type_all[$client_type] : '';
    }

    /**
     * 支付回调处理
     * @author   jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2018-09-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Respond($params = [])
    {
        $result = empty($GLOBALS['HTTP_RAW_POST_DATA']) ? $this->XmlToArray(file_get_contents('php://input')) : $this->XmlToArray($GLOBALS['HTTP_RAW_POST_DATA']);

        if(isset($result['result_code']) && $result['result_code'] == 'SUCCESS' && $result['sign'] == $this->GetSign($result))
        {
            return DataReturn('支付成功', 0, $this->ReturnData($result));
        }
        return DataReturn('处理异常错误', -100);
    }

    /**
     * [ReturnData 返回数据统一格式]
     * @author   jiakaunt
     * @blog     1276018921@qq.com
     * @version  1.0.0
     * @datetime 2018-10-06T16:54:24+0800
     * @param    [array]                   $data [返回数据]
     */
    private function ReturnData($data)
    {
        // 返回数据固定基础参数
        $data['trade_no']       = $data['transaction_id'];  // 支付平台 - 订单号
        $data['buyer_user']     = $data['openid'];          // 支付平台 - 用户
        $data['out_trade_no']   = $data['out_trade_no'];    // 本系统发起支付的 - 订单号
        $data['subject']        = $data['attach'];          // 本系统发起支付的 - 商品名称
        $data['pay_price']      = $data['total_fee']/100;   // 本系统发起支付的 - 总价
        return $data;
    }

    /**
     * 退款处理
     * @author  jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2019-05-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Refund($params = [])
    {
        // 参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'order_no',
                'error_msg'         => '订单号不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'trade_no',
                'error_msg'         => '交易平台订单号不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'pay_price',
                'error_msg'         => '支付金额不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'refund_price',
                'error_msg'         => '退款金额不能为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 证书是否配置
        if(empty($this->config['apiclient_cert']) || empty($this->config['apiclient_key']))
        {
            return DataReturn('证书未配置', -1);
        }

        // 退款原因
        $refund_reason = empty($params['refund_reason']) ? $params['order_no'].'订单退款'.$params['refund_price'].'元' : $params['refund_reason'];

        // appid，默认使用公众号appid
        $appid = $this->PayAppID($params['client_type']);

        // 请求参数
        $data = [
            'appid'             => $appid,
            'mch_id'            => $this->config['mch_id'],
            'nonce_str'         => md5(time().rand().$params['order_no']),
            'sign_type'         => 'MD5',
            'transaction_id'    => $params['trade_no'],
            'out_trade_no'      => $params['order_no'],
            'out_refund_no'     => $params['order_no'].GetNumberCode(),
            'total_fee'         => (int) (($params['pay_price']*1000)/10),
            'refund_fee'        => (int) (($params['refund_price']*1000)/10),
            'refund_desc'       => $refund_reason,            
        ];
        $data['sign'] = $this->GetSign($data);

        // 请求接口处理
        $result = $this->XmlToArray($this->HttpRequest('https://api.mch.weixin.qq.com/secapi/pay/refund', $this->ArrayToXml($data), true));
        if(isset($result['result_code']) && $result['result_code'] == 'SUCCESS' && isset($result['return_code']) && $result['return_code'] == 'SUCCESS')
        {
            // 统一返回格式
            $data = [
                'out_trade_no'  => isset($result['out_trade_no']) ? $result['out_trade_no'] : '',
                'trade_no'      => isset($result['transaction_id']) ? $result['transaction_id'] : (isset($result['err_code_des']) ? $result['err_code_des'] : ''),
                'buyer_user'    => isset($result['refund_id']) ? $result['refund_id'] : '',
                'refund_price'  => isset($result['refund_fee']) ? $result['refund_fee']/100 : 0.00,
                'return_params' => $result,
            ];
            return DataReturn('退款成功', 0, $data);
        }
        $msg = is_string($result) ? $result : (empty($result['err_code_des']) ? '退款接口异常' : $result['err_code_des']);
        if(!empty($result['return_msg']))
        {
            $msg .= '-'.$result['return_msg'];
        }
        return DataReturn($msg, -1);
    }

    /**
     * 签名生成
     * @author   jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2019-01-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GetSign($params = [])
    {
        ksort($params);
        $sign  = '';
        foreach($params as $k=>$v)
        {
            if($k != 'sign' && $v != '' && $v != null)
            {
                $sign .= "$k=$v&";
            }
        }
        return strtoupper(md5($sign.'key='.$this->config['key']));
    }

    /**
     * 数组转xml
     * @author   jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2019-01-07
     * @desc    description
     * @param   [array]          $data [数组]
     */
    private function ArrayToXml($data)
    {
        $xml = '<xml>';
        foreach($data as $k=>$v)
        {
            $xml .= '<'.$k.'>'.$v.'</'.$k.'>';
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * xml转数组
     * @author   jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2019-01-07
     * @desc    description
     * @param   [string]          $xml [xm数据]
     */
    private function XmlToArray($xml)
    {
        if(!$this->XmlParser($xml))
        {
            return is_string($xml) ? $xml : '接口返回数据有误';
        }

        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }


    /**
     * 判断字符串是否为xml格式
     * @author   jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2019-01-07
     * @desc    description
     * @param   [string]          $string [字符串]
     */
    function XmlParser($string)
    {
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser, $string, true))
        {
          xml_parser_free($xml_parser);
          return false;
        } else {
          return (json_decode(json_encode(simplexml_load_string($string)),true));
        }
    }

    /**
     * [HttpRequest 网络请求]
     * @author   jiakaunt
     * @blog     1276018921@qq.com
     * @version  1.0.0
     * @datetime 2017-09-25T09:10:46+0800
     * @param    [string]          $url         [请求url]
     * @param    [array]           $data        [发送数据]
     * @param    [boolean]         $use_cert    [是否需要使用证书]
     * @param    [int]             $second      [超时]
     * @return   [mixed]                        [请求返回数据]
     */
    private function HttpRequest($url, $data, $use_cert = false, $second = 30)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_POST           => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_TIMEOUT        => $second,
        );

        if($use_cert == true)
        {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            $apiclient = $this->GetApiclientFile();
            $options[CURLOPT_SSLCERTTYPE] = 'PEM';
            $options[CURLOPT_SSLCERT] = $apiclient['cert'];
            $options[CURLOPT_SSLKEYTYPE] = 'PEM';
            $options[CURLOPT_SSLKEY] = $apiclient['key'];
        }
 
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        //返回结果
        if($result)
        {
            curl_close($ch);
            return $result;
        } else { 
            $error = curl_errno($ch);
            curl_close($ch);
            return "curl出错，错误码:$error";
        }
    }

    /**
     * 获取证书文件路径
     * @author  jiakaunt
     * @blog    1276018921@qq.com
     * @version 1.0.0
     * @date    2019-05-29
     * @desc    description
     */
    private function GetApiclientFile()
    {
        // 证书位置
        $apiclient_cert_file = ROOT.'runtime'.DS.'cache'.DS.'payment_weixin_pay_apiclient_cert.pem';
        $apiclient_key_file = ROOT.'runtime'.DS.'cache'.DS.'payment_weixin_pay_apiclient_key.pem';

        // 证书处理
        if(stripos($this->config['apiclient_cert'], '-----') === false)
        {
            $apiclient_cert = "-----BEGIN CERTIFICATE-----\n";
            $apiclient_cert .= wordwrap($this->config['apiclient_cert'], 64, "\n", true);
            $apiclient_cert .= "\n-----END CERTIFICATE-----";
        } else {
            $apiclient_cert = $this->config['apiclient_cert'];
        }
        file_put_contents($apiclient_cert_file, $apiclient_cert);

        if(stripos($this->config['apiclient_key'], '-----') === false)
        {
            $apiclient_key = "-----BEGIN PRIVATE KEY-----\n";
            $apiclient_key .= wordwrap($this->config['apiclient_key'], 64, "\n", true);
            $apiclient_key .= "\n-----END PRIVATE KEY-----";
        } else {
            $apiclient_key = $this->config['apiclient_key'];
        }
        file_put_contents($apiclient_key_file, $apiclient_key);

        return ['cert' => $apiclient_cert_file, 'key' => $apiclient_key_file];
    }
}
?>