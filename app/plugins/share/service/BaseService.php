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
namespace app\plugins\share\service;

use app\service\PluginsService;

/**
 * 分享服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [
        'pic'
    ];

    // 基础私有字段
    public static $base_config_private_field = [
        'weixin_appid',
        'weixin_secret',
    ];

    // 分享类型列表
    public static $base_shar_type_list = [
        'qq'    => [
            'name'  => 'QQ好友',
            'tips'  => '分享到QQ好友',
        ],
        'qzone'    => [
            'name'  => 'QQ空间',
            'tips'  => '分享到QQ空间',
        ],
        'weixin'    => [
            'name'  => '微信',
            'tips'  => '分享到微信',
        ],
        'sina'    => [
            'name'  => '新浪微博',
            'tips'  => '分享到新浪微博',
        ],
        'twitter'    => [
            'name'  => 'Twitter',
            'tips'  => '分享到Twitter',
        ],
        'facebook'    => [
            'name'  => 'Facebook',
            'tips'  => '分享到Facebook',
        ],
        'url'    => [
            'name'  => '网址',
            'tips'  => '复制网址分享',
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
        $params['share_type'] = empty($params['share_type']) ? [] : explode(',', $params['share_type']);
        return PluginsService::PluginsDataSave(['plugins'=>'share', 'data'=>$params], self::$base_config_attachment_field);
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
        return PluginsService::PluginsData('share', self::$base_config_attachment_field, $is_cache);
    }
}
?>