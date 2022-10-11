<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace app\plugins\thirdpartylogin\platform;

use app\plugins\thirdpartylogin\service\BaseService;

/**
 * 第三方登录 - 支付宝
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class AlipayPlatform
{
    // 平台类型
    public static $platform = 'alipay';

    // 配置信息
    public static $config;

    /**
     * 登录处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]          $config [平台配置信息]
     * @param   [array]          $params [输入参数]
     */
    public static function Login($config, $params = [])
    {
        // 请求参数
        if(empty($params['redirect_uri']))
        {
            return DataReturn('回调地址为空', -1);
        }

        // 配置校验
        $ret = self::ConfigCheck($config);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 登录地址
        $state = BaseService::RequestStateCreate(self::$platform);
        $redirect_uri = urlencode($params['redirect_uri']);
        $url = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id='.$config['app_id'].'&scope=auth_user&redirect_uri='.$redirect_uri.'&state='.$state;
        return DataReturn('success', 0, $url);
    }

    /**
     * 回调处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]          $config [平台配置信息]
     * @param   [array]          $params [输入参数]
     */
    public static function Back($config, $params = [])
    {
        // 配置校验
        $ret = self::ConfigCheck($config);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 回调标记
        if(empty($params['auth_code']))
        {
            return DataReturn('回调code信息有误', -1);
        }

        // 根据 code 获取用户信息
        $bycode = self::OpenUserByCode($params['auth_code']);
        if($bycode['code'] != 0)
        {
            return $bycode;
        }

        // 获取用户信息
        $ou = self::OpenUserInfo($bycode['data']['access_token']);
        if($ou['code'] != 0)
        {
            return $ou;
        }

        // 返回统一格式
        $user = self::UserReturnData($ou['data']);
 
        return DataReturn('success', 0, $user);
    }

    /**
     * 用户统一返回格式
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]          $data [用户信息]
     */
    public static function UserReturnData($data)
    {
        return [
            'openid'    => $data['user_id'],
            'avatar'    => isset($data['avatar']) ? $data['avatar'] : '',
            'nickname'  => empty($data['nick_name']) ? '支付宝用户-'.RandomString(6) : $data['nick_name'],
            'gender'    => empty($data['gender']) ? 0 : (($data['gender'] == 'F') ? 1 : 2),
        ];
    }

    /**
     * 通过用户id获取用户信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [string]         $access_token   [access_token]
     */
    public static function OpenUserInfo($access_token)
    {
        // 请求参数
        $params = [
            'app_id'            => self::$config['app_id'],
            'method'            => 'alipay.user.info.share',
            'charset'           => 'utf-8',
            'format'            => 'JSON',
            'sign_type'         => 'RSA2',
            'timestamp'         => date('Y-m-d H:i:s'),
            'version'           => '1.0',
            'auth_token'        => $access_token,
            'biz_content'       => 'get_user_info',
        ];

        // 生成签名参数+签名
        $p = self::SignCreateString($params);
        $params['sign'] = self::MyRsaSign($p['value']);

        // 执行请求
        $ret = self::ApiRemoteData('https://openapi.alipay.com/gateway.do', $params);
        if($ret['code'] == 0 && !empty($ret['data']) && isset($ret['data']['code']) && $ret['data']['code'] == 10000)
        {
            return $ret;
        }
        return DataReturn($ret['data']['sub_msg'].'('.$ret['data']['code'].')', -1);
    }

    /**
     * 通过临时code获取用户开放信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [string]         $code   [临时code]
     */
    public static function OpenUserByCode($code)
    {
        // 请求参数
        $params = [
            'app_id'            => self::$config['app_id'],
            'method'            => 'alipay.system.oauth.token',
            'charset'           => 'utf-8',
            'format'            => 'JSON',
            'sign_type'         => 'RSA2',
            'timestamp'         => date('Y-m-d H:i:s'),
            'version'           => '1.0',
            'code'              => $code,
            'grant_type'        => 'authorization_code',
        ];

        // 生成签名参数+签名
        $p = self::SignCreateString($params);
        $params['sign'] = self::MyRsaSign($p['value']);

        // 执行请求
        return self::ApiRemoteData('https://openapi.alipay.com/gateway.do', $params);
    }

    /**
     * 远程获取数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-13
     * @desc    description
     * @param   [string]          $url      [请求地址]
     * @param   [array]           $data     [请求数据]
     */
    public static function ApiRemoteData($url, $data = [])
    {
        // 远程接口
        $ret = CurlPost($url, $data);
        if($ret['code'] != 0)
        {
            $ret['msg'] = '平台连接失败[ '.$ret['msg'].' ]';
            return $ret;
        }
        $result = json_decode($ret['data'], true);
        if(empty($result))
        {
            return DataReturn('数据无效'.(empty($ret['data']) ? '' : '['.$ret['data'].']'), -1);
        }

        // 是否成功
        $key = str_replace('.', '_', $data['method']).'_response';
        if(!empty($result[$key]))
        {
            return DataReturn('success', 0, $result[$key]);
        }
        $error = empty($result['error_response']) ? $result : $result['error_response'];
        return DataReturn($error['sub_msg'].'('.$error['code'].')', -1);
    }

    /**
     * 待生成前面的字符
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-24
     * @desc    description
     * @param   [array]          $data [待生成的参数]
     */
    public static function SignCreateString($data)
    {
        $param = '';
        $sign  = '';
        ksort($data);

        foreach($data AS $key => $val)
        {
            $param .= "$key=" .urlencode($val). "&";
            $sign  .= "$key=$val&";
        }

        $result = [
            'param' =>  substr($param, 0, -1),
            'value' =>  substr($sign, 0, -1),
        ];
        return $result;
    }
    
    /**
     * 签名字符串
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-24
     * @desc    description
     * @param    [string]       $prestr [需要签名的字符串]
     * @return   [string]               [签名结果]
     */
    public static function MyRsaSign($prestr)
    {
        $res = "-----BEGIN RSA PRIVATE KEY-----\n";
        $res .= wordwrap(self::$config['rsa_private'], 64, "\n", true);
        $res .= "\n-----END RSA PRIVATE KEY-----";
        return openssl_sign($prestr, $sign, $res, OPENSSL_ALGO_SHA256) ? base64_encode($sign) : null;
    }
    
    /**
     * RSA解密
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-24
     * @desc    description
     * @param    [string]         $content [需要解密的内容，密文]
     * @return   [string]                  [解密后内容，明文]
     */
    public static function MyRsaDecrypt($content)
    {
        $res = "-----BEGIN PUBLIC KEY-----\n";
        $res .= wordwrap(self::$config['rsa_public'], 64, "\n", true);
        $res .= "\n-----END PUBLIC KEY-----";
        $res = openssl_get_privatekey($res);
        $content = base64_decode($content);
        $result  = '';
        for($i=0; $i<strlen($content)/128; $i++)
        {
            $data = substr($content, $i * 128, 128);
            openssl_private_decrypt($data, $decrypt, $res, OPENSSL_ALGO_SHA256);
            $result .= $decrypt;
        }
        unset($res);
        return $result;
    }
    
    /**
     * 支付宝验证签名
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-24
     * @desc    description
     * @param    [string]                   $prestr [需要签名的字符串]
     * @param    [string]                   $sign   [签名结果]
     * @return   [boolean]                          [正确true, 错误false]
     */
    public static function OutRsaVerify($prestr, $sign)
    {
        $res = "-----BEGIN PUBLIC KEY-----\n";
        $res .= wordwrap(self::$config['out_rsa_public'], 64, "\n", true);
        $res .= "\n-----END PUBLIC KEY-----";
        $pkeyid = openssl_pkey_get_public($res);
        $sign = base64_decode($sign);
        if($pkeyid)
        {
            $verify = openssl_verify($prestr, $sign, $pkeyid, OPENSSL_ALGO_SHA256);
            unset($pkeyid);
        }
        return (isset($verify) && $verify == 1) ? true : false;
    }

    /**
     * 配置信息交易
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]          $config [配置信息]
     */
    public static function ConfigCheck($config)
    {
        // 支持函数验证
        if(!function_exists('openssl_sign'))
        {
            return DataReturn('系统环境不支持openssl扩展', -1);
        }

        // 配置信息
        if(empty($config) || !is_array($config))
        {
            return DataReturn('配置信息有误', -1);
        }

        // 配置信息参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'app_id',
                'error_msg'         => 'appid配置为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'rsa_public',
                'error_msg'         => '应用公钥配置为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'rsa_private',
                'error_msg'         => '应用私钥配置为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'out_rsa_public',
                'error_msg'         => '支付宝公钥配置为空',
            ],
        ];
        $ret = ParamsChecked($config, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 属性赋值
        self::$config = $config;

        return DataReturn('success', 0);
    }
}
?>