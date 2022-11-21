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
namespace app\plugins\shop\service;

use think\facade\Db;
use app\plugins\shop\service\BaseService;

/**
 * 多商户 - 数据统计服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-02-14
 * @desc    description
 */
class StatisticalService
{
    // 近30天日期
    private static $nearly_thirty_days;

    /**
     * 初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function Init($params = [])
    {
        static $object = null;
        if(!is_object($object))
        {
            // 初始化标记对象，避免重复初始化
            $object = (object) [];

            // 近30天
            $nearly_all = [
                30  => 'nearly_thirty_days',
            ];
            foreach($nearly_all as $day=>$name)
            {
                $date = [];
                $time = time();
                for($i=0; $i<$day; $i++)
                {
                    $date[] = [
                        'start_time'    => strtotime(date('Y-m-d 00:00:00', time()-$i*3600*24)),
                        'end_time'      => strtotime(date('Y-m-d 23:59:59', time()-$i*3600*24)),
                        'name'          => date('Y-m-d', time()-$i*3600*24),
                    ];
                }
                
                self::${$name} = array_reverse($date);
            }
        }
    }

    /**
     * 收益趋势, 30天数据
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @date     2020-09-04
     * @param    [array]          $params [输入参数]
     */
    public static function ProfitThirtyDayTotal($params = [])
    {
        // 初始化
        self::Init($params);

        // 订单状态列表
        $status_list = BaseService::$plugins_profit_status_list;
        $status_arr = array_column($status_list, 'value');

        // 循环获取统计数据
        $data = [];
        $value_arr = [];
        $name_arr = [];
        if(!empty($status_arr))
        {
            foreach(self::$nearly_thirty_days as $day)
            {
                // 当前日期名称
                $name_arr[] = $day['name'];

                // 根据状态获取数量
                foreach($status_arr as $status)
                {
                    // 获取订单
                    $where = [
                        ['status', '=', $status],
                        ['add_time', '>=', $day['start_time']],
                        ['add_time', '<=', $day['end_time']],
                    ];
                    if(!empty($params['user']))
                    {
                        $where[] = ['user_id', '=', $params['user']['id']];
                    }
                    $value_arr[$status][] = Db::name('PluginsShopOrderProfit')->where($where)->sum('profit_price');
                }
            }
        }

        // 数据格式组装
        foreach($status_arr as $status)
        {
            $data[] = [
                'name'      => $status_list[$status]['name'],
                'type'      => ($status == 3) ? 'line' : 'bar',
                'tiled'     => '总量',
                'data'      => empty($value_arr[$status]) ? [] : $value_arr[$status],
            ];
        }

        // 数据组装
        $result = [
            'title_arr' => array_column($status_list, 'name'),
            'name_arr'  => $name_arr,
            'data'      => $data,
        ];
        return DataReturn('处理成功', 0, $result);
    }

    /**
     * 订单交易趋势, 30天数据
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @date     2020-09-04
     * @param    [array]          $params [输入参数]
     */
    public static function OrderThirtyDayTotal($params = [])
    {
        // 初始化
        self::Init($params);

        // 订单状态列表
        $order_status_list = MyConst('common_order_status');
        $status_arr = array_column($order_status_list, 'id');

        // 循环获取统计数据
        $data = [];
        $value_arr = [];
        $name_arr = [];
        if(!empty($status_arr))
        {
            foreach(self::$nearly_thirty_days as $day)
            {
                // 当前日期名称
                $name_arr[] = $day['name'];

                // 根据状态获取数量
                foreach($status_arr as $status)
                {
                    // 获取订单
                    $where = [
                        ['status', '=', $status],
                        ['add_time', '>=', $day['start_time']],
                        ['add_time', '<=', $day['end_time']],
                    ];
                    if(!empty($params['user']))
                    {
                        $where[] = ['shop_user_id', '=', $params['user']['id']];
                    }
                    $value_arr[$status][] = (int) Db::name('Order')->where($where)->count();
                }
            }
        }

        // 数据格式组装
        foreach($status_arr as $status)
        {
            $data[] = [
                'name'      => $order_status_list[$status]['name'],
                'type'      => ($status == 4) ? 'bar' : 'line',
                'tiled'     => '总量',
                'data'      => empty($value_arr[$status]) ? [] : $value_arr[$status],
            ];
        }

        // 数据组装
        $result = [
            'title_arr' => array_column($order_status_list, 'name'),
            'name_arr'  => $name_arr,
            'data'      => $data,
        ];
        return DataReturn('处理成功', 0, $result);
    }

    /**
     * 收益总额
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2020-09-04
     * @param    [int]    $status [结算状态（0待生效, 1生效中, 2待结算, 3已结算, 4已失效）]
     * @param    [int]    $user_id [用户id]
     */
    public static function ProfitPriceTotal($status = 0, $user_id = null, $field = 'profit_price')
    {
        $where = [
            'status'    => $status,
        ];
        if(!empty($user_id))
        {
            $where['user_id'] = intval($user_id);
        }
        return PriceNumberFormat(Db::name('PluginsShopOrderProfit')->where($where)->sum($field));
    }

    /**
     * 收益总额
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2020-09-04
     * @param    [int]    $status [订单状态（0待确认, 1已确认/待支付, 2已支付/待发货, 3已发货/待收货, 4已完成, 5已取消, 6已关闭）]
     * @param    [int]    $user_id [用户id]
     */
    public static function OrderCountTotal($status = 0, $user_id = null)
    {
        $where = [
            'status'    => $status,
        ];
        if(!empty($user_id))
        {
            $where['shop_user_id'] = intval($user_id);
        }
        return (int) Db::name('Order')->where($where)->count();
    }
}
?>