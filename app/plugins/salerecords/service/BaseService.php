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
namespace app\plugins\salerecords\service;

use app\service\PluginsService;

/**
 * 销售记录 - 基础服务层
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

    // 商品详情提示位置
    public static $goods_detail_tips_location = [
        [
            'value' => 'top-left',
            'name'  => '左上',
        ],
        [
            'value' => 'top-right',
            'name'  => '右上',
        ],
        [
            'value' => 'bottom-left',
            'name'  => '左下',
        ],
        [
            'value' => 'bottom-right',
            'name'  => '右下',
        ],
        [
            'value' => 'top-center',
            'name'  => '上中',
        ],
        [
            'value' => 'bottom-center',
            'name'  => '下中',
        ]
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
        return PluginsService::PluginsDataSave(['plugins'=>'salerecords', 'data'=>$params]);
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
        $ret = PluginsService::PluginsData('salerecords', self::$base_config_attachment_field, $is_cache);
        if($ret['code'] == 0 && !empty($ret['data']))
        {
            // 订单状态
            $ret['data']['order_status'] = empty($ret['data']['order_status']) ? [] : explode(',', $ret['data']['order_status']);
            $ret['data']['order_status_text'] = '';
            if(!empty($ret['data']['order_status']))
            {
                $order_status_name = [];
                foreach(MyConst('common_order_status') as $v)
                {
                    if(in_array($v['id'], $ret['data']['order_status']))
                    {
                        $order_status_name[] = $v['name'];
                    }
                }
                $ret['data']['order_status_text'] = empty($order_status_name) ? '' : implode('，', $order_status_name);
            }

            // 提示位置
            $ret['data']['goods_detail_tips_location_text'] = '';
            if(!empty($ret['data']['goods_detail_tips_location']))
            {
                $loc_arr = array_column(BaseService::$goods_detail_tips_location, 'name', 'value');
                if(array_key_exists($ret['data']['goods_detail_tips_location'], $loc_arr))
                {
                    $ret['data']['goods_detail_tips_location_text'] = $loc_arr[$ret['data']['goods_detail_tips_location']];
                }
            }
        }
        return $ret;
    }
}
?>