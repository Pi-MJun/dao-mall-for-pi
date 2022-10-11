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
 * 第三方登录 - 钉钉
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class DingdingPlatform
{
    // 平台类型
    public static $platform = 'dingding';

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
        if(IsDingdingEnv())
        {
            $url = 'https://oapi.dingtalk.com/connect/oauth2/sns_authorize?appid='.$config['app_id'].'&response_type=code&scope=snsapi_auth&state='.$state.'&redirect_uri='.$redirect_uri;
        } else {
            $url = 'https://oapi.dingtalk.com/connect/qrconnect?appid='.$config['app_id'].'&response_type=code&scope=snsapi_login&state='.$state.'&redirect_uri='.$redirect_uri;
        }
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
        if(empty($params['code']))
        {
            return DataReturn('回调code信息有误', -1);
        }

        // 根据 code 获取用户信息
        $bycode = self::OpenUserByCode($params['code']);
        if($bycode['code'] != 0)
        {
            return $bycode;
        }

        // 获取用户id
        $uid = self::OpenUserId($bycode['data']['unionid']);
        if($uid['code'] != 0)
        {
            return $uid;
        }

        // 获取用户信息
        $ou = self::OpenUserInfo($uid['data']);
        if($ou['code'] != 0)
        {
            return $ou;
        }

        // 返回统一格式
        $user = self::UserReturnData($ou['data']);
        $user['openid'] = $bycode['data']['openid'];
        $user['unionid'] = $bycode['data']['unionid'];
 
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
            'avatar'    => $data['avatar'],
            'nickname'  =>  empty($data['name']) ? '钉钉用户-'.RandomString(6) : $data['name'],
            'mobile'    => isset($data['mobile']) ? $data['mobile'] : '',
            'email'     => isset($data['email']) ? $data['email'] : '',
        ];
    }

    /**
     * 通过用户id获取用户信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [string]         $uid   [uid]
     */
    public static function OpenUserInfo($uid)
    {
        // access_token
        $ac = self::AccessToken();
        if($ac['code'] != 0)
        {
            return $ac;
        }

        // 远程接口获取用户id
        $url = 'https://oapi.dingtalk.com/topapi/v2/user/get?access_token='.$ac['data'];
        $data = [
            'userid'    => $uid,
            'language'  => 'zh_CN',
        ];
        $ret = self::ApiRemoteData($url, $data);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 用户信息
        if(!empty($ret['data']['result']))
        {
            return DataReturn('success', 0, $ret['data']['result']);
        }
        return DataReturn('用户信息获取失败', -1);
    }

    /**
     * 通过unionid获取用户id
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [string]         $unionid   [unionid]
     */
    public static function OpenUserId($unionid)
    {
        // access_token
        $ac = self::AccessToken();
        if($ac['code'] != 0)
        {
            return $ac;
        }

        // 远程接口获取用户id
        // 应用需要开通权限[ 成员信息读权限 ]
        $url = 'https://oapi.dingtalk.com/topapi/user/getbyunionid?access_token='.$ac['data'];
        $data = ['unionid' => $unionid];
        $ret = self::ApiRemoteData($url, $data);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 用户id是否成功
        if(!empty($ret['data']['result']) && !empty($ret['data']['result']['userid']))
        {
            return DataReturn('success', 0, $ret['data']['result']['userid']);
        }
        
        return DataReturn('用户id获取失败', -1);
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
        $sign = self::SignCreate();
        $url = 'https://oapi.dingtalk.com/sns/getuserinfo_bycode?accessKey='.self::$config['app_id'].'&timestamp='.$sign['time'].'&signature='.$sign['value'];
        $data = ['tmp_auth_code' => $code];
        $ret = self::ApiRemoteData($url, $data);
        if($ret['code'] == 0 && isset($ret['data']['user_info']))
        {
            $ret['data'] = $ret['data']['user_info'];
        }
        return $ret;
    }

    /**
     * 获取access_token
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     */
    public static function AccessToken()
    {
        // 缓存key
        $key = 'plugins_thirdpartylogin_access_token_'.self::$platform.'_'.self::$config['app_id'];
        $result = MyCache($key);
        if(!empty($result))
        {
            if($result['expires_in'] > time())
            {
                return DataReturn('success', 0, $result['access_token']);
            }
        }

        // 网络请求
        $url = 'https://oapi.dingtalk.com/gettoken?appkey='.self::$config['app_id'].'&appsecret='.self::$config['app_secret'];
        $result = CurlGet($url);
        if(!empty($result))
        {
            $result = json_decode($result, true);
            if(!empty($result) && !empty($result['access_token']))
            {
                // 缓存存储
                $result['expires_in'] += time();
                MyCache($key, $result);
                return DataReturn('success', 0, $result['access_token']);
            } else {
                if(is_array($result))
                {
                    return DataReturn($result['errmsg'].'('.$result['errcode'].')', -1);
                }
            }
        }
        return DataReturn('acccess_token获取失败('.$result.')', -1);
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
        $ret = CurlPost($url, $data, true);
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
        if(isset($result['errcode']) && $result['errcode'] == 0)
        {
            return DataReturn('success', 0, $result);
        }
        return DataReturn($result['errmsg'].'('.$result['errcode'].')', -1);
    }

    /**
     * 签名生成
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     */
    public static function SignCreate()
    {
        $time =  self::Millisecond();
        $value = urlencode(base64_encode(hash_hmac('sha256', $time, self::$config['app_secret'], true)));
        return [
            'time'  => $time,
            'value' => $value,
        ];
    }

    /**
     * 获取毫秒时间戳
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     */
    public static function Millisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1)+floatval($t2))*1000);
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
                'key_name'          => 'app_secret',
                'error_msg'         => 'appsecret配置为空',
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