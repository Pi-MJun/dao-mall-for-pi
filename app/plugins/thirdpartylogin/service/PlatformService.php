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

use app\plugins\thirdpartylogin\service\BaseService;
use app\plugins\thirdpartylogin\service\PlatformUserService;

/**
 * 第三方登录 - 平台服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class PlatformService
{
    /**
     * 登录处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]           $config [插件配置信息]
     * @param   [array]           $params [输入参数]
     */
    public static function LoginHandle($config, $params = [])
    {
        // 指定跳转地址
        BaseService::SetRedirectUrl($params);

        // 设置来源
        BaseService::SetApplicationClientType($params);

        // 平台列表
        $platform_type_list = BaseService::PlatformTypeList($config, true);
        if(empty($platform_type_list))
        {
            return DataReturn('请确认是开启登陆', -1);
        }

        // 参数校验
        $ret = self::ParamsCheck($platform_type_list, $params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 模块映射
        $action = 'Login';
        $module = self::ModuleMapping($params['platform'], $action);
        if($module['code'] != 0)
        {
            return $module;
        }

        // 回调地址
        $params['redirect_uri'] = self::PlatformRedirectUri($params);

        // 应用配置信息
        $app_config = self::AppConfigHandle($platform_type_list[$params['platform']]['config'], $params);

        // 调用模块方法
        return $module['data']::$action($app_config, $params);
    }

    /**
     * 回调处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]           $config [插件配置信息]
     * @param   [array]           $params [输入参数]
     */
    public static function BackHandle($config, $params = [])
    {
        // 平台列表
        $platform_type_list = BaseService::PlatformTypeList($config, true);
        if(empty($platform_type_list))
        {
            return DataReturn('请确认是开启登陆', -1);
        }

        // 参数校验
        $ret = self::ParamsCheck($platform_type_list, $params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // state 值判断
        if(empty($params['state']))
        {
            return DataReturn('state标记值有误', -1);
        }
        $state_value = BaseService::RequestStateValue($params['platform']);
        if(empty($state_value))
        {
            return DataReturn('state标记值不存在', -1);
        }
        if($params['state'] != $state_value)
        {
            BaseService::RequestStateCacheRemove($params['platform']);
            return DataReturn('state标记值不一致', -1);
        }

        // 回调地址
        $params['redirect_uri'] = self::PlatformRedirectUri($params);

        // 模块映射
        $action = 'Back';
        $module = self::ModuleMapping($params['platform'], $action);
        if($module['code'] != 0)
        {
            return $module;
        }

        // 应用配置信息
        $app_config = self::AppConfigHandle($platform_type_list[$params['platform']]['config'], $params);

        // 调用模块方法
        $user = $module['data']::$action($app_config, $params);
        if($user['code'] != 0)
        {
            return $user;
        }

        // 用户登录信息处理
        $ret = PlatformUserService::PlatformUserLoginHandle($params['platform'], $user['data'], $config);

        // 清除跳转地址缓存
        BaseService::RedirectUrlCacheRemove();

        // 清除state标记值
        BaseService::RequestStateCacheRemove($params['platform']);

        // 返回处理数据
        return $ret;
    }

    /**
     * 绑定处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]           $config [插件配置信息]
     * @param   [array]           $params [输入参数]
     */
    public static function BindHandle($config, $params = [])
    {
        // 平台列表
        $platform_type_list = BaseService::PlatformTypeList($config, true);
        if(empty($platform_type_list))
        {
            return DataReturn('请确认是开启登陆', -1);
        }

        // 参数校验
        $ret = self::ParamsCheck($platform_type_list, $params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // app端则使用调用扩展类读取用户信息、则为H5端直接返回数据
        if(in_array(APPLICATION_CLIENT_TYPE, ['ios', 'android']))
        {
            // 模块映射
            $action = 'AppBind';
            $module = self::ModuleMapping($params['platform'], $action);
            if($module['code'] != 0)
            {
                return $module;
            }

            // 应用配置信息
            $app_config = self::AppConfigHandle($platform_type_list[$params['platform']]['config'], $params);

            // 调用模块方法
            $user = $module['data']::$action($app_config, $params);
            if($user['code'] != 0)
            {
                return $user;
            }
        } else {
            $user = [
                'data'  => [
                    'platform'  => isset($params['platform']) ? $params['platform'] : '',
                    'openid'  => isset($params['openid']) ? $params['openid'] : '',
                    'unionid'  => isset($params['unionid']) ? $params['unionid'] : '',
                    'nickname'  => isset($params['nickname']) ? $params['nickname'] : '',
                    'avatar'    => isset($params['avatar']) ? $params['avatar'] : '',
                    'mobile'    => isset($params['mobile']) ? $params['mobile'] : '',
                    'email'     => isset($params['email']) ? $params['email'] : '',
                    'gender'    => isset($params['gender']) ? intval($params['gender']) : 0,
                    'province'  => isset($params['province']) ? $params['province'] : '',
                    'city'      => isset($params['city']) ? $params['city'] : '',
                ]
            ];
        }

        // 用户登录信息处理
        $ret = PlatformUserService::PlatformUserLoginHandle($params['platform'], $user['data'], $config);

        // 清除跳转地址缓存
        BaseService::RedirectUrlCacheRemove();

        // 清除state标记值
        BaseService::RequestStateCacheRemove($params['platform']);

        // 返回处理数据
        return $ret;
    }

    /**
     * 配置信息处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-26
     * @desc    description
     * @param   [array]          $config [配置信息]
     * @param   [array]          $params [输入参数]
     */
    public static function AppConfigHandle($config, $params)
    {
        // 是否存在指定更多应用
        // 必须存在更多应用和指定标记存在
        $key = 'more_app';
        if(!empty($params) && !empty($params['appoint']) && !empty($config) && is_array($config) && array_key_exists($key, $config) && array_key_exists($params['appoint'], $config[$key]))
        {
            $config = $config[$key][$params['appoint']];
        }
        return $config;
    }

    /**
     * 平台回调地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-26
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function PlatformRedirectUri($params)
    {
        // 平台类型
        $up = ['platform'=>$params['platform']];

        // 指定账户
        if(!empty($params['appoint']))
        {
            $up['appoint'] = $params['appoint'];
        }
        return PluginsHomeUrl('thirdpartylogin', 'index', 'back', $up);
    }

    /**
     * 模块映射
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [string]          $platform [平台类型]
     * @param   [string]          $action   [调用方法]
     */
    public static function ModuleMapping($platform, $action)
    {
        // 映射模块处理
        $platform = ucfirst($platform);
        $module = '\app\plugins\thirdpartylogin\platform\\'.$platform.'Platform';
        if(!class_exists($module))
        {
            return DataReturn('平台模块未定义['.$platform.']', -1);
        }

        // 调用方法
        $action = ucfirst($action);
        if(!method_exists($module, $action))
        {
            return DataReturn('平台模块方法未定义['.$action.']', -1);
        }

        return DataReturn('success', 0, $module);
    }

    /**
     * 参数校验
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]          $platform_type_list [平台列表]
     * @param   [array]          $params             [输入参数]
     */
    public static function ParamsCheck($platform_type_list, $params)
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'platform',
                'error_msg'         => '平台有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'platform',
                'checked_data'      => array_keys($platform_type_list),
                'error_msg'         => '平台类型有误、请确认是否已开启登陆',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        return DataReturn('success', 0);
    }
}
?>