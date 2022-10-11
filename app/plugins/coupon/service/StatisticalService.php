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
namespace app\plugins\coupon\service;

use think\facade\Db;

/**
 * 统计服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @date     2019-08-08
 */
class StatisticalService
{
    // 昨天日期
    private static $yesterday_time_start;
    private static $yesterday_time_end;

    // 今天日期
    private static $today_time_start;
    private static $today_time_end;

    /**
     * 初始化
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-02-22
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

            // 昨天日期
            self::$yesterday_time_start = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
            self::$yesterday_time_end = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));

            // 今天日期
            self::$today_time_start = strtotime(date('Y-m-d 00:00:00'));
            self::$today_time_end = time();
        }
    }

    /**
     * 数据总数,今日,昨日,总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @date     2019-08-08
     * @param    [array]          $params [输入参数]
     */
    public static function YesterdayTodayTotal($params = [])
    {
        // 扩展数据
        $ext_count = 0;

        // 操作类型
        if(!empty($params['type']))
        {
            switch($params['type'])
            {
                // 优惠券
                case 'coupon' :
                    $table = 'PluginsCoupon';
                    break;

                // 用户优惠券
                case 'couponuser' :
                    $table = 'PluginsCouponUser';

                    // 扩展数据
                    $ext_count = Db::name($table)->group('user_id')->count();
                    break;
            }
        }
        if(empty($table))
        {
            return DataReturn('类型错误', -1);
        }

        // 总数
        $total_count = Db::name($table)->count();

        // 昨天
        $where = [
            ['add_time', '>=', self::$yesterday_time_start],
            ['add_time', '<=', self::$yesterday_time_end],
        ];
        $yesterday_count = Db::name($table)->where($where)->count();

        // 今天
        $where = [
            ['add_time', '>=', self::$today_time_start],
            ['add_time', '<=', self::$today_time_end],
        ];
        $today_count = Db::name($table)->where($where)->count();

        // 数据组装
        $result = [
            'total_count'       => $total_count,
            'yesterday_count'   => $yesterday_count,
            'today_count'       => $today_count,
            'ext_count'         => $ext_count,
        ];
        return DataReturn('处理成功', 0, $result);
    }

    /**
     * 获取统计数据
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @date     2019-08-08
     * @param    [array]          $params [输入参数]
     */
    public static function StatisticalData($params = [])
    {
        // 初始化
        self::Init($params);

        // 统计数据初始化
        $result = [
            'coupon' => [
                'title'             => '优惠券',
                'count'             => 0,
                'yesterday_count'   => 0,
                'today_count'       => 0,
                'url'               => PluginsAdminUrl('coupon', 'coupon', 'index'),
            ],
            'couponuser' => [
                'title'             => '用户优惠券',
                'count'             => 0,
                'yesterday_count'   => 0,
                'today_count'       => 0,
                'right_count'       => 0,
                'right_title'       => '用户总数',
                'url'               => PluginsAdminUrl('coupon', 'user', 'index'),
            ],
        ];
        $type_all = ['coupon', 'couponuser'];
        foreach($type_all as $type)
        {
            $ret = self::YesterdayTodayTotal(['type'=>$type]);
            if($ret['code'] == 0)
            {
                $result[$type]['count'] = $ret['data']['total_count'];
                $result[$type]['yesterday_count'] = $ret['data']['yesterday_count'];
                $result[$type]['today_count'] = $ret['data']['today_count'];
                if(isset($result[$type]['right_count']) && isset($ret['data']['ext_count']))
                {
                    $result[$type]['right_count'] = $ret['data']['ext_count'];
                }
            }
        }
        return $result;
    }
}
?>