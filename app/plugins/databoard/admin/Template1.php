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
namespace app\plugins\databoard\admin;

use app\plugins\databoard\admin\Common;
use app\plugins\databoard\service\StatsTemplate1Service;

/**
 * 数据看板 - 模板1
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2022-01-26
 * @desc    description
 */
class Template1 extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        MyViewAssign('base_data', $this->base_data);
        return MyView('../../../plugins/view/databoard/admin/template1/index');
    }

    /**
     * 统计数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   array           $params [description]
     */
    public function Stats($params = [])
    {
        // 概述
        $overview = StatsTemplate1Service::Overview($params);

        // 订单、用户列表
        $order_user_list = StatsTemplate1Service::NewOrderUserList($params);

        // 热销商品
        $goods_hot = StatsTemplate1Service::GoodsHot($params);

        // 订单最近6个月总数
        $order_month_total_number = StatsTemplate1Service::OrderMonthTotalNumber($params);

        // 订单按固定天总数
        $order_day_total_number = StatsTemplate1Service::OrderDayTotalNumber($params);

        // 订单销售额
        $order_sales_total_price = StatsTemplate1Service::OrderSalesTotalPrice($params);

        // 支付分布
        $payment_scatter = StatsTemplate1Service::PaymentScatter($params);

        // 当月销售进度
        $month_sales_speed = StatsTemplate1Service::MonthSalesSpeed($params);

        // 全国热榜
        $national_hot = StatsTemplate1Service::NationalHot($params);

        // 地图
        $region_map = StatsTemplate1Service::RegionMap($params);

        $result = [
            'overview'                  => $overview,
            'order_user_list'           => $order_user_list,
            'goods_hot'                 => $goods_hot,
            'order_month_total_number'  => $order_month_total_number,
            'order_day_total_number'    => $order_day_total_number,
            'order_sales_total_price'   => $order_sales_total_price,
            'payment_scatter'           => $payment_scatter,
            'month_sales_speed'         => $month_sales_speed,
            'national_hot'              => $national_hot,
            'region_map'                => $region_map,
        ];
        return DataReturn('success', 0, $result);
    }
}
?>