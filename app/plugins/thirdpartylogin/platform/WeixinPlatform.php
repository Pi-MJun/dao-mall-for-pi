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
 * 第三方登录 - 微信
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class WeixinPlatform
{
    // 平台类型
    public static $platform = 'weixin';

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
        if(IsWeixinEnv())
        {
            $auth_type = (self::$config['public_is_auth_base'] == 1) ? 'snsapi_base' : 'snsapi_userinfo';
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.self::$config['app_id'].'&redirect_uri='.$redirect_uri.'&response_type=code&scope='.$auth_type.'&state='.$state.'#wechat_redirect';
        } else {
            $url = 'https://open.weixin.qq.com/connect/qrconnect?appid='.self::$config['app_id'].'&response_type=code&redirect_uri='.$redirect_uri.'&scope=snsapi_login&state='.$state.'#wechat_redirect';
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

        // 获取用户信息
        if(IsWeixinEnv() && self::$config['public_is_auth_base'] == 1)
        {
            // 返回统一格式
            $user = self::UserReturnData($bycode['data']);
        } else {
            $ou = self::OpenUserInfo($bycode['data']);
            if($ou['code'] != 0)
            {
                return $ou;
            }

            // 返回统一格式
            $user = self::UserReturnData($ou['data']);
        }
        return DataReturn('success', 0, $user);
    }

    /**
     * app绑定处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]          $config [平台配置信息]
     * @param   [array]          $params [输入参数]
     */
    public static function AppBind($config, $params = [])
    {
        // 配置校验
        $ret = self::ConfigCheck($config);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'openid',
                'error_msg'         => 'openid为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'access_token',
                'error_msg'         => 'access_token为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取用户信息
        $ou = self::OpenUserInfo($params);
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
            'openid'    => $data['openid'],
            'unionid'   => empty($data['unionid']) ? '' : $data['unionid'],
            'nickname'  => empty($data['nickname']) ? '微信用户' : $data['nickname'],
            'avatar'    => empty($data['headimgurl']) ? '' : $data['headimgurl'],
            'gender'    => empty($data['gender']) ? 0 : (($data['gender'] == 1) ? 2 : 1),
            'province'  => empty($data['province']) ? '' : $data['province'],
            'city'      => empty($data['city']) ? '' : $data['city'],
        ];
    }

    /**
     * 通过用户id获取用户信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]         $data   [用户授权信息]
     */
    public static function OpenUserInfo($data)
    {
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$data['access_token'].'&openid='.$data['openid'].'&lang=zh_CN';
        $data = json_decode(CurlGet($url), true);
        if(empty($data) || empty($data['openid']))
        {
            return DataReturn(empty($data['errmsg']) ? '获取用户信息失败' : $data['errmsg'], -1);
        }
        return DataReturn('获取成功', 0, $data);
    }

    /**
     * 通过code获取用户开放信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [string]         $code   [临时code]
     */
    public static function OpenUserByCode($code)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.self::$config['app_id'].'&secret='.self::$config['app_secret'].'&code='.$code.'&grant_type=authorization_code';
        $data = json_decode(CurlGet($url), true);
        if(empty($data) || empty($data['access_token']) || empty($data['openid']))
        {
            return DataReturn(empty($data['errmsg']) ? '获取access_token失败' : $data['errmsg'], -1);
        }
        return DataReturn('获取成功', 0, $data);
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