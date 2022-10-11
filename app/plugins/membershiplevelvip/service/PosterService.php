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
namespace app\plugins\membershiplevelvip\service;

use app\service\ResourcesService;
use app\service\UserService;
use app\service\SystemService;
use app\service\AppMiniUserService;

/**
 * 会员等级服务层 - 推广
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class PosterService
{
    /**
     * 获取用户分享url地址
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2019-12-29
     * @param    [int]          $user_id [用户id]
     * @param    [array]        $config [插件配置]
     */
    public static function UserShareUrl($user_id, $config)
    {
        // 当前站点地址
        $url = __MY_URL__;

        // h5端地址处理、是否自定义h5地址
        if(APPLICATION == 'app' && !empty($config['h5_url']))
        {
            $url = $config['h5_url'];
        }

        return $url.'?referrer='.UserService::UserReferrerEncryption($user_id);
    }

    /**
     * 手机端首页页面地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-02-21
     * @desc    description
     * @param   [array]          $config [插件配置]
     */
    public static function MobileHomePage($config)
    {
        return empty($config['mobile_home_page']) ? 'pages/index/index' : $config['mobile_home_page'];
    }

    /**
     * 用户分享二维码生成
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-29
     * @desc    description
     * @param   [int]          $user_id         [用户id]
     * @param   [int]          $user_add_time   [用户创建时间]
     * @param   [array]        $config          [插件配置]
     * @param   [boolean]      $is_refresh      [是否刷新]
     */
    public static function UserShareQrcodeCreate($user_id, $user_add_time, $config, $is_refresh = false)
    {
        if(!empty($user_id) && !empty($user_add_time))
        {
            // 系统类型
            $system_type = SystemService::SystemTypeValue();

            // 自定义路径和名称
            $path = 'static'.DS.'upload'.DS.'images'.DS.'plugins_membershiplevelvip'.DS.'qrcode'.DS.$system_type.DS.APPLICATION_CLIENT_TYPE.DS.date('Y', $user_add_time).DS.date('m', $user_add_time).DS.date('d', $user_add_time).DS;
            $filename = date('YmdHis', $user_add_time).$user_id.'.png';

            // 二维码处理参数
            $params = [
                'path'      => DS.$path,
                'filename'  => $filename,
                'dir'       => ROOT.'public'.DS.$path.$filename,
            ];

            // 目录不存在则创建
            if(\base\FileUtil::CreateDir(ROOT.'public'.DS.$path) !== true)
            {
                return DataReturn('二维码目录创建失败', -1);
            }

            // 不存在则创建
            if(!file_exists($params['dir']) || $is_refresh === true)
            {
                // 根据客户端类型生成不同的二维码
                switch(APPLICATION_CLIENT_TYPE)
                {
                    // 微信小程序
                    case 'weixin' :
                        $ret = self::UserCreateMiniWechatQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // QQ小程序
                    case 'qq' :
                        $ret = self::UserCreateMiniQQQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // 支付宝小程序
                    case 'alipay' :
                        $ret = self::UserCreateMiniAlipayQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // 头条小程序
                    case 'toutiao' :
                        $ret = self::UserCreateMiniToutiaoQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // 百度小程序
                    case 'baidu' :
                        $ret = self::UserCreateMiniBaiduQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // 快手小程序
                    case 'kuaishou' :
                        $ret = self::UserCreateMiniKuaishouQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // 默认
                    default :
                        // url
                        $params['content'] = self::UserShareUrl($user_id, $config);

                        // 创建二维码
                        $ret = (new \base\Qrcode())->Create($params);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                }
            }
            return DataReturn('处理成功', 0, ResourcesService::AttachmentPathViewHandle($params['path'].$params['filename']));
        }
        return DataReturn('用户id有误', -100);
    }

    /**
     * 快手小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniKuaishouQrcode($params, $user_id, $config)
    {
        // 配置信息
        $appid = AppMiniUserService::AppMiniConfig('common_app_mini_kuaishou_appid');

        // 二维码内容
        $url = 'kwai://miniapp?appId='.$appid.'&KSMP_source=011012&KSMP_internal_source=011012&path='.urlencode(self::MobileHomePage($config).'?referrer='.$user_id);
        $params['content'] = $url;

        // 创建二维码
        $ret = (new \base\Qrcode())->Create($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }
        return DataReturn('获取成功', 0);
    }

    /**
     * 微信小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniWechatQrcode($params, $user_id, $config)
    {
        // 配置信息
        $appid = AppMiniUserService::AppMiniConfig('common_app_mini_weixin_appid');
        $appsecret = AppMiniUserService::AppMiniConfig('common_app_mini_weixin_appsecret');
        if(empty($appid) || empty($appsecret))
        {
            return DataReturn('微信小程序密钥未配置', -1);
        }

        // 请求参数
        $request_params = [
            'page'  => self::MobileHomePage($config),
            'scene' => 'share=vip&referrer='.$user_id,
            'width' => 300,
        ];
        $ret = (new \base\Wechat($appid, $appsecret))->MiniQrCodeCreate($request_params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 保存二维码
        if(@file_put_contents($params['dir'], $ret['data']) !== false)
        {
            return DataReturn('获取成功', 0);
        }
        return DataReturn('二维码保存失败', -1);
    }

    /**
     * qq小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniQQQrcode($params, $user_id, $config)
    {
        // 配置信息
        $appid = AppMiniUserService::AppMiniConfig('common_app_mini_qq_appid');

        // 二维码内容
        $url = 'https://m.q.qq.com/a/p/'.$appid.'?s='.urlencode(self::MobileHomePage($config).'?referrer='.$user_id);
        $params['content'] = $url;

        // 创建二维码
        $ret = (new \base\Qrcode())->Create($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }
        return DataReturn('获取成功', 0);
    }

    /**
     * 支付宝小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniAlipayQrcode($params, $user_id, $config)
    {
        // 配置信息
        $appid = AppMiniUserService::AppMiniConfig('common_app_mini_alipay_appid');
        if(empty($appid))
        {
            return DataReturn('支付宝小程序密钥未配置', -1);
        }

        // 请求参数
        $request_params = [
            'appid' => $appid,
            'page'  => self::MobileHomePage($config),
            'scene' => 'share=vip&referrer='.$user_id,
            'width' => 300,
        ];
        $ret = (new \base\Alipay())->MiniQrCodeCreate($request_params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 保存二维码
        if(@file_put_contents($params['dir'], file_get_contents($ret['data'])) !== false)
        {
            return DataReturn('获取成功', 0);
        }
        return DataReturn('二维码保存失败', -1);
    }

    /**
     * 头条小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniToutiaoQrcode($params, $user_id, $config)
    {
        // 配置信息
        $config = [
            'appid'     => AppMiniUserService::AppMiniConfig('common_app_mini_toutiao_appid'),
            'secret'    => AppMiniUserService::AppMiniConfig('common_app_mini_toutiao_appsecret'),
        ];
        if(empty($config['appid']) || empty($config['secret']))
        {
            return DataReturn('头条小程序密钥未配置', -1);
        }

        // 请求参数
        $request_params = [
            'page'  => self::MobileHomePage($config),
            'scene' => 'share=vip&referrer='.$user_id,
            'width' => 300,
        ];

        $ret = (new \base\Toutiao($config))->MiniQrCodeCreate($request_params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 保存二维码
        if(@file_put_contents($params['dir'], $ret['data']) !== false)
        {
            return DataReturn('获取成功', 0);
        }
        return DataReturn('二维码保存失败', -1);
    }

    /**
     * 百度小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniBaiduQrcode($params, $user_id, $config)
    {
        // 配置信息
        $config = [
            'appid'     => AppMiniUserService::AppMiniConfig('common_app_mini_baidu_appid'),
            'key'       => AppMiniUserService::AppMiniConfig('common_app_mini_baidu_appkey'),
            'secret'    => AppMiniUserService::AppMiniConfig('common_app_mini_baidu_appsecret'),
        ];
        if(empty($config['appid']) || empty($config['key']) || empty($config['secret']))
        {
            return DataReturn('百度小程序密钥未配置', -1);
        }

        // 请求参数
        $request_params = [
            'page'  => self::MobileHomePage($config),
            'scene' => 'share=vip&referrer='.$user_id,
            'width' => 300,
        ];

        $ret = (new \base\Baidu($config))->MiniQrCodeCreate($request_params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 保存二维码
        if(@file_put_contents($params['dir'], $ret['data']) !== false)
        {
            return DataReturn('获取成功', 0);
        }
        return DataReturn('二维码保存失败', -1);
    }
}
?>