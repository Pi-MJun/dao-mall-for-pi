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
namespace app\plugins\databoard\service;

use app\service\PluginsService;

/**
 * 数据看板 - 基础服务层
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
        return PluginsService::PluginsDataSave(['plugins'=>'databoard', 'data'=>$params], self::$base_config_attachment_field);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * 
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        return PluginsService::PluginsData('databoard', self::$base_config_attachment_field, $is_cache);
    }

    /**
     * 区间时间创建
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-08-30
     * @desc    description
     * @param   [int]          $start [起始时间]
     * @param   [int]          $end   [结束时间]
     * @param   [string]       $str   [时间操作符号]
     */
    public static function DayCreate($start, $end, $str = '+1 day')
    {
        $data = [];
        while(true)
        {
            // 计算时间条件
            $temp_end = strtotime($str, $start);

            // 最大时间减1秒，条件使用 start >= ? && end <= ?
            // start 2021-01-01 00:00:00 , end 2021-01-01 23:59:58
            $data[] = [
                'start' => $start,
                'end'   => $temp_end-1,
                'date'  => date('Y-m-d H:i:s', $start).' - '.date('Y-m-d H:i:s', $temp_end-1),
            ];

            // 结束跳出循环
            if($temp_end >= $end)
            {
                // 结束使用最大时间替代计算的最后一个最大时间
                $count = count($data)-1;
                $data[$count]['end'] = $end;
                $data[$count]['date'] = date('Y-m-d H:i:s', $data[$count]['start']).' - '.date('Y-m-d H:i:s', $end);
                break;
            }
            $start = $temp_end;
        }
        return $data;
    }

    /**
     * 订单类型、兼容老版本处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-28
     * @desc    description
     */
    public static function OrderTypeList()
    {
        $result = [];
        $order_type_list = MyConst('common_order_type_list');
        if(empty($order_type_list))
        {
            $order_type_list = MyConst('common_site_type_list');
        }
        if(!empty($order_type_list))
        {
            foreach($order_type_list as $v)
            {
                if(in_array($v['value'], [0,2,3]))
                {
                    $result[] = $v;
                }
            }
        }
        return $result;
    }
}
?>