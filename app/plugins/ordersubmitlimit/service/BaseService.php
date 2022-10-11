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
namespace app\plugins\ordersubmitlimit\service;

use app\service\PluginsService;

/**
 * 订单提交限制 - 基础
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [];

    // 订单金额类型
    public static $price_limit_type_list =  [
        0 => array('value' => 0, 'field' => 'price', 'name' => '订单单价', 'checked' => true),
        1 => array('value' => 1, 'field' => 'total_price', 'name' => '订单总价(订单最终价格)'),
    ];

    // 订单提交数量限制类型
    public static $number_limit_type_list =  [
        0 => array('value' => 0, 'name' => 'SKU数量', 'checked' => true),
        1 => array('value' => 1, 'name' => '商品'),
    ];

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'ordersubmitlimit', 'data'=>$params], self::$base_config_attachment_field);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        $ret = PluginsService::PluginsData('ordersubmitlimit', self::$base_config_attachment_field, $is_cache);

        if(!empty($ret['data']))
        {
            // 订单模式
            $ret['data']['order_model'] = empty($ret['data']['order_model']) ? [] : (is_array($ret['data']['order_model']) ? $ret['data']['order_model'] : explode(',', $ret['data']['order_model']));

            // 订单模式
            $order_model_names = [];
            if(!empty($ret['data']['order_model']))
            {
                $site_type_list = MyConst('common_site_type_list');
                foreach($ret['data']['order_model'] as $value)
                {
                    if(isset($site_type_list[$value]))
                    {
                        $order_model_names[] = $site_type_list[$value]['name'];
                    }
                }
            }
            $ret['data']['order_model_names'] = empty($order_model_names) ? '' : implode(',', $order_model_names);
        }
        return $ret;
    }
}
?>