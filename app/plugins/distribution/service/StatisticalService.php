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
namespace app\plugins\distribution\service;

use think\facade\Db;

/**
 * 分销 - 数据统计服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-06-12T01:13:44+0800
 */
class StatisticalService
{
    // 近15天日期
    private static $nearly_fifteen_days;

    /**
     * 初始化
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-06-12T01:13:44+0800
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

            // 近15天
            $nearly_all = [
                15  => 'nearly_fifteen_days',
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
     * 收益趋势, 15天数据
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2019-06-12T01:13:44+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserProfitFifteenTodayTotal($params = [])
    {
        // 初始化
        self::Init($params);

        // 循环获取统计数据
        $data = [];
        $name_arr = [];
        foreach(self::$nearly_fifteen_days as $day)
        {
            // 当前日期名称
            $name_arr[] = $day['name'];

            // 获取收益金额
            $where = [
                ['add_time', '>=', $day['start_time']],
                ['add_time', '<=', $day['end_time']],
                ['status', '<=', 2],
            ];
            if(!empty($params['user']))
            {
                $where[] = ['user_id', '=', $params['user']['id']];
            }
            $data[] = PriceNumberFormat(Db::name('PluginsDistributionProfitLog')->where($where)->sum('profit_price'));
        }

        // 数据组装
        $result = [
            'name_arr'  => $name_arr,
            'data'      => $data,
        ];
        return DataReturn('处理成功', 0, $result);
    }

    /**
     * 推广用户趋势, 15天数据
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2019-06-12T01:13:44+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserExtensionFifteenTodayTotal($params = [])
    {
        // 初始化
        self::Init($params);

        // 循环获取统计数据
        $data = [];
        $name_arr = [];
        foreach(self::$nearly_fifteen_days as $day)
        {
            // 当前日期名称
            $name_arr[] = $day['name'];

            // 获取用户总数
            $where = [
                ['add_time', '>=', $day['start_time']],
                ['add_time', '<=', $day['end_time']],
            ];
            if(!empty($params['user']))
            {
                $where[] = ['referrer', '=', $params['user']['id']];
            } else {
                $where[] = ['referrer', '>', 0];
            }
            $data[] = Db::name('User')->where($where)->count('id');
        }

        // 数据组装
        $result = [
            'name_arr'  => $name_arr,
            'data'      => $data,
        ];
        return DataReturn('处理成功', 0, $result);
    }

    /**
     * 用户收益总额
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-05T22:08:34+0800
     * @param    [int]                   $status [结算状态（0待生效, 1待结算, 2已结算, 3已失效）]
     * @param    [int]                   $user_id [用户id]
     */
    public static function UserProfitPriceTotal($status = 0, $user_id = null, $field = 'profit_price')
    {
        $where = [
            'status'    => $status,
        ];
        if(!empty($user_id))
        {
            $where['user_id'] = intval($user_id);
        }
        return PriceNumberFormat(Db::name('PluginsDistributionProfitLog')->where($where)->sum($field));
    }

    /**
     * 获取推广用户数量
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-12-08T00:52:25+0800
     * @desc     description
     * @param    [array]                  $params [输入参数]
     */
    public static function UserExtensionTotal($params = 0)
    {
        // 用户总数
        $where = [];
        if(!empty($params['user']))
        {
            $where[] = ['referrer', '=', $params['user']['id']];
        } else {
            $where[] = ['referrer', '>', 0];
        }
        $user_count = (int) Db::name('User')->where($where)->count('id');

        // 已消费用户总数
        $where = [
            ['l.status', '<=', 2],
        ];
        if(!empty($params['user']))
        {
            $where[] = ['u.referrer', '=', $params['user']['id']];
            $valid_user_count = (int) Db::name('PluginsDistributionProfitLog')->alias('l')->join('user u', 'u.id=l.order_user_id')->where($where)->count('distinct u.id');
        } else {
            $valid_user_count = (int) Db::name('PluginsDistributionProfitLog')->count('distinct order_user_id');
        }
        
        return [
            'user_count'        => $user_count,
            'valid_user_count'  => $valid_user_count,
        ];
    }

    /**
     * 自提订单统计总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-05T22:08:34+0800
     * @param    [int]      $user_id [用户id]
     * @param    [int]      $status  [状态（0待处理, 1已处理）]
     */
    public static function ExtractionStatusTotal($user_id, $status = 0)
    {
        $where = [
            'o.status'  => [2,3,4],
            'po.status' => $status,
        ];
        if(!empty($user_id))
        {
            $where['po.user_id'] = intval($user_id);
        }
        return (int) Db::name('PluginsDistributionUserSelfExtractionOrder')->alias('po')->join('order o', 'o.id=po.order_id')->where($where)->count();
    }
}
?>