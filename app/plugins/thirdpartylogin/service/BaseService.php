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
namespace app\plugins\thirdpartylogin\service;

use app\service\PluginsService;

/**
 * 第三方登录 - 基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [];

    // 基础私有字段
    public static $base_config_private_field = [
        'alipay_app_id',
        'alipay_rsa_public',
        'alipay_rsa_private',
        'alipay_out_rsa_public',
        'weixin_app_id',
        'weixin_app_secret',
        'weixin_web_id',
        'weixin_web_secret',
        'weixin_public_id',
        'weixin_public_secret',
        'qq_app_id',
        'qq_app_secret',
        'qq_web_id',
        'qq_web_secret',
        'weibo_app_id',
        'weibo_app_secret',
        'dingding_app_name',
        'dingding_app_id',
        'dingding_app_secret',
    ];

    // 登录绑定平台用户 id 缓存 key
    public static $bind_platform_user_key = 'plugins_thirdpartylogin_platform_user_id';

    // 指定跳转地址缓存 key
    public static $back_redirect_url_key = 'plugins_thirdpartylogin_back_redirect_url';

    // 防止csrf攻击请求 key
    public static $request_state_key = 'plugins_thirdpartylogin_request_state_key';

    // 来源客户端
    public static $application_client_type_key = 'plugins_thirdpartylogin_application_client_type_key';

    // 绑定平台
    public static $platform_type_list = [
            'dingding' => [
                'name'      => '钉钉',
                'bg_color'  => '#3297fa',
                'config'    => [],
            ],
            'alipay' => [
                'name'      => '支付宝',
                'bg_color'  => '#01aaf1',
                'config'    => [],
            ],
            'weixin' => [
                'name'      => '微信',
                'bg_color'  => '#00c800',
                'config'    => [],
            ],
            'qq' => [
                'name'      => 'QQ',
                'bg_color'  => '#ff9800',
                'config'    => [],
            ],
            'weibo' => [
                'name'      => '微博',
                'bg_color'  => '#f70b1a',
                'config'    => [],
            ],
            'iphone' => [
                'name'      => '苹果',
                'bg_color'  => '#666',
                'config'    => [],
            ],
        ];

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        // 钉钉更多帐号
        if(!empty($params['dingding_more_app']) && is_array($params['dingding_more_app']))
        {
            $dingding_more_app = [];
            foreach($params['dingding_more_app'] as $v)
            {
                if(!empty($v['app_id']) && !empty($v['app_secret']))
                {
                    $dingding_more_app[md5($v['app_id'])] = $v;
                }
            }
            $params['dingding_more_app'] = $dingding_more_app;
        }

        return PluginsService::PluginsDataSave(['plugins'=>'thirdpartylogin', 'data'=>$params]);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        return PluginsService::PluginsData('thirdpartylogin', self::$base_config_attachment_field, $is_cache);
    }

    /**
     * 平台列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]           $config       [配置信息]
     * @param   [boolean]         $is_config    [是否需要配置信息]
     */
    public static function PlatformTypeList($config, $is_config = false)
    {
        $result = [];
        if(!empty($config) && is_array($config))
        {
            // 来源终端
            $application_client_type = BaseService::GetApplicationClientType();

            // 附件url
            $attachment_host = MyConfig('shopxo.attachment_host');

            // 钉钉
            $platform = 'dingding';
            if(isset($config['dingding_is_enable']) && $config['dingding_is_enable'] == 1 && isset(self::$platform_type_list[$platform]))
            {
                $platform_config = self::$platform_type_list[$platform];
                if($is_config)
                {
                    $platform_config['config'] = [
                        'app_name'          => empty($config['dingding_app_name']) ? '' : $config['dingding_app_name'],
                        'app_id'            => empty($config['dingding_app_id']) ? '' : $config['dingding_app_id'],
                        'app_secret'        => empty($config['dingding_app_secret']) ? '' : $config['dingding_app_secret'],
                        'is_env_auto_login' => empty($config['dingding_is_env_auto_login']) ? '' : $config['dingding_is_env_auto_login'],
                        'more_app'          => empty($config['dingding_more_app']) ? [] : $config['dingding_more_app'],
                    ];
                }
                $result[$platform] = $platform_config;
            }

            // 支付宝
            $platform = 'alipay';
            if(isset($config['alipay_is_enable']) && $config['alipay_is_enable'] == 1 && isset(self::$platform_type_list[$platform]))
            {
                $platform_config = self::$platform_type_list[$platform];
                if($is_config)
                {
                    $platform_config['config'] = [
                        'app_id'            => empty($config['alipay_app_id']) ? '' : $config['alipay_app_id'],
                        'rsa_public'        => empty($config['alipay_rsa_public']) ? '' : $config['alipay_rsa_public'],
                        'rsa_private'       => empty($config['alipay_rsa_private']) ? '' : $config['alipay_rsa_private'],
                        'out_rsa_public'    => empty($config['alipay_out_rsa_public']) ? '' : $config['alipay_out_rsa_public'],
                        'is_env_auto_login' => empty($config['alipay_is_env_auto_login']) ? '' : $config['alipay_is_env_auto_login'],
                    ];
                }
                $result[$platform] = $platform_config;
            }

            // 微信
            $platform = 'weixin';
            if(isset($config['weixin_is_enable']) && $config['weixin_is_enable'] == 1 && isset(self::$platform_type_list[$platform]))
            {
                $platform_config = self::$platform_type_list[$platform];
                if($is_config)
                {
                    if(in_array($application_client_type, ['pc', 'h5']))
                    {
                        if(IsWeixinEnv())
                        {
                            $app_id = empty($config['weixin_public_id']) ? '' : $config['weixin_public_id'];
                            $app_secret = empty($config['weixin_public_secret']) ? '' : $config['weixin_public_secret'];
                        } else {
                            $app_id = empty($config['weixin_web_id']) ? '' : $config['weixin_web_id'];
                            $app_secret = empty($config['weixin_web_secret']) ? '' : $config['weixin_web_secret'];
                        }
                    } else {
                        $app_id = empty($config['weixin_app_id']) ? '' : $config['weixin_app_id'];
                        $app_secret = empty($config['weixin_app_secret']) ? '' : $config['weixin_app_secret'];
                    }
                    $platform_config['config'] = [
                        'app_id'                => $app_id,
                        'app_secret'            => $app_secret,
                        'public_is_auth_base'   => (isset($config['weixin_public_is_auth_base']) && $config['weixin_public_is_auth_base'] == 1) ? 1 : 0,
                    ];
                }
                $result[$platform] = $platform_config;
            }

            // QQ
            $platform = 'qq';
            if(isset($config['qq_is_enable']) && $config['qq_is_enable'] == 1 && isset(self::$platform_type_list[$platform]))
            {
                $platform_config = self::$platform_type_list[$platform];
                if($is_config)
                {
                    if(in_array($application_client_type, ['pc', 'h5']))
                    {
                        $app_id = empty($config['qq_web_id']) ? '' : $config['qq_web_id'];
                        $app_secret = empty($config['qq_web_secret']) ? '' : $config['qq_web_secret'];
                    } else {
                        $app_id = empty($config['qq_app_id']) ? '' : $config['qq_app_id'];
                        $app_secret = empty($config['qq_app_secret']) ? '' : $config['qq_app_secret'];
                    }
                    $platform_config['config'] = [
                        'app_id'            => $app_id,
                        'app_secret'        => $app_secret,
                        'is_env_auto_login' => empty($config['qq_is_env_auto_login']) ? '' : $config['qq_is_env_auto_login'],
                    ];
                }
                $result[$platform] = $platform_config;
            }

            // 新浪微博
            $platform = 'weibo';
            if(isset($config['weibo_is_enable']) && $config['weibo_is_enable'] == 1 && isset(self::$platform_type_list[$platform]))
            {
                $platform_config = self::$platform_type_list[$platform];
                if($is_config)
                {
                    $platform_config['config'] = [
                        'app_id'            => empty($config['weibo_app_id']) ? '' : $config['weibo_app_id'],
                        'app_secret'        => empty($config['weibo_app_secret']) ? '' : $config['weibo_app_secret'],
                        'is_env_auto_login' => empty($config['weibo_is_env_auto_login']) ? '' : $config['weibo_is_env_auto_login'],
                    ];
                }
                $result[$platform] = $platform_config;
            }

            // 苹果、目前仅支持ios端
            if(APPLICATION_CLIENT_TYPE == 'ios')
            {
                $platform = 'iphone';
                if(isset($config['iphone_is_enable']) && $config['iphone_is_enable'] == 1 && isset(self::$platform_type_list[$platform]))
                {
                    $platform_config = self::$platform_type_list[$platform];
                    if($is_config)
                    {
                        $platform_config['config'] = [];
                    }
                    $result[$platform] = $platform_config;
                }
            }

            // icon增加
            if(!empty($result))
            {
                foreach($result as $k=>$v)
                {
                    $up = [
                        'platform'                  => $k,
                        'application_client_type'   => APPLICATION_CLIENT_TYPE,
                    ];
                    $result[$k]['login_url'] = PluginsHomeUrl('thirdpartylogin', 'index', 'login', $up);
                    $result[$k]['icon'] = $attachment_host.'/static/plugins/images/thirdpartylogin/icon/'.$k.'.png';
                }
            }
        }
        return $result;
    }

    /**
     * 获取平台名称
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-22
     * @desc    description
     * @param   [array]          $config   [插件配置信息]
     * @param   [string]         $platform [平台标记]
     */
    public static function PlatformTypeName($config, $platform)
    {
        $platform_list = self::$platform_type_list;
        if(!empty($platform_list) && array_key_exists($platform, $platform_list))
        {
            return $platform_list[$platform]['name'];
        }
        return '';
    }

    /**
     * 设置指定跳转地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function SetRedirectUrl($params = [])
    {
        // 指定跳转地址
        if(!empty($params['redirect_url']))
        {
            MySession( self::$back_redirect_url_key, base64_decode(urldecode($params['redirect_url'])));
        }
    }

    /**
     * 获取回调跳转地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-23
     * @desc    description
     */
    public static function BackRedirectUrl()
    {
        $url = MySession(self::$back_redirect_url_key);
        return empty($url) ? __MY_URL__ : $url;
    }

    /**
     * 清除缓存-平台用户信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-23
     * @desc    description
     */
    public static function PlatformUserCacheRemove()
    {
        MySession(self::$bind_platform_user_key, null);
    }

    /**
     * 清除缓存-指定跳转地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-23
     * @desc    description
     */
    public static function RedirectUrlCacheRemove()
    {
        MySession(self::$back_redirect_url_key, null);
    }

    /**
     * 防止csrf攻击state值生成
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-24
     * @desc    description
     * @param   [string]          $platform [平台标识]
     */
    public static function RequestStateCreate($platform)
    {
        $value = RandomString(10);
        MySession(self::$request_state_key.$platform, $value);
        return $value;
    }

    /**
     * 防止csrf攻击state值获取
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-24
     * @desc    description
     * @param   [string]          $platform [平台标识]
     */
    public static function RequestStateValue($platform)
    {
        return MySession(self::$request_state_key.$platform);
    }

    /**
     * 防止csrf攻击state值清除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-24
     * @desc    description
     * @param   [string]          $platform [平台标识]
     */
    public static function RequestStateCacheRemove($platform)
    {
        MySession(self::$request_state_key.$platform, null);
    }

    /**
     * 设置客户端类型
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-12-05
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function SetApplicationClientType($params = [])
    {
        if(!empty($params['application_client_type']))
        {
            MySession(self::$application_client_type_key, $params['application_client_type']);
        }
    }

    /**
     * 获取客户端类型
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-12-05
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GetApplicationClientType($params = [])
    {
        $res = MySession(self::$application_client_type_key);
        return empty($res) ? APPLICATION_CLIENT_TYPE : $res;
    }

    /**
     * h5地址错误页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-12-05
     * @desc    description
     * @param   [array]         $config [配置信息]
     * @param   [string]        $msg    [错误信息]
     * @param   [int]           $code   [错误码]
     */
    public static function H5PageErrorUrl($config, $msg, $code = -1)
    {
        if(!empty($config['h5_url']))
        {
            $join = (stripos($config['h5_url'], '?') === false) ? '?' : '&';
            return $config['h5_url'].'pages/login/login'.$join.'msg='.urlencode(base64_encode($msg)).'&code='.$code;
        }
        return '';
    }

    /**
     * h5地址成功页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-12-05
     * @desc    description
     * @param   [array]         $config [配置信息]
     * @param   [array]         $data   [成功的数据]
     */
    public static function H5PageSuccessUrl($config, $data)
    {
        if(!empty($config['h5_url']))
        {
            $join = (stripos($config['h5_url'], '?') === false) ? '?' : '&';
            return $config['h5_url'].'pages/login/login'.$join.'thirdpartylogin='.urlencode(base64_encode(json_encode($data)));
        }
        return '';
    }
}
?>