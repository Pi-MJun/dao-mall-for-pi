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
use app\service\UserService;
use app\plugins\shop\service\BaseService;

/**
 * 多商户 - 收益明细数据
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class ProfitDataService
{
    /**
     * 数据列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params  [输入参数]
     */
    public static function ProfitList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? 'op.*, o.order_no, o.status as order_status, o.pay_status as order_pay_status, o.client_type as order_client_type, o.refund_price, o.collect_time as order_collect_time' : $params['field'];
        $order_by = empty($params['order_by']) ? 'op.id desc' : $params['order_by'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        // 获取数据
        $data = Db::name('PluginsShopOrderProfit')->alias('op')->join('order o', 'op.order_id=o.id')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        return DataReturn('处理成功', 0, self::DataHandle($data, $params));
    }

    /**
     * 数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-18
     * @desc    description
     * @param   [array]          $data      [数据]
     * @param   [array]          $params    [输入参数]
     */
    public static function DataHandle($data, $params = [])
    {
        if(!empty($data))
        {
            // 状态
            $status_list = array_column(BaseService::$plugins_profit_status_list, 'name', 'value');

            // 插件配置信息
            $base = BaseService::BaseConfig();

            // 结算周期时间
            $profit_settlement_limit_time = (empty($base['data']) || empty($base['data']['profit_settlement_limit_time'])) ? 43200 : intval($base['data']['profit_settlement_limit_time']);

            // 开始处理数据
            $platform_type = MyConst('common_platform_type');
            $order_status_list = MyConst('common_order_status');
            $order_pay_status = MyConst('common_order_pay_status');
            $user_type = isset($params['user_type']) ? $params['user_type'] : 'user';
            foreach($data as &$v)
            {
                // 用户信息
                $v['user'] = ($user_type == 'admin') ? UserService::GetUserViewInfo($v['user_id']) : [];

                // 状态
                $v['status_name'] = (array_key_exists('status', $v) && array_key_exists($v['status'], $status_list)) ? $status_list[$v['status']] : '';

                // 订单状态
                $v['order_status_name'] = $order_status_list[$v['order_status']]['name'];

                // 支付状态
                $v['order_pay_status_name'] = $order_pay_status[$v['order_pay_status']]['name'];

                // 客户端
                $v['order_client_type_name'] = isset($platform_type[$v['order_client_type']]) ? $platform_type[$v['order_client_type']]['name'] : '';

                // 时间
                if(array_key_exists('add_time', $v))
                {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if(array_key_exists('upd_time', $v))
                {
                    $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
                }

                // 结算时间未完成状态下计算剩余时间
                if(array_key_exists('status', $v) && $v['status'] == 2)
                {
                    $v['success_time_icon'] = '预计结算';
                    $v['success_time'] = date('Y-m-d H:i', $v['order_collect_time']+($profit_settlement_limit_time*60));
                }
            }
        }
        return $data;
    }

    /**
     * 数据总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params  [输入参数]
     */
    public static function ProfitTotal($where)
    {
        return (int) Db::name('PluginsShopOrderProfit')->alias('op')->join('order o', 'op.order_id=o.id')->where($where)->count();
    }

    /**
     * 提示信息
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function ProfitTipsMsg($where = [])
    {
        $profit_price = Db::name('PluginsShopOrderProfit')->alias('op')->join('order o', 'op.order_id=o.id')->where($where)->sum('op.profit_price');
        return '总额 '.$profit_price.' π';
    }
}
?>