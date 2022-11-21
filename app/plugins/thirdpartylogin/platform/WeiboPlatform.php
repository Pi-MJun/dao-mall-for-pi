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
 * 第三方登录 - 新浪微博
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class WeiboPlatform
{
    // 平台类型
    public static $platform = 'weibo';

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
        $redirect_uri = $params['redirect_uri'];
        $display = 'default';
        $url = 'https://api.weibo.com/oauth2/authorize';
        if(IsMobile())
        {
            $display = 'mobile';
            $url = 'https://open.weibo.cn/oauth2/authorize';
        }
        $url .= '?client_id='.$config['app_id'].'&response_type=code&state='.$state.'&display='.$display.'&redirect_uri='.$redirect_uri;
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

        // 获取用户信息
        $ou = self::OpenUserInfo($bycode['data']['access_token'], $bycode['data']['uid']);
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
            'openid'    => $data['id'],
            'nickname'  => $data['name'],
            'avatar'    => $data['avatar_hd'],
            'gender'    => empty($data['gender']) || $data['gender'] == 'n' ? 0 : (($data['gender'] == 'm') ? 2 : 1),
        ];
    }

    /**
     * 通过用户id获取用户信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [string]         $access_token  [access_token]
     * @param   [string]         $uid           [uid]
     */
    public static function OpenUserInfo($access_token, $uid)
    {
        $url = 'https://api.weibo.com/2/users/show.json?uid='.$uid.'&access_token='.$access_token;
        $data = json_decode(CurlGet($url), true);
        if(!empty($data) && !empty($data['id']))
        {
            return DataReturn('获取成功', 0, $data);
        }
        $msg = empty($data['error']) ? '用户信息获取失败' : $data['error'];
        return DataReturn($msg.(isset($data['error_code']) ? '('.$data['error_code'].')' : ''), -1);
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
        $url = 'https://api.weibo.com/oauth2/access_token';
        $data = [
            'client_id'     => self::$config['app_id'],
            'client_secret' => self::$config['app_secret'],
            'grant_type'    => 'authorization_code',
            'code'          => $params['code'],
            'redirect_uri'  => $params['redirect_uri'],
        ];
        $ret = self::ApiRemoteData($url, $data);
        if($ret['code'] == 0)
        {
            // 是否存在 access_token
            if(empty($ret['data']['access_token']) || empty($ret['data']['uid']))
            {
                return DataReturn('获取access_token失败', -1);
            }
        }
        return $ret;
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

        // 是否失败
        if(!empty($result['error_description']) && !empty($result['error_code']))
        {
            return DataReturn($result['error_description'].'('.$result['error_code'].')', -1);
        }
        return DataReturn('success', 0, $result);
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