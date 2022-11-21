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
 * 第三方登录 - QQ
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class QqPlatform
{
    // 平台类型
    public static $platform = 'qq';

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
        $display = IsQQEnv() ? 'mobile' : '';
        $url = 'https://graph.qq.com/oauth2.0/authorize?client_id='.$config['app_id'].'&response_type=code&scope=get_user_info&state='.$state.'&display='.$display.'&redirect_uri='.$redirect_uri;
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

        // 配置信息参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'code',
                'error_msg'         => '回调code信息有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'redirect_uri',
                'error_msg'         => '回调地址为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 根据 code 获取用户信息
        $bycode = self::OpenUserByCode($params);
        if($bycode['code'] != 0)
        {
            return $bycode;
        }

        // 获取用户id
        $uid = self::OpenUserId($bycode['data']['access_token']);
        if($uid['code'] != 0)
        {
            return $uid;
        }

        // 获取用户信息
        $ou = self::OpenUserInfo($bycode['data']['access_token'], $uid['data']['openid']);
        if($ou['code'] != 0)
        {
            return $ou;
        }

        // 返回统一格式
        $user = self::UserReturnData($ou['data'], $uid['data']);
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

        // 获取用户id
        $uid = self::OpenUserId($params['access_token']);
        if($uid['code'] != 0)
        {
            return $uid;
        }

        // 获取用户信息
        $ou = self::OpenUserInfo($params['access_token'], $uid['data']['openid']);
        if($ou['code'] != 0)
        {
            return $ou;
        }

        // 返回统一格式
        $user = self::UserReturnData($ou['data'], $uid['data']);
        return DataReturn('success', 0, $user);
    }

    /**
     * 用户统一返回格式
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]          $data [用户基础信息]
     * @param   [array]          $uid  [用户id信息]
     */
    public static function UserReturnData($data, $uid)
    {
        return [
            'openid'    => $uid['openid'],
            'unionid'   => empty($uid['unionid']) ? '' : $uid['unionid'],
            'nickname'  => $data['nickname'],
            'avatar'    => $data['figureurl_qq'],
            'gender'    => empty($data['gender_type']) ? 0 : (($data['gender_type'] == 1) ? 2 :1),
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
     * @param   [string]         $access_token      [access_token]
     * @param   [string]         $openid            [openid]
     */
    public static function OpenUserInfo($access_token, $openid, $config = [])
    {
        // 是否指导配置信息
        if(!empty($config))
        {
            // 配置校验
            $ret = self::ConfigCheck($config);
            if($ret['code'] != 0)
            {
                return $ret;
            }
        }

        $url = 'https://graph.qq.com/user/get_user_info?fmt=json&oauth_consumer_key='.self::$config['app_id'].'&openid='.$openid.'&access_token='.$access_token;
        $data = json_decode(CurlGet($url), true);
        if(!empty($data) && isset($data['ret']) && $data['ret'] == 0)
        {
            return DataReturn('获取成功', 0, $data);
        }
        return DataReturn('用户信息获取失败'.(empty($data['error_description']) ? '' : '['.$data['error_description']).']', -1);
    }

    /**
     * 通过access_token获取用户id
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [string]         $access_token   [access_token]
     */
    public static function OpenUserId($access_token)
    {
        $url = 'https://graph.qq.com/oauth2.0/me?fmt=json&unionid=1&access_token='.$access_token;
        $data = json_decode(CurlGet($url), true);
        if(empty($data) || empty($data['openid']))
        {
            return DataReturn('获取openid失败'.(empty($data['error_description']) ? '' : '['.$data['error_description']).']', -1);
        }
        return DataReturn('获取成功', 0, $data);
    }

    /**
     * 通过临时code获取用户开放信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]         $params   [输入参数]
     */
    public static function OpenUserByCode($params)
    {
        $url = 'https://graph.qq.com/oauth2.0/token?client_id='.self::$config['app_id'].'&client_secret='.self::$config['app_secret'].'&code='.$params['code'].'&grant_type=authorization_code&fmt=json&redirect_uri='.urlencode($params['redirect_uri']);
        $data = json_decode(CurlGet($url), true);
        if(empty($data) || empty($data['access_token']))
        {
            return DataReturn('获取access_token失败'.(empty($data['error_description']) ? '' : '['.$data['error_description']).']', -1);
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