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
namespace app\plugins\popupscreen\service;

use app\service\PluginsService;

/**
 * 弹屏广告 - 基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = ['images'];

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
        return PluginsService::PluginsDataSave(['plugins'=>'popupscreen', 'data'=>$params]);
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
        $ret = PluginsService::PluginsData('popupscreen', self::$base_config_attachment_field, $is_cache);
        if(empty($ret['data']))
        {
            $ret['data'] = [];
        }

        // url地址处理
        $module = RequestModule();
        $ret['data']['images_url'] = empty($ret['data']['images_url']) ? [] : (is_array($ret['data']['images_url']) ? $ret['data']['images_url'] : json_decode($ret['data']['images_url'], true));
        if($module != 'admin')
        {
            if(!empty($ret['data']['images_url']) && is_array($ret['data']['images_url']) && array_key_exists(APPLICATION_CLIENT_TYPE, $ret['data']['images_url']))
            {
                $ret['data']['images_url'] = $ret['data']['images_url'][APPLICATION_CLIENT_TYPE];
            } else {
                $ret['data']['images_url'] = '';
            }
        }
        return $ret;
    }
}
?>